<?php

namespace App\Traits;

use App\Models\Job\JobAuditLog;

trait LogsJobActivities
{
    /**
     * Boot the trait
     */
    protected static function bootLogsJobActivities()
    {
        // Log when a job is created
        static::created(function ($model) {
            JobAuditLog::log(
                $model,
                'created',
                'create',
                auth()->user(),
                null,
                $model->toArray(),
                ['source' => request()->is('api/*') ? 'api' : 'web']
            );
        });

        // Log when a job is updated
        static::updated(function ($model) {
            $oldData = $model->getOriginal();
            $newData = $model->getChanges();
            
            JobAuditLog::log(
                $model,
                'updated',
                'update',
                auth()->user(),
                $oldData,
                $newData,
                ['source' => request()->is('api/*') ? 'api' : 'web']
            );
        });

        // Log when a job is deleted
        static::deleted(function ($model) {
            JobAuditLog::log(
                $model,
                'deleted',
                'delete',
                auth()->user(),
                $model->toArray(),
                null,
                ['source' => request()->is('api/*') ? 'api' : 'web']
            );
        });
    }

    /**
     * Get audit logs for this job
     */
    public function auditLogs()
    {
        return $this->hasMany(JobAuditLog::class, 'job_post_id');
    }

    /**
     * Log a custom action
     */
    public function logAction($action, $event, $metadata = [])
    {
        return JobAuditLog::log(
            $this,
            $action,
            $event,
            auth()->user(),
            null,
            $this->toArray(),
            $metadata
        );
    }
}