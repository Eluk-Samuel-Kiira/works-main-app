<?php

namespace App\Console\Commands\Seo;

use App\Models\Job\JobPost;
use App\Services\GoogleIndexingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyIndexing extends Command
{
    protected $signature = 'seo:verify-indexing 
                            {--limit=50 : Maximum jobs to verify} 
                            {--days=7 : Check jobs submitted within last N days}
                            {--force : Skip rate limit delays for testing}
                            {--job= : Verify a single job by ID}';

    protected $description = 'Verify Google indexing status for submitted job posts';

    public function handle(GoogleIndexingService $service): int
    {
        $this->info('🔍 Starting Google indexing verification...');
        
        $limit   = (int) $this->option('limit');
        $days    = (int) $this->option('days');
        $force   = $this->option('force');
        $jobId   = $this->option('job');

        // Single job mode
        if ($jobId) {
            return $this->verifySingleJob($service, (int) $jobId);
        }

        // Bulk verification mode
        $jobs = JobPost::where('submitted_to_indexing', true)
            ->where(function ($q) {
                $q->whereNull('is_indexed')
                  ->orWhere('is_indexed', false);
            })
            ->where('indexing_submitted_at', '>=', now()->subDays($days))
            ->orderBy('indexing_submitted_at')
            ->limit($limit)
            ->get();

        if ($jobs->isEmpty()) {
            $this->warn('✓ No pending jobs found to verify.');
            return self::SUCCESS;
        }

        $this->info("Found {$jobs->count()} job(s) to verify (limit: {$limit}, days: {$days})");
        
        $bar = $this->output->createProgressBar($jobs->count());
        $bar->start();

        $verified = 0;
        $indexed  = 0;
        $errors   = 0;

        foreach ($jobs as $job) {
            try {
                $result = $service->verifyIndexingStatus($job->id);
                
                if ($result['success']) {
                    $verified++;
                    if ($result['indexed']) {
                        $indexed++;
                        $job->update(['is_indexed' => true, 'indexed_at' => now()]);
                        $bar->setProgressCharacter('✅');
                    } else {
                        $bar->setProgressCharacter('⏳');
                    }
                } else {
                    $errors++;
                    $bar->setProgressCharacter('❌');
                    $this->warn("Job #{$job->id}: {$result['error']}");
                }
            } catch (\Exception $e) {
                $errors++;
                $bar->setProgressCharacter('💥');
                Log::error("Index verification failed for job #{$job->id}: {$e->getMessage()}");
            }

            $bar->advance();
            
            // Rate limiting (skip with --force)
            if (!$force) {
                usleep(1000000); // 1 second between requests
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Summary table
        $this->table(
            ['Metric', 'Value'],
            [
                ['Jobs checked', $jobs->count()],
                ['Successfully verified', $verified],
                ['Confirmed indexed', $indexed],
                ['Errors', $errors],
                ['Index rate', round(($indexed / max(1, $verified)) * 100, 1) . '%'],
            ]
        );

        // Log results
        Log::info('Index verification completed', [
            'checked'   => $jobs->count(),
            'verified'  => $verified,
            'indexed'   => $indexed,
            'errors'    => $errors,
        ]);

        // Email report if configured
        $this->sendSummaryEmail($verified, $indexed, $errors, $jobs->count());

        $this->info('✨ Verification complete.');
        return self::SUCCESS;
    }

    private function verifySingleJob(GoogleIndexingService $service, int $jobId): int
    {
        $job = JobPost::find($jobId);
        
        if (!$job) {
            $this->error("Job #{$jobId} not found.");
            return self::FAILURE;
        }

        $this->info("Verifying job #{$jobId}: {$job->job_title}");
        
        try {
            $result = $service->verifyIndexingStatus($jobId);
            
            if ($result['success']) {
                if ($result['indexed']) {
                    $job->update(['is_indexed' => true, 'indexed_at' => now()]);
                    $this->info('✅ Job is confirmed indexed by Google.');
                } else {
                    $this->warn('⏳ Job submitted but not yet indexed by Google.');
                }
            } else {
                $this->error("❌ Verification failed: {$result['error']}");
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("💥 Error: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function sendSummaryEmail(int $verified, int $indexed, int $errors, int $total): void
    {
        $adminEmails = array_filter(
            array_map('trim', explode(',', env('ADMIN_EMAILS', '')))
        );
        
        if (empty($adminEmails) || $verified === 0) {
            return;
        }

        $subject = "🔍 Index Verification Report — {$indexed}/{$verified} indexed";
        
        // Simple text email (upgrade to Mailable class if needed)
        $body = "Indexing Verification Summary\n";
        $body .= str_repeat('-', 40) . "\n";
        $body .= "Time: " . now()->format('Y-m-d H:i:s T') . "\n";
        $body .= "Jobs checked: {$total}\n";
        $body .= "Successfully verified: {$verified}\n";
        $body .= "Confirmed indexed: {$indexed}\n";
        $body .= "Errors: {$errors}\n";
        $body .= "Index rate: " . round(($indexed / max(1, $verified)) * 100, 1) . "%\n";
        $body .= "\nView dashboard: " . config('api.web_app.url') . "/admin/job-posts\n";

        foreach ($adminEmails as $email) {
            try {
                \Illuminate\Support\Facades\Mail::raw($body, function ($message) use ($email, $subject) {
                    $message->to($email)
                        ->subject($subject)
                        ->from(config('mail.from.address', 'noreply@stardenaworks.com'), 'Stardena Works SEO');
                });
            } catch (\Exception $e) {
                Log::error("Failed to send verification report to {$email}: {$e->getMessage()}");
            }
        }
    }
}