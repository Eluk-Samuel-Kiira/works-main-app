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

        $urls[] = $this->makeUrl($webUrl, 'daily', '1.0');
        $urls[] = $this->makeUrl($webUrl . '/jobs', 'hourly', '0.9');

        $jobCount = JobPost::where('is_active', true)
            ->where('deadline', '>=', now())
            ->whereNotNull('slug')       // ← skip null slugs
            ->where('slug', '!=', '')    // ← skip empty slugs
            ->count();

        $this->info("Adding {$jobCount} jobs to sitemap...");

        JobPost::select(['slug', 'updated_at', 'published_at', 'deadline', 'is_featured'])
            ->where('is_active', true)
            ->where('deadline', '>=', now())
            ->whereNotNull('slug')       // ← skip null slugs
            ->where('slug', '!=', '')    // ← skip empty slugs
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

        
            // Filter out any empty strings from invalid URLs
        
        $urls = array_filter($urls);

        // Correct XML — declaration + namespace on same line, no whitespace
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= implode('', $urls);
        $xml .= '</urlset>';

        file_put_contents(public_path('sitemap.xml'), $xml);

        $this->info('✓ Sitemap generated: ' . public_path('sitemap.xml'));
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