# Laravel Vertical Slice 04: Referral System

## Overview
This vertical slice implements the referral system including code generation, referral tracking, and bonus awarding using Laravel's native features, replacing WordPress referral management.

## Key Components

### Laravel Components
- Eloquent Referral model
- Laravel Events for referral events
- Laravel Jobs for bonus processing
- Laravel Notifications for referral communications
- Laravel Validation for referral validation
- Laravel Cache for referral code caching

### Domain Entities
- ReferralCode (Value Object)
- UserId (Value Object)
- Referral (Eloquent Model)

### API Endpoints
- `GET /api/v1/users/me/referrals` - Get user referrals
- `POST /api/v1/users/me/referrals/nudge` - Get nudge options for referee
- `POST /api/v1/referrals/process` - Process referral conversion

### Laravel Services
- ReferralService (referral logic and management)
- ReferralCodeService (code generation and validation)
- ReferralBonusService (bonus processing)

### Laravel Models
- Referral (Eloquent model for referral tracking)
- User (extended with referral relationships)

### Laravel Events
- ReferralInviteeSignedUp
- ReferralConverted
- ReferralBonusAwarded

### Laravel Jobs
- ProcessReferralConversion
- AwardReferralBonus
- NotifyReferrerOfConversion

### Laravel Notifications
- ReferralInvitationNotification
- ReferralConversionNotification
- ReferralBonusAwardedNotification

## Implementation Details

### Referral Model Structure
```php
// app/Models/Referral.php
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
```

### User Extension for Referrals
```php
// In User model
class User extends Authenticatable
{
    // ... existing code ...
    
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
        return $this->referral_code ?? $this->generateReferralCode();
    }
    
    protected function generateReferralCode()
    {
        // Generate unique referral code
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('referral_code', $code)->exists());
        
        $this->referral_code = $code;
        $this->save();
        
        return $code;
    }
}
```

### Referral Service Implementation
```php
// app/Services/ReferralService.php
class ReferralService
{
    protected $referralBonusService;
    protected $referralCodeService;
    
    public function __construct(
        ReferralBonusService $referralBonusService,
        ReferralCodeService $referralCodeService
    ) {
        $this->referralBonusService = $referralBonusService;
        $this->referralCodeService = $referralCodeService;
    }
    
    public function processSignUp(User $invitee, string $referralCode): bool
    {
        // Validate referral code
        if (!$this->referralCodeService->isValid($referralCode)) {
            return false;
        }
        
        // Find referrer
        $referrer = User::where('referral_code', $referralCode)->first();
        if (!$referrer) {
            return false;
        }
        
        // Create referral record
        Referral::create([
            'referrer_user_id' => $referrer->id,
            'invitee_user_id' => $invitee->id,
            'referral_code' => $referralCode,
            'status' => 'signed_up',
        ]);
        
        // Fire event
        event(new ReferralInviteeSignedUp($referrer, $invitee, $referralCode));
        
        return true;
    }
    
    public function processConversion(User $invitee): void
    {
        $referral = Referral::where('invitee_user_id', $invitee->id)
            ->where('status', 'signed_up')
            ->first();
            
        if (!$referral) {
            return;
        }
        
        // Update referral status
        $referral->update([
            'status' => 'converted',
            'converted_at' => now(),
        ]);
        
        // Award bonus to referrer
        $this->referralBonusService->awardBonus($referral->referrer, $invitee);
        
        // Fire event
        event(new ReferralConverted($referral->referrer, $invitee));
    }
    
    public function getReferralStats(User $user): array
    {
        $totalReferrals = $user->referrals()->count();
        $convertedReferrals = $user->referrals()->converted()->count();
        
        return [
            'total_referrals' => $totalReferrals,
            'converted_referrals' => $convertedReferrals,
            'conversion_rate' => $totalReferrals > 0 ? ($convertedReferrals / $totalReferrals) * 100 : 0,
        ];
    }
}
```

## Referral Workflow

### Referral Code Generation
1. New user registers with unique referral code
2. Code is generated using Laravel Str and stored in User model
3. Code uniqueness is verified before assignment
4. Code is cached for quick lookup

### Referral Tracking
1. Invitee signs up using referral code
2. Referral record is created with "signed_up" status
3. ReferralInviteeSignedUp event is fired
4. When invitee completes first scan, referral is converted
5. ReferralConverted event is fired
6. Referrer receives bonus points

### Bonus Awarding
```php
// app/Services/ReferralBonusService.php
class ReferralBonusService
{
    public function awardBonus(User $referrer, User $invitee): void
    {
        // Award points to referrer
        $points = config('cannarewards.referral_bonus_points', 500);
        
        // Dispatch job to award points
        AwardReferralBonus::dispatch($referrer->id, $points, "Referral bonus for {$invitee->email}");
        
        // Update referral record
        Referral::where('referrer_user_id', $referrer->id)
            ->where('invitee_user_id', $invitee->id)
            ->update(['bonus_points_awarded' => $points]);
            
        // Send notification
        $referrer->notify(new ReferralBonusAwardedNotification($points, $invitee));
        
        // Fire event
        event(new ReferralBonusAwarded($referrer, $invitee, $points));
    }
}
```

## Laravel-Native Features Utilized

### Events & Listeners
- Laravel Event system for referral lifecycle
- Event discovery for automatic listener registration
- Queued event listeners for performance
- Event broadcasting for real-time updates

### Jobs & Queues
- Laravel Jobs for background bonus processing
- Queue workers for async operations
- Failed job handling and retry logic
- Job chaining for complex workflows

### Notifications
- Laravel Notifications for user communications
- Multiple channels (email, SMS, database)
- Markdown notification templates
- Notification throttling

### Validation
- Laravel Form Requests for referral validation
- Custom validation rules for referral codes
- Automatic error response formatting

### Caching
- Laravel Cache facade for referral code lookups
- Cache tags for granular invalidation
- Automatic cache expiration and refresh

## Business Logic Implementation

### Referral Conversion Rules
- Only first scan by invitee counts as conversion
- Referrer receives bonus points upon conversion
- Conversion must happen within defined timeframe (if configured)
- Bonus points configurable via Laravel config

### Nudge System
```php
// app/Services/ReferralNudgeService.php
class ReferralNudgeService
{
    public function getNudgeOptions(User $user, string $refereeEmail): array
    {
        // Validate email
        if (!filter_var($refereeEmail, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Invalid email address'];
        }
        
        // Check if already referred
        $existingReferral = Referral::whereHas('invitee', function ($query) use ($refereeEmail) {
            $query->where('email', $refereeEmail);
        })->first();
        
        if ($existingReferral) {
            return ['error' => 'This person has already been referred'];
        }
        
        return [
            'can_nudge' => true,
            'message' => "Invite {$refereeEmail} to earn bonus points!",
            'referral_code' => $user->referral_code,
        ];
    }
}
```

## Data Migration Strategy

### From WordPress to Laravel
- Migrate user referral codes to referral_code column
- Migrate referral relationships to referrals table
- Convert referral meta to structured referral records
- Preserve referral bonus history
- Maintain referral status tracking

## Dependencies
- Laravel Framework
- Database (MySQL/PostgreSQL)
- Redis (for queues and caching)
- Eloquent ORM

## Definition of Done
- [ ] New users receive unique referral codes automatically generated
- [ ] Referral codes can be validated during registration
- [ ] Referral relationships are correctly established when invitee signs up
- [ ] First scans by referred users are detected as conversions
- [ ] Referrers receive appropriate bonuses for conversions
- [ ] Referral activity is properly tracked and logged via database records
- [ ] Referral notifications are correctly sent to users
- [ ] Referral events are correctly broadcast and processed by listeners
- [ ] Adequate test coverage using Laravel testing features (100% of referral functionality)
- [ ] Error handling for edge cases with Laravel exception handling
- [ ] Performance benchmarks met (referral processing < 100ms)
- [ ] Background processing via Laravel queues for bonus awarding
- [ ] Proper validation using Laravel Form Requests
- [ ] Cache efficiency for referral code lookups (hit ratio > 90%)
- [ ] Referral stats calculation provides accurate conversion metrics
- [ ] Nudge system correctly identifies valid invite opportunities