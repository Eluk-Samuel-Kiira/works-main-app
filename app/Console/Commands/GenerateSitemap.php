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

        // Build XML manually — no Spatie view dependency
        $urls = [];

        // Homepage
        $urls[] = $this->makeUrl($webUrl, 'daily', '1.0');

        // Jobs listing
        $urls[] = $this->makeUrl($webUrl . '/jobs', 'hourly', '0.9');

        // Individual jobs
        $jobCount = JobPost::where('is_active', true)
            ->where('deadline', '>=', now())
            ->count();

        $this->info("Adding {$jobCount} jobs to sitemap...");

        JobPost::select(['slug', 'updated_at', 'published_at', 'deadline', 'is_featured'])
            ->where('is_active', true)
            ->where('deadline', '>=', now())
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

        // Build XML string directly
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= implode('', $urls);
        $xml .= '</urlset>';

        // Write to public/ for the proxy route to serve
        file_put_contents(public_path('sitemap.xml'), $xml);

        $this->info('✓ Sitemap generated successfully at: ' . public_path('sitemap.xml'));
        $this->info('✓ Sitemap URL: ' . $webUrl . '/sitemap.xml');
    }

    private function makeUrl(string $loc, string $changefreq, string $priority, ?string $lastmod = null): string
    {
        $tag  = "  <url>\n";
        $tag .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        if ($lastmod) {
            $tag .= "    <lastmod>{$lastmod}</lastmod>\n";
        }
        $tag .= "    <changefreq>{$changefreq}</changefreq>\n";
        $tag .= "    <priority>{$priority}</priority>\n";
        $tag .= "  </url>\n";
        return $tag;
    }
}