<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardCode extends Model
{
    protected $fillable = [
        'code',
        'sku',
        'batch_id',
        'is_used',
        'user_id',
    ];
    
    protected $casts = [
        'is_used' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
