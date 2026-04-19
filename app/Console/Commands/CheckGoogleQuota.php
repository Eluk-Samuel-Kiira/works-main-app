<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SearchEnginePingService;

class CheckGoogleQuota extends Command
{
    protected $signature = 'google:quota';
    protected $description = 'Check Google Indexing API quota usage';

    public function handle(SearchEnginePingService $service)
    {
        $quota = $service->getGoogleQuotaStatus();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Quota Limit', $quota['limit']],
                ['Submitted Today', $quota['submitted_today']],
                ['Remaining', $quota['remaining']],
                ['Available', $quota['available'] ? 'Yes' : 'No'],
            ]
        );
    }
}