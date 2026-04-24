<?php

namespace App\Console\Commands;

use App\Models\Blog;
use Illuminate\Console\Command;

class GenerateBlogSitemap extends Command
{
    protected $signature   = 'sitemap:blog:generate';
    protected $description = 'Generate XML sitemap for all published blog posts';

    public function handle(): void
    {
        $webUrl = rtrim(config('api.web_app.url', env('WEB_APP_URL', 'https://stardenaworks.com')), '/');
        $this->info("Generating blog sitemap for frontend: {$webUrl}");

        $urls = [];

        // ── Static blog pages ──────────────────────────────────────────────────
        $urls[] = $this->makeUrl($webUrl . '/blog',                    'daily',   '0.9');
        $urls[] = $this->makeUrl($webUrl . '/blog/categories',         'weekly',  '0.7');
        $urls[] = $this->makeUrl($webUrl . '/blog/tags',               'weekly',  '0.6');

        // ── Category pages (from existing categories) ─────────────────────────
        $categories = Blog::where('is_active', true)
            ->where('is_published', true)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        $this->info("Adding {$categories->count()} category archive pages...");

        foreach ($categories as $cat) {
            $urls[] = $this->makeUrl(
                $webUrl . '/blog/category/' . urlencode($cat),
                'weekly',
                '0.7'
            );
        }

        // ── Tag pages (from existing tags) ────────────────────────────────────
        $tags = Blog::where('is_active', true)
            ->where('is_published', true)
            ->whereNotNull('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->values();

        $this->info("Adding {$tags->count()} tag archive pages...");

        foreach ($tags as $tag) {
            $urls[] = $this->makeUrl(
                $webUrl . '/blog/tag/' . urlencode($tag),
                'weekly',
                '0.6'
            );
        }

        // ── Individual blog posts ─────────────────────────────────────────────
        $blogCount = Blog::where('is_active', true)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('slug')
            ->count();

        $this->info("Adding {$blogCount} blog pages...");

        Blog::select(['slug', 'updated_at', 'published_at', 'is_featured'])
            ->where('is_active', true)
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('slug')
            ->orderBy('published_at', 'desc')
            ->chunk(500, function ($blogs) use (&$urls, $webUrl) {
                foreach ($blogs as $blog) {
                    $urls[] = $this->makeUrl(
                        $webUrl . '/blog/' . $blog->slug,
                        'weekly',
                        $blog->is_featured ? '0.9' : '0.8',
                        $blog->updated_at?->toAtomString()
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

        // Save to public directory
        file_put_contents(public_path('blog-sitemap.xml'), $xml);

        $this->info("✓ Blog sitemap generated: {$total} URLs total");
        $this->info('✓ Sitemap URL: ' . $webUrl . '/blog-sitemap.xml');
        
        // Also update robots.txt to include blog sitemap
        $this->updateRobotsTxt($webUrl);
    }

    private function makeUrl(string $loc, string $changefreq, string $priority, ?string $lastmod = null): string
    {
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

    private function updateRobotsTxt(string $webUrl): void
    {
        $robotsPath = public_path('robots.txt');
        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Allow: /blog/\n";
        $content .= "Disallow: /api/\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /dashboard/\n\n";
        $content .= "Sitemap: {$webUrl}/sitemap.xml\n";
        $content .= "Sitemap: {$webUrl}/blog-sitemap.xml\n";
        
        file_put_contents($robotsPath, $content);
        $this->info('✓ Updated robots.txt with blog sitemap');
    }
}