<?php
// app/Models/Notification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'notifications';

    protected $fillable = [
        'type',
        'title',
        'message',
        'data',
        'status',
        'priority',
        'read_at',
        'user_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Scopes
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'unread');
    }

    // Methods
    public function markAsRead()
    {
        $this->update([
            'read_at' => now(),
            'status' => 'read'
        ]);
    }

    public function markAsResolved()
    {
        $this->update([
            'status' => 'resolved'
        ]);
    }

    // Relationship with user (if you want to track who reported)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}