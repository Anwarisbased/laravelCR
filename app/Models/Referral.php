<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'referrer_user_id',
        'invitee_user_id',
        'referral_code',
        'status',
        'converted_at',
        'bonus_points_awarded',
    ];
    
    protected $casts = [
        'converted_at' => 'datetime',
        'bonus_points_awarded' => 'integer',
    ];
    
    // Relationships
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }
    
    public function invitee()
    {
        return $this->belongsTo(User::class, 'invitee_user_id');
    }
    
    // Scopes
    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
