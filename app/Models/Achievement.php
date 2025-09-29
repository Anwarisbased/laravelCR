<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Achievement extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'achievement_key',
        'title',
        'description',
        'points_reward',
        'rarity',
        'icon_url',
        'is_active',
        'trigger_event',
        'trigger_count',
        'conditions',
        'category',
        'sort_order',
        'type',
    ];
    
    protected $casts = [
        'points_reward' => 'integer',
        'is_active' => 'boolean',
        'trigger_count' => 'integer',
        'conditions' => 'array',
        'sort_order' => 'integer',
        'description' => 'string',
    ];
    
    protected $primaryKey = 'achievement_key';
    
    public $incrementing = false;
    
    protected $keyType = 'string';
    
    // Accessors
    public function getKeyAttribute($value)
    {
        return $value ?? $this->attributes['achievement_key'];
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
    
    public function scopeByRarity($query, string $rarity)
    {
        return $query->where('rarity', $rarity);
    }
    
    // Methods
    public function meetsConditions(array $context): bool
    {
        if (empty($this->conditions)) {
            return true;
        }
        
        return app(\App\Services\RulesEngineService::class)->evaluate($this->conditions, $context);
    }
    
    // Relationship
    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class, 'achievement_key', 'achievement_key');
    }
}
