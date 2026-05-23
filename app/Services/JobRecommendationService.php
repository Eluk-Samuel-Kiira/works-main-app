<?php
// MAIN APP: app/Services/JobRecommendationService.php

namespace App\Services;

use App\Models\Job\JobPost;
use App\Models\Seeker\SeekerCV;
use Illuminate\Support\Facades\{Cache, Log, Http};
use Illuminate\Support\Collection;

class JobRecommendationService
{
    // ── Scoring weights (total = 100) ────────────────────────────────────
    private const W_SKILLS     = 40;
    private const W_TITLE      = 20;
    private const W_LOCATION   = 15;
    private const W_EXPERIENCE = 12;
    private const W_JOB_TYPE   = 8;
    private const W_RECENCY    = 5;

    private const CANDIDATE_POOL = 80;
    private const CACHE_TTL      = 300; // 5 min

    // ─────────────────────────────────────────────────────────────────────
    // Public entry point
    // ─────────────────────────────────────────────────────────────────────
    public function getRecommendations(int $userId, int $limit = 6): array
    {
        return Cache::remember("job_recs_user_{$userId}", self::CACHE_TTL, function () use ($userId, $limit) {

            $cv = SeekerCV::where('user_id', $userId)->first();

            // No CV at all → return trending + flag
            if (!$cv) {
                return [
                    'jobs'        => $this->getTrendingJobs($limit),
                    'has_profile' => false,
                    'mode'        => 'trending',
                    'message'     => 'Upload or create your CV to get personalised recommendations.',
                ];
            }

            $profile = $this->buildProfile($cv);
            $hasSignal = !empty($profile['skills']) || !empty($profile['title']);

            // CV exists but empty → return trending + encourage completion
            if (!$hasSignal) {
                return [
                    'jobs'        => $this->getTrendingJobs($limit),
                    'has_profile' => true,
                    'mode'        => 'trending',
                    'message'     => 'Add your skills and job title to your CV for better matches.',
                ];
            }

            // Full personalised recommendation
            $candidates = $this->fetchCandidates($profile);
            $scored     = $this->scoreAndSort($candidates, $profile);

            // Pad with trending if not enough matches
            if ($scored->count() < $limit) {
                $existingIds = $scored->pluck('id')->toArray();
                $trending    = $this->getTrendingJobs($limit - $scored->count(), $existingIds);
                $scored      = $scored->concat($trending);
            }

            $top = $scored->take($limit)->values();

            return [
                'jobs'        => $top->toArray(),
                'has_profile' => true,
                'mode'        => 'personalised',
                'message'     => null,
            ];
        });
    }

    public function clearCache(int $userId): void
    {
        Cache::forget("job_recs_user_{$userId}");
    }

    // ─────────────────────────────────────────────────────────────────────
    // Build a normalised seeker profile from the CV
    // ─────────────────────────────────────────────────────────────────────
    private function buildProfile(SeekerCV $cv): array
    {
        $prefs = $cv->job_preferences ?? [];

        // Compute years of experience from work history
        $yearsExp = $cv->years_of_experience ?? 0;
        if ($yearsExp === 0 && !empty($cv->work_experience)) {
            foreach ($cv->work_experience as $exp) {
                if (empty($exp['start_date'])) continue;
                $start = strtotime($exp['start_date']);
                $end   = ($exp['current'] ?? false) || empty($exp['end_date'])
                       ? time()
                       : strtotime($exp['end_date']);
                if ($start && $end > $start) {
                    $yearsExp += ($end - $start) / (365.25 * 86400);
                }
            }
            $yearsExp = round($yearsExp, 1);
        }

        return [
            // Normalised arrays for comparison
            'skills'    => array_map('strtolower', array_map('trim', $cv->skills ?? [])),
            'title'     => strtolower(trim($cv->professional_title ?? '')),
            'city'      => strtolower(trim($cv->city    ?? '')),
            'country'   => strtolower(trim($cv->country ?? '')),
            'years_exp' => $yearsExp,
            'job_types' => array_map('strtolower', $prefs['job_types']  ?? []),
            'locations' => array_map('strtolower', $prefs['locations']  ?? []),
            'salary_min'=> (int)($prefs['salary_min'] ?? 0),
            'salary_max'=> (int)($prefs['salary_max'] ?? 0),
            // Raw strings kept for display / Gemini context
            'raw_title' => $cv->professional_title ?? '',
            'raw_skills'=> implode(', ', $cv->skills ?? []),
            'summary'   => strtolower($cv->professional_summary ?? ''),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Pull candidate jobs from DB using broad indexed filters
    // ─────────────────────────────────────────────────────────────────────
    private function fetchCandidates(array $p): Collection
    {
        $query = JobPost::with(['company', 'jobType', 'jobLocation', 'experienceLevel', 'salaryRange'])
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('deadline', '>=', now())
            ->select([
                'id','slug','job_title','job_description','skills','qualifications',
                'employment_type','location_type','duty_station','salary_amount',
                'currency','payment_period','base_salary',
                'company_id','job_type_id','job_location_id','experience_level_id',
                'salary_range_id','is_featured','is_urgent','published_at','deadline',
            ])
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->limit(self::CANDIDATE_POOL);

        // Soft location pre-filter to shrink the pool
        if ($p['city'] || $p['country']) {
            $query->where(function ($q) use ($p) {
                if ($p['city']) {
                    $q->orWhere('duty_station', 'like', '%'.$p['city'].'%');
                }
                if ($p['country']) {
                    $q->orWhere('duty_station', 'like', '%'.$p['country'].'%');
                }
                $q->orWhere('location_type', 'remote');
            });
        }

        return $query->get();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Score every candidate and return sorted collection (desc)
    // ─────────────────────────────────────────────────────────────────────
    private function scoreAndSort(Collection $jobs, array $profile): Collection
    {
        return $jobs
            ->map(fn($job) => $this->scoreJob($job, $profile))
            ->filter(fn($j) => $j['_score'] > 5)   // drop zero-signal jobs
            ->sortByDesc('_score')
            ->values();
    }

    private function scoreJob(JobPost $job, array $p): array
    {
        $score   = 0;
        $reasons = [];

        $jobTitle = strtolower($job->job_title ?? '');
        $jobDesc  = strtolower(substr($job->job_description ?? '', 0, 2000)); // avoid huge strings
        $jobSkills= strtolower(is_array($job->skills) ? implode(' ', $job->skills) : ($job->skills ?? ''));
        $jobQual  = strtolower($job->qualifications ?? '');
        $jobText  = "{$jobTitle} {$jobDesc} {$jobSkills} {$jobQual}";

        // ── 1. Skills (40 pts) ────────────────────────────────────────────
        if (!empty($p['skills'])) {
            $matched = [];
            foreach ($p['skills'] as $skill) {
                if (strlen($skill) < 2) continue;
                // Title hit = stronger signal (×3), body hit = (×1)
                if (str_contains($jobTitle,  $skill)) { $matched[$skill] = 3; }
                elseif (str_contains($jobSkills, $skill)) { $matched[$skill] = 2; }
                elseif (str_contains($jobText,   $skill)) { $matched[$skill] = 1; }
            }
            $rawHits   = array_sum($matched);
            $maxHits   = count($p['skills']) * 3;
            $skillPct  = $maxHits > 0 ? min($rawHits / $maxHits, 1) : 0;
            $skillPts  = round($skillPct * self::W_SKILLS, 1);
            $score    += $skillPts;

            if ($matched) {
                $topSkills = array_slice(array_keys($matched), 0, 3);
                $reasons[] = count($matched).' skill'.( count($matched)>1?'s':'' ).' matched: '.implode(', ', $topSkills);
            }
        }

        // ── 2. Title (20 pts) ─────────────────────────────────────────────
        if (!empty($p['title'])) {
            $words   = array_filter(explode(' ', $p['title']), fn($w) => strlen($w) > 2);
            $matched = array_filter($words, fn($w) => str_contains($jobTitle, $w));
            $titlePt = count($words) > 0
                ? (count($matched) / count($words)) * self::W_TITLE
                : 0;
            $score += round($titlePt, 1);
            if ($titlePt > 8) $reasons[] = 'Matches your role: '.$p['raw_title'];
        }

        // ── 3. Location (15 pts) ──────────────────────────────────────────
        $jobLoc  = strtolower($job->duty_station ?? '');
        $locType = strtolower($job->location_type ?? '');
        $locPts  = 0;

        if ($locType === 'remote' || str_contains($jobLoc, 'remote')) {
            $locPts = self::W_LOCATION;
            $reasons[] = 'Remote — work from anywhere';
        } elseif ($p['city'] && str_contains($jobLoc, $p['city'])) {
            $locPts = self::W_LOCATION;
            $reasons[] = 'Based in '.ucfirst($p['city']);
        } elseif ($p['country'] && str_contains($jobLoc, $p['country'])) {
            $locPts = self::W_LOCATION * 0.6;
        }
        // Preferred locations from preferences
        foreach ($p['locations'] as $prefLoc) {
            if ($prefLoc && str_contains($jobLoc, $prefLoc)) {
                $locPts = max($locPts, self::W_LOCATION * 0.8);
            }
        }
        $score += round(min($locPts, self::W_LOCATION), 1);

        // ── 4. Experience (12 pts) ────────────────────────────────────────
        $expLevel = strtolower($job->experienceLevel?->name ?? '');
        $yrs      = $p['years_exp'];
        $expPts   = 0;

        $bands = [
            'entry'     => [0, 2],
            'junior'    => [1, 3],
            'associate' => [1, 4],
            'mid'       => [3, 6],
            'senior'    => [5, 12],
            'lead'      => [6, 15],
            'principal' => [8, 20],
            'executive' => [10, 30],
            'director'  => [12, 30],
            'manager'   => [4, 15],
        ];
        foreach ($bands as $kw => [$min, $max]) {
            if (str_contains($expLevel, $kw)) {
                if ($yrs >= $min && $yrs <= $max) {
                    $expPts = self::W_EXPERIENCE;
                } elseif (abs($yrs - $min) <= 1 || abs($yrs - $max) <= 1) {
                    $expPts = self::W_EXPERIENCE * 0.6;  // within 1 year of band
                } else {
                    $expPts = self::W_EXPERIENCE * 0.2;  // mismatch but give partial
                }
                break;
            }
        }
        if ($expPts === 0 && $yrs > 0) $expPts = self::W_EXPERIENCE * 0.3; // unknown level
        $score += round($expPts, 1);

        // ── 5. Job type preference (8 pts) ────────────────────────────────
        $empType = strtolower($job->employment_type ?? '');
        if (!empty($p['job_types'])) {
            if (in_array($empType, $p['job_types'])) {
                $score += self::W_JOB_TYPE;
                $reasons[] = ucfirst($empType).' — your preferred type';
            }
        } else {
            $score += self::W_JOB_TYPE * 0.4; // no preference → partial
        }

        // ── 6. Recency (5 pts) ────────────────────────────────────────────
        $daysOld  = $job->published_at ? now()->diffInDays($job->published_at) : 30;
        $recencyPt= $daysOld <= 3  ? self::W_RECENCY
                  : ($daysOld <= 7  ? self::W_RECENCY * 0.8
                  : ($daysOld <= 14 ? self::W_RECENCY * 0.5
                  : ($daysOld <= 30 ? self::W_RECENCY * 0.2 : 0)));
        $score += round($recencyPt, 1);

        // ── Bonus ─────────────────────────────────────────────────────────
        if ($job->is_featured) $score += 3;
        if ($job->is_urgent)   $score += 2;

        $finalScore = round(min($score, 100), 1);

        // ── Format salary ─────────────────────────────────────────────────
        $salary = 'Negotiable';
        if ($job->salary_amount) {
            $salary = ($job->currency ?? 'UGX').' '.number_format($job->salary_amount);
            if ($job->payment_period) $salary .= '/'.$job->payment_period;
        } elseif ($job->salaryRange?->name) {
            $salary = $job->salaryRange->name;
        }

        return [
            // Identity
            'id'              => $job->id,
            'slug'            => $job->slug,
            'job_title'       => $job->job_title,
            'duty_station'    => $job->duty_station,
            'location_type'   => $job->location_type,
            'employment_type' => $job->employment_type,
            'formatted_salary'=> $salary,
            'is_featured'     => (bool)$job->is_featured,
            'is_urgent'       => (bool)$job->is_urgent,
            'created_at'      => $job->published_at,
            'deadline'        => $job->deadline?->format('d M Y'),
            // Relations
            'company' => $job->company ? [
                'id'       => $job->company->id,
                'name'     => $job->company->name,
                'logo_url' => $job->company->logo_url ?? null,
                'logo'     => $job->company->logo     ?? null,
            ] : null,
            'job_type' => $job->jobType ? [
                'name' => $job->jobType->name,
            ] : null,
            'experience_level' => $job->experienceLevel ? [
                'name' => $job->experienceLevel->name,
            ] : null,
            // Recommendation metadata
            'recommendation_score'  => $finalScore,
            'match_reasons'         => array_slice($reasons, 0, 2),
            // Internal sort key (not sent to client, stripped in controller)
            '_score'                => $finalScore,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Trending fallback — no CV or empty CV
    // ─────────────────────────────────────────────────────────────────────
    private function getTrendingJobs(int $limit, array $excludeIds = []): Collection
    {
        $query = JobPost::with(['company', 'jobType', 'jobLocation', 'experienceLevel', 'salaryRange'])
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('deadline', '>=', now())
            ->orderByDesc('is_featured')
            ->orderByDesc('view_count')
            ->orderByDesc('published_at')
            ->limit($limit);

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->get()->map(fn($job) => [
            'id'              => $job->id,
            'slug'            => $job->slug,
            'job_title'       => $job->job_title,
            'duty_station'    => $job->duty_station,
            'location_type'   => $job->location_type,
            'employment_type' => $job->employment_type,
            'formatted_salary'=> $job->salary_amount
                ? ($job->currency ?? 'UGX').' '.number_format($job->salary_amount)
                : ($job->salaryRange?->name ?? 'Negotiable'),
            'is_featured'     => (bool)$job->is_featured,
            'is_urgent'       => (bool)$job->is_urgent,
            'created_at'      => $job->published_at,
            'deadline'        => $job->deadline?->format('d M Y'),
            'company' => $job->company ? [
                'id'       => $job->company->id,
                'name'     => $job->company->name,
                'logo_url' => $job->company->logo_url ?? null,
                'logo'     => $job->company->logo     ?? null,
            ] : null,
            'job_type' => $job->jobType ? [
                'name' => $job->jobType->name,
            ] : null,
            'experience_level' => $job->experienceLevel ? [
                'name' => $job->experienceLevel->name,
            ] : null,
            'recommendation_score' => 0,
            'match_reasons'        => ['Trending in your area'],
            '_score'               => 0,
        ]);
    }
}