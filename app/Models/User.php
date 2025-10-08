<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <--- ADD THIS LINE
use Illuminate\Support\Facades\App;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }
    
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
        'referral_code',
        'meta',
        'lifetime_points',
        'current_rank_key',
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
            'referral_code' => 'string',
            'meta' => 'array',
            'is_admin' => 'boolean',
            'lifetime_points' => 'integer',
        ];
    }
    
    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_user_id');
    }
    
    public function referredBy()
    {
        return $this->hasOne(Referral::class, 'invitee_user_id');
    }
    
    public function getReferralCodeAttribute()
    {
        if (!empty($this->attributes['referral_code'])) {
            return $this->attributes['referral_code'];
        }
        
        return $this->generateReferralCode();
    }
    
    protected function generateReferralCode()
    {
        // Generate unique referral code
        $code = null;
        $attempts = 0;
        $maxAttempts = 10; // Prevent infinite loop
        
        while (!$code && $attempts < $maxAttempts) {
            $baseCode = !empty($this->name) ? substr($this->name, 0, 4) : 'USER';
            $baseCode = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $baseCode));
            $uniquePart = strtoupper(\Illuminate\Support\Str::random(4));
            $potentialCode = $baseCode . $uniquePart;
            
            if (!static::where('referral_code', $potentialCode)->exists()) {
                $code = $potentialCode;
            }
            
            $attempts++;
        }
        
        // Fallback to random code generation if name-based fails
        if (!$code) {
            do {
                $code = strtoupper(\Illuminate\Support\Str::random(8));
            } while (static::where('referral_code', $code)->exists());
        }
        
        $this->referral_code = $code;
        $this->save();
        
        // Cache the referral code for quick lookups
        \Illuminate\Support\Facades\Cache::put("referral_code_{$code}", $this, 3600);
        
        return $code;
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
     * User's current rank relationship
     */
    public function rank()
    {
        return $this->belongsTo(Rank::class, 'current_rank_key', 'key');
    }
    
    /**
     * Get the current rank attribute
     */
    public function getCurrentRankAttribute()
    {
        return $this->rank;
    }
    
    /**
     * Get the next rank attribute
     */
    public function getNextRankAttribute()
    {
        $currentRank = $this->rank;
        if (!$currentRank) {
            return null;
        }
        
        return Rank::where('points_required', '>', $currentRank->points_required)
            ->orderBy('points_required')
            ->first();
    }
    
    /**
     * Ensure the user has a referral code
     */
    public function ensureReferralCode(): void
    {
        if (empty($this->meta['_canna_referral_code'] ?? null)) {
            $referralService = App::make(\App\Services\ReferralService::class);
            $referralService->generate_code_for_new_user(\App\Domain\ValueObjects\UserId::fromInt($this->id), $this->name ?: 'User');
        }
    }
    
    /**
     * User's unlocked achievements relationship
     */
    public function unlockedAchievements()
    {
        return $this->belongsToMany(
            Achievement::class, 
            'user_achievements', 
            'user_id', 
            'achievement_key'
        )->whereNotNull('unlocked_at')
        ->withPivot('unlocked_at', 'trigger_count')->withTimestamps();
    }
    
    /**
     * User's orders relationship
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    
    /**
     * Get the user's points balance
     */
    public function getPointsBalanceAttribute(): int
    {
        return $this->meta[\App\Domain\MetaKeys::POINTS_BALANCE] ?? 0;
    }
    
    /**
     * User's claimed reward codes relationship
     */
    public function claimedRewardCodes()
    {
        return $this->hasMany(RewardCode::class);
    }
}
