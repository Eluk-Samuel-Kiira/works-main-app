<?php

namespace App\Console\Commands;

use App\Models\Job\JobPost;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature   = 'sitemap:generate';
    protected $description = 'Generate XML sitemap for all active job posts';

    public function handle(): void
    {
        // Use the web_app URL from config/api.php
        $webUrl = rtrim(config('api.web_app.url'), '/');
        
        $this->info("Generating sitemap for frontend: {$webUrl}");
        
        $sitemap = Sitemap::create();
        

        // Homepage
        $sitemap->add(Url::create($webUrl)
            ->setChangeFrequency('daily')
            ->setPriority(1.0));

        // Jobs listing page
        $sitemap->add(Url::create($webUrl . '/jobs')
            ->setChangeFrequency('hourly')
            ->setPriority(0.9));

        // Individual job posts — active and not expired
        $jobCount = JobPost::where('is_active', true)
            ->where('deadline', '>=', now())
            ->count();
        
        $this->info("Adding {$jobCount} jobs to sitemap...");
        
        JobPost::select(['slug', 'updated_at', 'published_at', 'deadline', 'is_featured'])
            ->where('is_active', true)
            ->where('deadline', '>=', now())
            ->orderBy('published_at', 'desc')
            ->chunk(500, function ($jobs) use ($sitemap, $webUrl) {
                foreach ($jobs as $job) {
                    $sitemap->add(
                        Url::create($webUrl . '/jobs/' . $job->slug)
                            ->setLastModificationDate($job->updated_at)
                            ->setChangeFrequency('weekly')
                            ->setPriority($job->is_featured ? 0.9 : 0.8)
                    );
                }
            });

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('✓ Sitemap generated successfully at: ' . public_path('sitemap.xml'));
        $this->info('✓ Sitemap URL: ' . $webUrl . '/sitemap.xml');
    }
}