<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'sku',
        'description',
        'short_description',
        'points_award',
        'points_cost',
        'required_rank_key',
        'is_active',
        'is_featured',
        'is_new',
        'category_id',
        'brand',
        'strain_type',
        'thc_content',
        'cbd_content',
        'product_form',
        'marketing_snippet',
        'image_urls',
        'tags',
        'sort_order',
        'available_from',
        'available_until',
    ];
    
    protected $casts = [
        'points_award' => 'integer',
        'points_cost' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'image_urls' => 'array',
        'tags' => 'array',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'thc_content' => 'float',
        'cbd_content' => 'float',
    ];
    
    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }
    
    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('available_from')
                  ->orWhere('available_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('available_until')
                  ->orWhere('available_until', '>=', now());
            });
    }
    
    public function scopeRewardable(Builder $query): Builder
    {
        return $query->where('points_cost', '>', 0);
    }
    
    public function scopeByCategory(Builder $query, $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }
    
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
    
    public function scopeNew(Builder $query): Builder
    {
        return $query->where('is_new', true);
    }
    
    // Relationships
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    // Methods
    public function isInStock(): bool
    {
        // Implement stock checking logic if needed
        return true;
    }
    
    public function isAvailable(): bool
    {
        return $this->is_active && 
               (!$this->available_from || $this->available_from <= now()) &&
               (!$this->available_until || $this->available_until >= now());
    }
}
