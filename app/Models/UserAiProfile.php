<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiProfile extends Model
{
    protected $fillable = [
        'user_id',
        'churn_probability',
        'predicted_lifetime_value',
        'engagement_score',
        'product_affinity_scores',
        'purchase_probability',
        'recommended_segment',
        'next_best_action',
        'ai_insights',
    ];

    protected $casts = [
        'product_affinity_scores' => 'array',
        'ai_insights' => 'array',
        'churn_probability' => 'decimal:4',
        'predicted_lifetime_value' => 'decimal:2',
        'engagement_score' => 'decimal:4',
        'purchase_probability' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}