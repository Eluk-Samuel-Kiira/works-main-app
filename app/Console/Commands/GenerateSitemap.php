<?php
namespace App\Console\Commands;

use App\Models\Job\JobPost;
use App\Models\Job\JobLocation;
use App\Models\Job\JobCategory;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature   = 'sitemap:generate';
    protected $description = 'Generate XML sitemap for all active job posts';

    // Country codes and their suffixes for job slugs
    private const COUNTRY_SUFFIXES = [
        'ke' => '-ke',
        'tz' => '-tz',
        'rw' => '-rw',
        'ug' => '-ug',
        'ng' => '-ng',
        'za' => '-za',
        'bi' => '-bi',
        'ss' => '-ss',
    ];

    // Country mapping for location slugs (from database seeder)
    private const LOCATION_COUNTRY_MAPPING = [
        'ke' => 'KE',
        'ug' => 'UG',
        'ng' => 'NG',
        'tz' => 'TZ',
        'rw' => 'RW',
        'bi' => 'BI',
        'ss' => 'SS',
        'za' => 'ZA',
    ];

    // All supported countries
    private const ALL_COUNTRIES = [
        'ke' => ['name' => 'Kenya', 'country_code' => 'KE', 'enabled' => true],
        'tz' => ['name' => 'Tanzania', 'country_code' => 'TZ', 'enabled' => true],
        'rw' => ['name' => 'Rwanda', 'country_code' => 'RW', 'enabled' => true],
        'ug' => ['name' => 'Uganda', 'country_code' => 'UG', 'enabled' => true],
        'ng' => ['name' => 'Nigeria', 'country_code' => 'NG', 'enabled' => true],
        'za' => ['name' => 'South Africa', 'country_code' => 'ZA', 'enabled' => true],
        'bi' => ['name' => 'Burundi', 'country_code' => 'BI', 'enabled' => true],
        'ss' => ['name' => 'South Sudan', 'country_code' => 'SS', 'enabled' => true],
    ];

    public function handle(): void
    {
        $webUrl = rtrim(config('api.web_app.url'), '/');
        $this->info("Generating sitemap for frontend: {$webUrl}");
        
        // Generate original sitemap.xml (ONLY jobs WITHOUT country suffixes)
        $this->generateOriginalSitemap($webUrl);
        
        // Generate country-specific sitemaps
        $this->generateCountrySitemaps($webUrl);
        
        // Generate sitemap index
        $this->generateSitemapIndex($webUrl);
        
        $this->info("✓ All sitemaps generated successfully!");
    }

    /**
     * Generate ORIGINAL sitemap.xml
     */
    private function generateOriginalSitemap(string $webUrl): void
    {
        $urls = [];

        // Static pages
        $urls[] = $this->makeUrl($webUrl, 'daily', '1.0');
        $urls[] = $this->makeUrl($webUrl . '/jobs', 'hourly', '0.9');
        $urls[] = $this->makeUrl($webUrl . '/cv-builder', 'weekly', '0.9');
        $urls[] = $this->makeUrl($webUrl . '/companies', 'daily', '0.8');
        $urls[] = $this->makeUrl($webUrl . '/about', 'monthly', '0.5');
        $urls[] = $this->makeUrl($webUrl . '/contact', 'monthly', '0.4');
        $urls[] = $this->makeUrl($webUrl . '/privacy-policy', 'monthly', '0.3');

        // Category pages
        $categories = JobCategory::where('is_active', true)
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

        // Company pages
        $companies = \App\Models\Job\Company::where('is_active', true)
            ->withCount(['jobPosts' => fn($q) => $q->where('is_active', true)->where('deadline', '>=', now())])
            ->get()
            ->filter(fn($c) => $c->job_posts_count > 0 && !empty($c->slug));

        foreach ($companies as $company) {
            $urls[] = $this->makeUrl(
                $webUrl . '/company/' . $company->slug,
                'weekly',
                '0.7',
                $company->updated_at?->toAtomString()
            );
        }

        // Individual job posts (without country suffix)
        $jobs = JobPost::where('is_active', true)
            ->where('deadline', '>=', now())
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->get(['slug', 'updated_at', 'published_at', 'is_featured']);

        foreach ($jobs as $job) {
            $slug = $job->slug;
            $hasCountrySuffix = false;
            
            foreach (self::COUNTRY_SUFFIXES as $suffix) {
                if (str_ends_with($slug, $suffix)) {
                    $hasCountrySuffix = true;
                    break;
                }
            }
            
            if (!$hasCountrySuffix) {
                $urls[] = $this->makeUrl(
                    $webUrl . '/jobs/' . $slug,
                    'weekly',
                    $job->is_featured ? '0.9' : '0.8',
                    $job->updated_at?->toAtomString()
                );
            }
        }

        // Build XML
        $urls = array_filter($urls);
        $total = count($urls);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= implode('', $urls);
        $xml .= '</urlset>';

        file_put_contents(public_path('sitemap.xml'), $xml);

        $this->info("✓ Original sitemap.xml generated: {$total} URLs");
    }

    /**
     * Generate country-specific sitemaps
     */
    private function generateCountrySitemaps(string $webUrl): void
    {
        foreach (self::ALL_COUNTRIES as $code => $config) {
            if (!$config['enabled']) {
                continue;
            }
            
            $this->info("Generating sitemap for {$config['name']}...");
            $urls = [];
            
            // 1. Country-specific static pages
            $urls[] = $this->makeUrl($webUrl . '/' . $code, 'daily', '0.9', now()->toAtomString());
            $urls[] = $this->makeUrl($webUrl . '/' . $code . '/jobs', 'hourly', '0.8', now()->toAtomString());
            $urls[] = $this->makeUrl($webUrl . '/' . $code . '/companies', 'daily', '0.75', now()->toAtomString());
            
            // 2. Country-specific company pages
            $companiesInCountry = \App\Models\Job\Company::where('is_active', true)
                ->whereHas('jobPosts', function($q) use ($config) {
                    $q->where('is_active', true)
                      ->where('deadline', '>=', now())
                      ->whereHas('jobLocation', function($locQ) use ($config) {
                          $locQ->where('country', $config['country_code']);
                      });
                })
                ->get();
            
            foreach ($companiesInCountry as $company) {
                $urls[] = $this->makeUrl(
                    $webUrl . '/' . $code . '/jobs/company/' . $company->slug,
                    'weekly',
                    '0.7',
                    $company->updated_at?->toAtomString()
                );
            }
            $this->info("  ✓ Added " . $companiesInCountry->count() . " company pages");
            
            // 3. Country-specific category pages
            $categoriesWithJobs = JobCategory::where('is_active', true)
                ->withCount([
                    'jobPosts' => function($q) use ($config) {
                        $q->where('is_active', true)
                          ->where('deadline', '>=', now())
                          ->whereHas('jobLocation', function($locQ) use ($config) {
                              $locQ->where('country', $config['country_code']);
                          });
                    }
                ])
                ->having('job_posts_count', '>', 0)
                ->get();
            
            foreach ($categoriesWithJobs as $category) {
                $urls[] = $this->makeUrl(
                    $webUrl . '/' . $code . '/jobs/category/' . $category->slug,
                    'daily',
                    '0.75',
                    $category->updated_at?->toAtomString()
                );
            }
            $this->info("  ✓ Added " . $categoriesWithJobs->count() . " category pages");
            
            // 4. ⭐ Location pages - Using slugs directly from database
            // The slugs are stored as: "kampala-jobs-uganda", "nairobi-jobs-ke", etc.
            $locations = JobLocation::where('country', $config['country_code'])
                ->where('is_active', true)
                ->withCount(['jobPosts' => function($q) {
                    $q->where('is_active', true)->where('deadline', '>=', now());
                }])
                ->having('job_posts_count', '>', 0)
                ->get();
            
            foreach ($locations as $location) {
                // Use the slug exactly as stored in the database
                $urls[] = $this->makeUrl(
                    $webUrl . '/' . $code . '/jobs/location/' . $location->slug,
                    'weekly',
                    '0.7',
                    $location->updated_at?->toAtomString()
                );
            }
            $this->info("  ✓ Added " . $locations->count() . " location pages (using DB slugs)");
            
            // 5. Job detail pages with country suffix
            $suffix = self::COUNTRY_SUFFIXES[$code] ?? '-' . $code;
            $jobs = JobPost::where('is_active', true)
                ->where('deadline', '>=', now())
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->where('slug', 'like', '%' . $suffix)
                ->with(['jobLocation'])
                ->get(['slug', 'updated_at', 'is_featured', 'job_location_id']);
            
            $filteredJobs = $jobs->filter(function($job) use ($config) {
                if ($job->jobLocation) {
                    return $job->jobLocation->country === $config['country_code'];
                }
                return true;
            });
            
            foreach ($filteredJobs as $job) {
                $urls[] = $this->makeUrl(
                    $webUrl . '/' . $code . '/jobs/' . $job->slug,
                    'weekly',
                    $job->is_featured ? '0.8' : '0.6',
                    $job->updated_at?->toAtomString()
                );
            }
            $this->info("  ✓ Added " . $filteredJobs->count() . " job detail pages");
            
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
                
                $this->info("  ✓ Generated {$total} total URLs for {$config['name']}");
                $this->info("");
            } else {
                $this->info("  ⚠ No URLs found for {$config['name']}");
                $this->info("");
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
        foreach (self::ALL_COUNTRIES as $code => $config) {
            if (!$config['enabled']) {
                continue;
            }
            
            $filePath = public_path("sitemap_{$code}.xml");
            if (file_exists($filePath) && filesize($filePath) > 500) {
                $xml .= $this->makeSitemapEntry($webUrl . "/sitemap_{$code}.xml", now()->toAtomString());
                $this->info("  ✓ Added sitemap_{$code}.xml to index");
            }
        }
        
        $xml .= '</sitemapindex>';
        file_put_contents(public_path('sitemap_index.xml'), $xml);
        
        $this->info("✓ Sitemap index created at: sitemap_index.xml");
    }

    /**
     * Generate a single URL entry for sitemap
     */
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
    
    /**
     * Generate sitemap index entry
     */
    private function makeSitemapEntry(string $loc, string $lastmod): string
    {
        return "  <sitemap>\n" .
               "    <loc>" . htmlspecialchars($loc) . "</loc>\n" .
               "    <lastmod>{$lastmod}</lastmod>\n" .
               "  </sitemap>\n";
    }
}