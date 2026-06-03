<?php
namespace App\Console\Commands;

use App\Models\Job\JobPost;
use App\Models\Job\JobLocation;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature   = 'sitemap:generate';
    protected $description = 'Generate XML sitemap for all active job posts';

    // Country codes and their suffixes
    private const COUNTRY_SUFFIXES = [
        'ke' => '-ke',
        // 'tz' => '-tz',
        // 'rw' => '-rw',
        'ug' => '-ug',
        'ng' => '-ng',
        // 'za' => '-za',
        // 'bi' => '-bi',
        // 'ss' => '-ss',
    ];

    public function handle(): void
    {
        $webUrl = rtrim(config('api.web_app.url'), '/');
        $this->info("Generating sitemap for frontend: {$webUrl}");
        
        // Generate original sitemap.xml (ONLY jobs WITHOUT country suffixes)
        $this->generateOriginalSitemap($webUrl);
        
        // Generate country-specific sitemaps (ONLY jobs WITH matching suffixes)
        $this->generateCountrySitemaps($webUrl);
        
        // Generate sitemap index
        $this->generateSitemapIndex($webUrl);
        
        $this->info("✓ All sitemaps generated successfully!");
    }

    /**
     * Generate ORIGINAL sitemap.xml - ONLY jobs WITHOUT country suffixes
     * These are the old URLs that are already indexed by Google
     */
    private function generateOriginalSitemap(string $webUrl): void
    {
        $urls = [];

        // ── Static pages ──────────────────────────────────────────────────────
        $urls[] = $this->makeUrl($webUrl, 'daily', '1.0');
        $urls[] = $this->makeUrl($webUrl . '/jobs', 'hourly', '0.9');
        $urls[] = $this->makeUrl($webUrl . '/cv-builder', 'weekly', '0.9');
        $urls[] = $this->makeUrl($webUrl . '/companies', 'daily', '0.8');
        $urls[] = $this->makeUrl($webUrl . '/about', 'monthly', '0.5');
        $urls[] = $this->makeUrl($webUrl . '/contact', 'monthly', '0.4');
        $urls[] = $this->makeUrl($webUrl . '/privacy-policy', 'monthly', '0.3');

        // ── Category pages ────────────────────────────────────────────────────
        $categories = \App\Models\Job\JobCategory::where('is_active', true)
            ->withCount(['jobPosts' => fn($q) => $q->where('is_active', true)->where('deadline', '>=', now())])
            ->get()
            ->filter(fn($cat) => $cat->job_posts_count > 0 && !empty($cat->slug));

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
            ->withCount(['jobPosts' => fn($q) => $q->where('is_active', true)->where('deadline', '>=', now())])
            ->get()
            ->filter(fn($c) => $c->job_posts_count > 0 && !empty($c->slug));

        foreach ($companies as $company) {
            $urls[] = $this->makeUrl(
                $webUrl . '/jobs?company=' . $company->slug,
                'weekly',
                '0.7',
                $company->updated_at?->toAtomString()
            );
        }

        // ── Individual job posts ──────────────────────────────────────────────
        // ⭐ ONLY include jobs whose slugs do NOT end with any country suffix
        $jobs = JobPost::where('is_active', true)
            ->where('deadline', '>=', now())
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->get(['slug', 'updated_at', 'published_at', 'is_featured']);

        foreach ($jobs as $job) {
            $slug = $job->slug;
            $hasCountrySuffix = false;
            
            // Check if slug ends with any country suffix
            foreach (self::COUNTRY_SUFFIXES as $suffix) {
                if (str_ends_with($slug, $suffix)) {
                    $hasCountrySuffix = true;
                    break;
                }
            }
            
            // ⭐ ONLY include if NO country suffix found
            if (!$hasCountrySuffix) {
                $urls[] = $this->makeUrl(
                    $webUrl . '/jobs/' . $slug,
                    'weekly',
                    $job->is_featured ? '0.9' : '0.8',
                    $job->updated_at?->toAtomString()
                );
            }
        }

        // ── Build XML ─────────────────────────────────────────────────────────
        $urls = array_filter($urls);
        $total = count($urls);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= implode('', $urls);
        $xml .= '</urlset>';

        file_put_contents(public_path('sitemap.xml'), $xml);

        $this->info("✓ Original sitemap.xml generated: {$total} URLs (ONLY jobs WITHOUT country codes)");
    }

    /**
     * Generate country-specific sitemaps
     * Each sitemap ONLY contains jobs whose slugs end with that country's suffix
     */
    private function generateCountrySitemaps(string $webUrl): void
    {
        $countryConfigs = [
            'ke' => ['name' => 'Kenya', 'suffix' => '-ke', 'country_code' => 'KE'],
            'tz' => ['name' => 'Tanzania', 'suffix' => '-tz', 'country_code' => 'TZ'],
            'rw' => ['name' => 'Rwanda', 'suffix' => '-rw', 'country_code' => 'RW'],
            'ug' => ['name' => 'Uganda', 'suffix' => '-ug', 'country_code' => 'UG'],
            'ng' => ['name' => 'Nigeria', 'suffix' => '-ng', 'country_code' => 'NG'],
            'za' => ['name' => 'South Africa', 'suffix' => '-za', 'country_code' => 'ZA'],
            'bi' => ['name' => 'Burundi', 'suffix' => '-bi', 'country_code' => 'BI'],
            'ss' => ['name' => 'South Sudan', 'suffix' => '-ss', 'country_code' => 'SS'],
        ];

        foreach ($countryConfigs as $code => $config) {
            $this->info("Generating sitemap for {$config['name']}...");
            $urls = [];
            
            // Country-specific static pages
            $urls[] = $this->makeUrl(
                $webUrl . '/' . $code,
                'daily',
                '0.9',
                now()->toAtomString()
            );
            
            $urls[] = $this->makeUrl(
                $webUrl . '/' . $code . '/jobs',
                'hourly',
                '0.8',
                now()->toAtomString()
            );
            
            // Location pages for this country
            $locations = JobLocation::where('country', $config['country_code'])
                ->where('is_active', true)
                ->withCount('jobPosts')
                ->get()
                ->filter(fn($loc) => $loc->job_posts_count > 0);
            
            foreach ($locations as $location) {
                $urls[] = $this->makeUrl(
                    $webUrl . '/' . $code . '/jobs/location/' . $location->slug,
                    'weekly',
                    '0.7',
                    $location->updated_at?->toAtomString()
                );
            }
            
            // ⭐ ONLY include jobs whose slugs end with this country's suffix
            $jobs = JobPost::where('is_active', true)
                ->where('deadline', '>=', now())
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->where('slug', 'like', '%' . $config['suffix']) // Ends with -ke, -ug, etc.
                ->get(['slug', 'updated_at', 'is_featured']);
            
            foreach ($jobs as $job) {
                // Country-prefixed URL: /ke/jobs/{slug}
                $urls[] = $this->makeUrl(
                    $webUrl . '/' . $code . '/jobs/' . $job->slug,
                    'weekly',
                    $job->is_featured ? '0.8' : '0.6',
                    $job->updated_at?->toAtomString()
                );
            }
            
            // Build country sitemap
            $urls = array_filter($urls);
            $total = count($urls);
            
            if ($total > 0) {
                $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
                $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
                $xml .= implode('', $urls);
                $xml .= '</urlset>';
                
                $filename = "sitemap_{$code}.xml";
                file_put_contents(public_path($filename), $xml);
                
                $this->info("  ✓ Generated {$total} URLs for {$config['name']}");
            } else {
                $this->info("  ✓ No URLs found for {$config['name']}");
            }
        }
    }
    
    /**
     * Generate sitemap index file
     */
    private function generateSitemapIndex(string $webUrl): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Add original sitemap
        $xml .= $this->makeSitemapEntry($webUrl . '/sitemap.xml', now()->toAtomString());
        
        // Add country sitemaps if they have content
        $countryCodes = ['ke', 'tz', 'rw', 'ug', 'ng', 'za', 'bi', 'ss'];
        foreach ($countryCodes as $code) {
            $filePath = public_path("sitemap_{$code}.xml");
            if (file_exists($filePath) && filesize($filePath) > 100) { // Has actual content
                $xml .= $this->makeSitemapEntry($webUrl . "/sitemap_{$code}.xml", now()->toAtomString());
            }
        }
        
        $xml .= '</sitemapindex>';
        file_put_contents(public_path('sitemap_index.xml'), $xml);
        
        $this->info("✓ Sitemap index created");
    }

    private function makeUrl(string $loc, string $changefreq, string $priority, ?string $lastmod = null): string
    {
        if (empty(trim($loc)) || !filter_var($loc, FILTER_VALIDATE_URL)) {
            return '';
        }

        $tag = "  <url>\n";
        $tag .= "    <loc>" . htmlspecialchars(trim($loc)) . "</loc>\n";
        if ($lastmod) {
            $tag .= "    <lastmod>{$lastmod}</lastmod>\n";
        }
        $tag .= "    <changefreq>{$changefreq}</changefreq>\n";
        $tag .= "    <priority>{$priority}</priority>\n";
        $tag .= "  </url>\n";
        return $tag;
    }
    
    private function makeSitemapEntry(string $loc, string $lastmod): string
    {
        return "  <sitemap>\n" .
               "    <loc>{$loc}</loc>\n" .
               "    <lastmod>{$lastmod}</lastmod>\n" .
               "  </sitemap>\n";
    }
}