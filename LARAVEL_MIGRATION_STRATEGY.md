# Laravel Migration Strategy

## Overview
This document outlines the comprehensive strategy for migrating the CannaRewards system from the current WordPress/WooCommerce implementation to a new Laravel-based architecture, following the vertical slice approach defined in the laravel-vertical-slices directory.

## Migration Approach

### Blue-Green Deployment Strategy
We will employ a blue-green deployment approach to minimize downtime and risk during the migration:

1. **Blue Environment**: Current WordPress/WooCommerce implementation
2. **Green Environment**: New Laravel implementation
3. **Traffic Switch**: Gradual traffic shifting from blue to green
4. **Rollback Plan**: Immediate rollback capability to blue environment

### Phased Migration
The migration will occur in phases to ensure stability and minimize business disruption:

1. **Phase 1**: Dual Running (Weeks 1-4)
   - Both systems operate in parallel
   - New user registrations directed to Laravel
   - Existing users continue on WordPress
   - Data synchronization between systems

2. **Phase 2**: Gradual Transition (Weeks 5-8)
   - Existing users migrated in batches
   - Feature parity validation
   - Performance monitoring and optimization

3. **Phase 3**: Full Cutover (Weeks 9-10)
   - Complete migration to Laravel
   - Decommissioning of WordPress components
   - Final validation and optimization

## Data Migration

### Migration Principles
1. **Data Integrity**: Zero data loss during migration
2. **Atomic Operations**: All-or-nothing migration for individual entities
3. **Validation**: Pre and post-migration data validation
4. **Rollback Capability**: Ability to revert migrated data if needed

### Data Entities to Migrate

#### Users (High Priority)
- User accounts and metadata
- Points balances and lifetime points
- Rank information
- Referral codes and relationships
- Shipping addresses
- Custom field values
- Action logs (scan history, redemptions)

#### Products (Medium Priority)
- Product definitions
- Point awards and costs
- Rank requirements
- Metadata and attributes

#### Configuration (High Priority)
- Rank definitions
- Achievement definitions
- Custom field definitions
- Trigger definitions
- Brand settings

#### Historical Data (Medium Priority)
- Order history
- Achievement unlocks
- Referral conversions
- System logs

### Migration Process

#### 1. Preparation Phase
- Create comprehensive data mapping document
- Develop migration scripts for each entity type
- Set up parallel databases (if needed)
- Create backup and rollback procedures
- Establish validation criteria

#### 2. Script Development
- Develop ETL (Extract, Transform, Load) scripts
- Implement data validation and reconciliation
- Create progress tracking and error handling
- Build rollback capabilities

#### 3. Testing Phase
- Test migration scripts with sample data
- Validate data integrity post-migration
- Performance test migration process
- Document any issues and resolutions

#### 4. Execution Phase
- Execute migration in controlled batches
- Monitor progress and performance
- Validate data at each checkpoint
- Handle errors and exceptions

## Technical Migration Steps

### 1. Environment Setup
- Provision new Laravel application servers
- Configure database clusters
- Set up caching layers (Redis)
- Configure load balancers
- Establish monitoring and alerting

### 2. Code Deployment
- Deploy Laravel application to staging environment
- Conduct comprehensive testing
- Deploy to production environment
- Configure blue-green routing

### 3. Data Migration Execution
- Execute user data migration in batches
- Migrate product and configuration data
- Validate migrated data
- Handle any discrepancies

### 4. Traffic Shifting
- Gradually shift traffic to new system
- Monitor performance and error rates
- Adjust traffic distribution based on metrics
- Complete full cutover when stable

## Integration Points

### API Compatibility
To ensure a smooth transition, the Laravel system will maintain API compatibility with the existing WordPress implementation:

1. **Endpoint Parity**: All existing API endpoints will be replicated
2. **Response Format**: JSON response formats will remain consistent
3. **Authentication**: JWT token compatibility will be maintained
4. **Error Handling**: Error response formats will be consistent

### Third-Party Integrations
All existing third-party integrations will be maintained:

1. **WooCommerce**: Product and order data access patterns will be preserved
2. **Customer.io**: Event tracking and user data synchronization will continue
3. **Payment Gateways**: Existing payment processing workflows will be maintained
4. **Analytics**: Data tracking and reporting will continue uninterrupted

## Risk Management

### Identified Risks

#### 1. Data Loss
- **Mitigation**: Comprehensive backups, validation scripts, atomic operations
- **Impact**: High
- **Probability**: Low

#### 2. Performance Degradation
- **Mitigation**: Performance testing, gradual rollout, monitoring
- **Impact**: Medium
- **Probability**: Medium

#### 3. User Experience Disruption
- **Mitigation**: Feature parity, user testing, gradual migration
- **Impact**: High
- **Probability**: Low

#### 4. Extended Downtime
- **Mitigation**: Blue-green deployment, rollback procedures
- **Impact**: High
- **Probability**: Low

### Contingency Plans

#### Immediate Rollback
If issues are detected with specific components:
1. Redirect traffic for affected components back to WordPress
2. Isolate and debug issues in Laravel components
3. Deploy fixes and re-attempt migration

#### Full Rollback
If critical issues affect the entire system:
1. Immediately redirect all traffic to WordPress
2. Assess and document all issues
3. Develop remediation plan
4. Schedule re-attempt of migration

## Testing Strategy

### Pre-Migration Testing
- Data migration scripts validation
- Performance benchmarking
- Security auditing
- User acceptance testing

### During Migration Testing
- Continuous data validation
- Real-time performance monitoring
- User experience monitoring
- Error rate tracking

### Post-Migration Testing
- Comprehensive functionality testing
- Data integrity verification
- Performance validation
- Security assessment

## Communication Plan

### Internal Communication
- Daily standups during active migration
- Weekly progress reports to stakeholders
- Immediate escalation procedures for critical issues
- Post-mortem analysis after completion

### External Communication
- Advance notice to users about maintenance windows
- Real-time status updates during migration
- Post-migration communication about improvements
- Support team preparation for potential user questions

## Success Criteria

### Technical Success Metrics
- Zero data loss during migration
- < 5 minute total downtime during cutover
- API response times maintained or improved
- System stability > 99.9% uptime
- Successful third-party integration continuity

### Business Success Metrics
- User satisfaction scores maintained or improved
- No significant increase in support tickets
- Successful completion within planned timeline
- Budget adherence
- Positive feedback from development team on new architecture

## Rollback Procedures

### Partial Rollback
If issues are detected with specific components:
1. Redirect traffic for affected components back to WordPress
2. Isolate and debug issues in Laravel components
3. Deploy fixes and re-attempt migration

### Full Rollback
If critical issues affect the entire system:
1. Immediately redirect all traffic to WordPress
2. Assess and document all issues
3. Develop remediation plan
4. Schedule re-attempt of migration

## Timeline and Milestones

### Week 1-2: Preparation
- Environment setup and configuration
- Data mapping and script development
- Backup and rollback procedure establishment
- Testing framework implementation

### Week 3-4: Testing and Validation
- Script testing with sample data
- Performance benchmarking
- Security auditing
- User acceptance testing preparation

### Week 5-6: Soft Launch
- Deploy to subset of users
- Monitor performance and user feedback
- Fine-tune system based on real-world usage
- Prepare for full migration

### Week 7-8: Full Migration
- Execute batch user migrations
- Monitor system stability
- Handle any migration issues
- Validate data integrity

### Week 9-10: Optimization and Completion
- Performance optimization based on real usage
- Decommission WordPress components
- Final validation and testing
- Documentation completion

## Data Migration Scripts

### User Migration Script
```php
// database/migrations/scripts/migrate-users.php
<?php

namespace Database\Migrations\Scripts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class MigrateUsers
{
    public function migrate()
    {
        // Get users from WordPress database
        $wpUsers = DB::connection('wordpress')->table('wp_users')->get();
        
        foreach ($wpUsers as $wpUser) {
            // Check if user already exists in Laravel database
            if (User::where('email', $wpUser->user_email)->exists()) {
                continue;
            }
            
            // Get user meta data
            $wpUserMeta = DB::connection('wordpress')
                ->table('wp_usermeta')
                ->where('user_id', $wpUser->ID)
                ->get()
                ->pluck('meta_value', 'meta_key')
                ->toArray();
            
            // Create user in Laravel database
            User::create([
                'first_name' => $wpUserMeta['first_name'] ?? '',
                'last_name' => $wpUserMeta['last_name'] ?? '',
                'email' => $wpUser->user_email,
                'password' => Hash::make($wpUser->user_pass),
                'points_balance' => (int) ($wpUserMeta['_canna_points_balance'] ?? 0),
                'lifetime_points' => (int) ($wpUserMeta['_canna_lifetime_points'] ?? 0),
                'current_rank_key' => $wpUserMeta['_canna_current_rank_key'] ?? 'member',
                'referral_code' => $wpUserMeta['_canna_referral_code'] ?? null,
                'marketing_consent' => (bool) ($wpUserMeta['marketing_consent'] ?? false),
                'shipping_first_name' => $wpUserMeta['shipping_first_name'] ?? '',
                'shipping_last_name' => $wpUserMeta['shipping_last_name'] ?? '',
                'shipping_address_1' => $wpUserMeta['shipping_address_1'] ?? '',
                'shipping_city' => $wpUserMeta['shipping_city'] ?? '',
                'shipping_state' => $wpUserMeta['shipping_state'] ?? '',
                'shipping_postcode' => $wpUserMeta['shipping_postcode'] ?? '',
                'created_at' => $wpUser->user_registered,
                'updated_at' => now(),
            ]);
        }
    }
    
    public function validate()
    {
        $wpUserCount = DB::connection('wordpress')->table('wp_users')->count();
        $laravelUserCount = User::count();
        
        return [
            'wp_users' => $wpUserCount,
            'laravel_users' => $laravelUserCount,
            'migration_complete' => $wpUserCount === $laravelUserCount,
        ];
    }
}
```

### Product Migration Script
```php
// database/migrations/scripts/migrate-products.php
<?php

namespace Database\Migrations\Scripts;

use Illuminate\Support\Facades\DB;
use App\Models\Product;

class MigrateProducts
{
    public function migrate()
    {
        // Get products from WooCommerce
        $wcProducts = DB::connection('wordpress')
            ->table('wp_posts')
            ->where('post_type', 'product')
            ->where('post_status', 'publish')
            ->get();
            
        foreach ($wcProducts as $wcProduct) {
            // Get product meta data
            $wcProductMeta = DB::connection('wordpress')
                ->table('wp_postmeta')
                ->where('post_id', $wcProduct->ID)
                ->get()
                ->pluck('meta_value', 'meta_key')
                ->toArray();
                
            // Create product in Laravel database
            Product::updateOrCreate(
                ['sku' => $wcProductMeta['_sku'] ?? ''],
                [
                    'name' => $wcProduct->post_title,
                    'description' => $wcProduct->post_content,
                    'short_description' => $wcProduct->post_excerpt,
                    'points_award' => (int) ($wcProductMeta['points_award'] ?? 0),
                    'points_cost' => (int) ($wcProductMeta['points_cost'] ?? 0),
                    'required_rank_key' => $wcProductMeta['_required_rank'] ?? null,
                    'is_active' => true,
                    'brand' => $wcProductMeta['brand'] ?? '',
                    'strain_type' => $wcProductMeta['strain_type'] ?? '',
                    'thc_content' => (float) ($wcProductMeta['thc_content'] ?? 0),
                    'cbd_content' => (float) ($wcProductMeta['cbd_content'] ?? 0),
                    'product_form' => $wcProductMeta['product_form'] ?? '',
                    'marketing_snippet' => $wcProductMeta['marketing_snippet'] ?? '',
                    'created_at' => $wcProduct->post_date,
                    'updated_at' => $wcProduct->post_modified,
                ]
            );
        }
    }
}
```

### Rank Migration Script
```php
// database/migrations/scripts/migrate-ranks.php
<?php

namespace Database\Migrations\Scripts;

use Illuminate\Support\Facades\DB;
use App\Models\Rank;

class MigrateRanks
{
    public function migrate()
    {
        // Get ranks from WordPress custom post type
        $wpRanks = DB::connection('wordpress')
            ->table('wp_posts')
            ->where('post_type', 'canna_rank')
            ->where('post_status', 'publish')
            ->get();
            
        foreach ($wpRanks as $wpRank) {
            // Get rank meta data
            $wpRankMeta = DB::connection('wordpress')
                ->table('wp_postmeta')
                ->where('post_id', $wpRank->ID)
                ->get()
                ->pluck('meta_value', 'meta_key')
                ->toArray();
                
            // Create rank in Laravel database
            Rank::updateOrCreate(
                ['key' => $wpRank->post_name],
                [
                    'name' => $wpRank->post_title,
                    'description' => $wpRank->post_content,
                    'points_required' => (int) ($wpRankMeta['points_required'] ?? 0),
                    'point_multiplier' => (float) ($wpRankMeta['point_multiplier'] ?? 1.0),
                    'benefits' => $wpRankMeta['benefits'] ?? '',
                    'is_active' => true,
                    'sort_order' => $wpRankMeta['menu_order'] ?? 0,
                    'created_at' => $wpRank->post_date,
                    'updated_at' => $wpRank->post_modified,
                ]
            );
        }
    }
}
```

## Migration Validation

### Data Integrity Checks
```php
// database/migrations/scripts/validate-migration.php
<?php

namespace Database\Migrations\Scripts;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use App\Models\Rank;

class ValidateMigration
{
    public function validateUsers()
    {
        $wpUserCount = DB::connection('wordpress')->table('wp_users')->count();
        $laravelUserCount = User::count();
        
        return [
            'wp_users' => $wpUserCount,
            'laravel_users' => $laravelUserCount,
            'match' => $wpUserCount === $laravelUserCount,
        ];
    }
    
    public function validateProducts()
    {
        $wpProductCount = DB::connection('wordpress')
            ->table('wp_posts')
            ->where('post_type', 'product')
            ->where('post_status', 'publish')
            ->count();
            
        $laravelProductCount = Product::count();
        
        return [
            'wp_products' => $wpProductCount,
            'laravel_products' => $laravelProductCount,
            'match' => $wpProductCount === $laravelProductCount,
        ];
    }
    
    public function validateRanks()
    {
        $wpRankCount = DB::connection('wordpress')
            ->table('wp_posts')
            ->where('post_type', 'canna_rank')
            ->where('post_status', 'publish')
            ->count();
            
        $laravelRankCount = Rank::count();
        
        return [
            'wp_ranks' => $wpRankCount,
            'laravel_ranks' => $laravelRankCount,
            'match' => $wpRankCount === $laravelRankCount,
        ];
    }
    
    public function fullValidation()
    {
        return [
            'users' => $this->validateUsers(),
            'products' => $this->validateProducts(),
            'ranks' => $this->validateRanks(),
        ];
    }
}
```

## Cutover Process

### Traffic Shifting Strategy
```php
// app/Services/TrafficShiftService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class TrafficShiftService
{
    public function shiftTraffic(float $percentage)
    {
        Redis::set('traffic_shift_percentage', $percentage);
    }
    
    public function getTrafficPercentage()
    {
        return (float) Redis::get('traffic_shift_percentage') ?? 0.0;
    }
    
    public function shouldRouteToLaravel()
    {
        $percentage = $this->getTrafficPercentage();
        return mt_rand(1, 100) <= ($percentage * 100);
    }
}
```

### Health Monitoring
```php
// app/Services/HealthMonitoringService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HealthMonitoringService
{
    public function checkLaravelHealth()
    {
        try {
            $response = Http::timeout(10)->get(config('app.url') . '/api/v1/health');
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Laravel health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function checkWordpressHealth()
    {
        try {
            $response = Http::timeout(10)->get(config('wordpress.old_url') . '/wp-json/');
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WordPress health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function shouldInitiateRollback()
    {
        $laravelHealthy = $this->checkLaravelHealth();
        $wordpressHealthy = $this->checkWordpressHealth();
        
        // If Laravel is unhealthy and WordPress is healthy, initiate rollback
        return !$laravelHealthy && $wordpressHealthy;
    }
}
```

## Post-Migration Activities

### WordPress Decommissioning
1. Backup all WordPress data and files
2. Disable WordPress cron jobs
3. Redirect WordPress URLs to Laravel equivalents
4. Monitor for broken links or missing content
5. Remove WordPress files after verification period

### Performance Optimization
1. Monitor Laravel application performance
2. Optimize database queries and indexing
3. Implement additional caching strategies
4. Scale infrastructure based on usage patterns
5. Implement performance monitoring and alerting

### Documentation Updates
1. Update all documentation to reflect new Laravel implementation
2. Create new onboarding documentation for developers
3. Update API documentation with new endpoints
4. Create operations documentation for system maintenance
5. Document rollback procedures and emergency contacts

## Monitoring and Alerting

### Infrastructure Monitoring
- Server health and resource utilization
- Database performance and query optimization
- Cache hit ratios and performance
- Queue processing and job completion rates
- Network connectivity and latency

### Application Monitoring
- API response times and error rates
- User session and authentication metrics
- Business logic performance and throughput
- Third-party integration status and performance
- Custom business metrics and KPIs

### Alerting Thresholds
- Critical alerts for system downtime or major errors
- Warning alerts for performance degradation
- Info alerts for routine maintenance and updates
- Escalation procedures for different severity levels
- Notification channels (email, SMS, Slack, etc.)

This migration strategy provides a comprehensive approach to transitioning from the WordPress/WooCommerce implementation to the new Laravel architecture while minimizing risk and ensuring business continuity.