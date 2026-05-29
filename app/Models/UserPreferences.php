<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class UserPreferences extends Model
{
    protected $fillable = ['user_id', 'notifications'];
    
    protected $casts = [
        'notifications' => 'array'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}