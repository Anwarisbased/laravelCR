<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionLog extends Model
{
    protected $table = 'canna_user_action_log';
    
    protected $primaryKey = 'log_id';
    
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'action_type',
        'object_id',
        'meta_data',
        'result',
        'created_at',
    ];
    
    protected $casts = [
        'meta_data' => 'array',
        'created_at' => 'datetime',
    ];
    
    /**
     * The user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}