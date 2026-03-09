<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Auth\User;

class JobAuditLog extends Model
{
    use HasFactory;

    protected $table = 'job_audit_logs';

    protected $fillable = [
        'job_post_id',
        'user_id',
        'action',
        'event',
        'old_data',
        'new_data',
        'changes',
        'ip_address',
        'user_agent',
        'source',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'changes' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the job post that was audited
     */
    public function jobPost()
    {
        return $this->belongsTo(JobPost::class);
    }

    /**
     * Get the user who performed the action
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope by action
     */
    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope by event
     */
    public function scopeEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope by source
     */
    public function scopeSource($query, $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get action color for UI
     */
    public function getActionColorAttribute(): string
    {
        $colors = [
            'created' => 'green',
            'updated' => 'blue',
            'deleted' => 'red',
            'featured' => 'yellow',
            'urgent' => 'orange',
            'verified' => 'green',
            'expired' => 'gray',
            'published' => 'green',
            'unpublished' => 'gray',
        ];

        return $colors[$this->action] ?? 'gray';
    }

    /**
     * Get action icon for UI
     */
    public function getActionIconAttribute(): string
    {
        $icons = [
            'created' => 'fa-plus-circle',
            'updated' => 'fa-edit',
            'deleted' => 'fa-trash',
            'featured' => 'fa-star',
            'urgent' => 'fa-exclamation-circle',
            'verified' => 'fa-check-circle',
            'expired' => 'fa-clock',
            'published' => 'fa-globe',
            'unpublished' => 'fa-eye-slash',
        ];

        return $icons[$this->action] ?? 'fa-history';
    }

    /**
     * Get formatted changes for display
     */
    public function getFormattedChangesAttribute(): array
    {
        if (!$this->changes) {
            return [];
        }

        $formatted = [];
        foreach ($this->changes as $field => $change) {
            $old = is_array($change['old'] ?? null) ? json_encode($change['old']) : ($change['old'] ?? null);
            $new = is_array($change['new'] ?? null) ? json_encode($change['new']) : ($change['new'] ?? null);
            
            $formatted[] = [
                'field' => $field,
                'old' => $old,
                'new' => $new,
                'type' => gettype($change['new'] ?? $change['old'] ?? 'string'),
            ];
        }

        return $formatted;
    }

    /**
     * Log a job action
     */
    public static function log($jobPost, $action, $event, $user = null, $oldData = null, $newData = null, $metadata = [])
    {
        $changes = [];
        
        if ($oldData && $newData) {
            foreach ($newData as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] != $value) {
                    $changes[$key] = [
                        'old' => $oldData[$key],
                        'new' => $value,
                    ];
                }
            }
        }

        return self::create([
            'job_post_id' => $jobPost->id,
            'user_id' => $user?->id ?? auth()->id(),
            'action' => $action,
            'event' => $event,
            'old_data' => $oldData,
            'new_data' => $newData,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'source' => $metadata['source'] ?? 'web',
            'notes' => $metadata['notes'] ?? null,
            'metadata' => $metadata,
        ]);
    }
}