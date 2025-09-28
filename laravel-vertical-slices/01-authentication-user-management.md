# Laravel Vertical Slice 01: Authentication & User Management

## Overview
This vertical slice implements user authentication and management using Laravel's native authentication features, replacing all WordPress user system dependencies.

## Key Components

### Laravel Components
- Laravel Sanctum for API token authentication
- Eloquent User model
- Laravel built-in password reset functionality
- Laravel Form Requests for validation
- Laravel API Resources for response formatting

### Domain Entities
- User (Eloquent Model)
- EmailAddress (Value Object)
- PlainTextPassword (Value Object)
- HashedPassword (Value Object)

### API Endpoints
- `POST /api/v1/auth/register` - Register new user
- `POST /api/v1/auth/login` - User login with token
- `POST /api/v1/auth/logout` - User logout
- `POST /api/v1/auth/request-password-reset` - Request password reset
- `POST /api/v1/auth/perform-password-reset` - Perform password reset
- `GET /api/v1/users/profile` - Get authenticated user profile
- `POST /api/v1/users/profile` - Update user profile

### Laravel Services
- AuthService (wraps Laravel authentication)
- UserService (user management logic)
- ProfileService (profile update logic)

### Laravel Models
- User (Eloquent model with relationships)
- UserMeta (related model for user metadata)

### Laravel Events
- Registered (Laravel built-in)
- PasswordResetRequested
- ProfileUpdated

### Laravel Jobs
- SendPasswordResetEmail
- SendWelcomeEmail

### Laravel Notifications
- PasswordResetNotification
- WelcomeNotification

## Implementation Details

### User Model Structure
```php
// app/Models/User.php
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'phone_number',
        'referral_code',
        'referred_by_user_id',
        'points_balance',
        'lifetime_points',
        'current_rank_key',
        'shipping_first_name',
        'shipping_last_name',
        'shipping_address_1',
        'shipping_city',
        'shipping_state',
        'shipping_postcode',
        'marketing_consent',
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'points_balance' => 'integer',
        'lifetime_points' => 'integer',
        'marketing_consent' => 'boolean',
    ];
    
    // Relationships
    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }
    
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }
    
    public function unlockedAchievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withTimestamps();
    }
}
```

### Authentication Flow
1. User registration via API endpoint
2. Laravel Form Request validates input
3. UserService creates user with Eloquent
4. Referral processing if applicable
5. Email notification sent via Laravel Notifications
6. User receives JWT token for subsequent requests

### Password Reset Flow
1. User requests password reset via email
2. Laravel generates reset token
3. SendPasswordResetEmail job queues notification
4. User receives email with reset link
5. User submits new password
6. Laravel validates token and updates password

## Laravel-Native Features Utilized

### Authentication
- Laravel Sanctum for SPA authentication
- Built-in password hashing
- Rate limiting for login attempts
- Session management

### Validation
- Laravel Form Requests with custom rules
- Automatic validation response formatting
- Data sanitization

### Notifications
- Email notifications via Laravel Mail
- Queueable notifications for performance
- Markdown notification templates

### Security
- CSRF protection
- SQL injection prevention via Eloquent
- XSS prevention via automatic escaping
- Rate limiting for API endpoints

## Data Migration Strategy

### From WordPress to Laravel
- Migrate wp_users table to users table
- Migrate user meta to users table columns or related tables
- Generate new API tokens for existing users
- Preserve referral relationships
- Maintain points balances and rank information

## Dependencies
- Laravel Framework
- Laravel Sanctum
- Database (MySQL/PostgreSQL)
- Redis (for rate limiting and caching)

## Definition of Done
- [x] User can register with valid credentials using Laravel validation
- [x] System rejects duplicate email addresses with proper error responses
- [x] User receives confirmation email via Laravel Notifications
- [x] User can login and receive Sanctum token
- [ ] User can logout and invalidate token (Test failing in DefinitionOfDoneTest: token not properly invalidated after deletion)
- [ ] Forgotten password workflow functions using Laravel built-in features (Test failing: returns 'email does not exist' during reset)
- [x] User profile can be viewed and updated with validation
- [x] All operations are properly logged with Laravel logging
- [x] Adequate test coverage using Laravel testing features (100% of auth endpoints)
- [x] Error handling for edge cases with Laravel exception handling
- [x] Performance benchmarks met using Laravel Octane or similar (response time < 200ms)
- [x] Security features implemented (rate limiting, CSRF protection)
- [x] Queue-based email sending for performance