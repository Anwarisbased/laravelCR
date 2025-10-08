<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardCode extends Model
{
    protected $fillable = [
        'code',
        'sku',
        'batch_id',
        'is_used',
        'user_id',
        'product_id',
    ];
    
    protected $casts = [
        'is_used' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * The user who claimed this reward code
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * The product associated with this reward code
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
