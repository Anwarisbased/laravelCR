<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'name',
        'description',
        'points_required',
        'point_multiplier',
        'is_active',
        'sort_order',
        'benefits',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'points_required' => 'integer',
        'point_multiplier' => 'float',
        'is_active' => 'boolean',
        'benefits' => 'array',
    ];

    /**
     * Scope a query to only include active ranks.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order ranks by points required.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('points_required');
    }

    /**
     * Check if a user qualifies for this rank based on lifetime points.
     *
     * @param int $lifetimePoints
     * @return bool
     */
    public function qualifiesFor(int $lifetimePoints): bool
    {
        return $lifetimePoints >= $this->points_required;
    }
}
