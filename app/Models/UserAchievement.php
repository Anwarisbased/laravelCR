<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserAchievement extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'achievement_key',
        'unlocked_at',
        'trigger_count',
    ];
    
    protected $casts = [
        'unlocked_at' => 'datetime',
        'trigger_count' => 'integer',
    ];
    
    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
    
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'achievement_key', 'achievement_key');
    }
}
