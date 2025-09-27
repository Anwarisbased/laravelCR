<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <--- ADD THIS LINE
use Illuminate\Support\Facades\App;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable; // <--- ADD HasApiTokens HERE

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'meta' => 'array', // <-- ADD THIS LINE
            'is_admin' => 'boolean', // Add is_admin field
        ];
    }
    
    /**
     * Boot the model and attach event listeners
     */
    protected static function boot(): void
    {
        parent::boot();
        
        // Generate referral code after creating a user if one doesn't exist
        static::created(function ($user) {
            $user->ensureReferralCode();
        });
        
        // Also ensure referral code on update if it doesn't exist yet
        static::updated(function ($user) {
            if (empty($user->meta['_canna_referral_code'] ?? null)) {
                $user->ensureReferralCode();
            }
        });
    }
    
    /**
     * Ensure the user has a referral code
     */
    public function ensureReferralCode(): void
    {
        if (empty($this->meta['_canna_referral_code'] ?? null)) {
            $referralService = App::make(\App\Services\ReferralService::class);
            $referralService->generate_code_for_new_user($this->id, $this->name ?: 'User');
        }
    }
}
