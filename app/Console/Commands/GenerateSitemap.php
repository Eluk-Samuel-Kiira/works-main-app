<?php
namespace App\Console\Commands;

use App\Models\Job\JobPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GenerateSitemap extends Command
{
    protected $signature   = 'sitemap:generate';
    protected $description = 'Generate XML sitemap for all active job posts';

    public function handle(): void
    {
        $webUrl = rtrim(config('api.web_app.url'), '/');
        $this->info("Generating sitemap for frontend: {$webUrl}");

        $urls = [];

        // ── Static pages ──────────────────────────────────────────────────────
        $urls[] = $this->makeUrl($webUrl,                    'daily',  '1.0');
        $urls[] = $this->makeUrl($webUrl . '/jobs',          'hourly', '0.9');
        $urls[] = $this->makeUrl($webUrl . '/companies',     'daily',  '0.8');
        $urls[] = $this->makeUrl($webUrl . '/about',         'monthly','0.5');
        $urls[] = $this->makeUrl($webUrl . '/contact',       'monthly','0.4');
        $urls[] = $this->makeUrl($webUrl . '/privacy-policy','monthly','0.3');

        // ── Category pages ────────────────────────────────────────────────────
        $categories = \App\Models\Job\JobCategory::where('is_active', true)
            ->withCount([
                'jobPosts' => fn($q) => $q
                    ->where('is_active', true)
                    ->where('deadline', '>=', now())
            ])
            ->get()
            ->filter(fn($cat) => $cat->job_posts_count > 0 && !empty($cat->slug));

        $this->info("Adding {$categories->count()} category pages...");

        foreach ($categories as $cat) {
            $urls[] = $this->makeUrl(
                $webUrl . '/jobs/category/' . $cat->slug,
                'daily',
                '0.8',
                $cat->updated_at?->toAtomString()
            );
        }

        // ── Company pages ─────────────────────────────────────────────────────
        $companies = \App\Models\Job\Company::where('is_active', true)
            ->withCount([
                'jobPosts' => fn($q) => $q
                    ->where('is_active', true)
                    ->where('deadline', '>=', now())
            ])
            ->get()
            ->filter(fn($c) => $c->job_posts_count > 0 && !empty($c->slug));

        $this->info("Adding {$companies->count()} company pages...");

        foreach ($companies as $company) {
            // Company jobs filter page
            $urls[] = $this->makeUrl(
                $webUrl . '/jobs?company=' . $company->slug,
                'weekly',
                '0.7',
                $company->updated_at?->toAtomString()
            );
        }

        // ── Individual job posts ──────────────────────────────────────────────
        $jobCount = JobPost::where('is_active', true)
            ->where('deadline', '>=', now())
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->count();

        $this->info("Adding {$jobCount} job pages...");

        JobPost::select(['slug', 'updated_at', 'published_at', 'deadline', 'is_featured'])
            ->where('is_active', true)
            ->where('deadline', '>=', now())
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('published_at', 'desc')
            ->chunk(500, function ($jobs) use (&$urls, $webUrl) {
                foreach ($jobs as $job) {
                    $urls[] = $this->makeUrl(
                        $webUrl . '/jobs/' . $job->slug,
                        'weekly',
                        $job->is_featured ? '0.9' : '0.8',
                        $job->updated_at?->toAtomString()
                    );
                }
            });

        // ── Build XML ─────────────────────────────────────────────────────────
        $urls = array_filter($urls);
        $total = count($urls);

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= implode('', $urls);
        $xml .= '</urlset>';

        file_put_contents(public_path('sitemap.xml'), $xml);

        $this->info("✓ Sitemap generated: {$total} URLs total");
        $this->info('✓ Sitemap URL: ' . $webUrl . '/sitemap.xml');
    }

    private function makeUrl(string $loc, string $changefreq, string $priority, ?string $lastmod = null): string
    {
        // Skip empty URLs entirely
        if (empty(trim($loc)) || !filter_var($loc, FILTER_VALIDATE_URL)) {
            return '';
        }

        $tag  = "  <url>\n";
        $tag .= "    <loc>" . htmlspecialchars(trim($loc)) . "</loc>\n";
        if ($lastmod) {
            $tag .= "    <lastmod>{$lastmod}</lastmod>\n";
        }
        $tag .= "    <changefreq>{$changefreq}</changefreq>\n";
        $tag .= "    <priority>{$priority}</priority>\n";
        $tag .= "  </url>\n";
        return $tag;
    }
}