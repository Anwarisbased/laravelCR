## Database Schema Design

**Document Version:** 1.0  
**Status:** Draft  
**Date:** October 8, 2025

### Overview
This document specifies the database schema for the CannaRewards Synergy Engine platform. It includes tables for user management, achievements, events, Customer.io integration data, and other core functionality supporting the bidirectional data flow with Customer.io.

### 1. Core Entity Tables

#### 1.1 Users Table
```sql
CREATE TABLE users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) UNIQUE NOT NULL,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  phone_number VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL, -- Use Laravel's hashing
  age_verified_at TIMESTAMP NULL,
  marketing_consent BOOLEAN DEFAULT FALSE,
  referral_code VARCHAR(50) UNIQUE,
  referred_by_user_id BIGINT UNSIGNED NULL,
  points_balance INT UNSIGNED DEFAULT 0,
  lifetime_points INT UNSIGNED DEFAULT 0,
  current_rank_key VARCHAR(50),
  is_guest BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (referred_by_user_id) REFERENCES users(id),
  INDEX idx_email (email),
  INDEX idx_referral_code (referral_code),
  INDEX idx_rank_key (current_rank_key)
);
```

#### 1.2 Brands Table
```sql
CREATE TABLE brands (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE NOT NULL,
  customer_io_site_id VARCHAR(100),
  customer_io_api_key VARCHAR(255), -- Encrypted
  settings JSON, -- Brand-specific settings
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_slug (slug)
);
```

### 2. Product & Economy Tables

#### 2.1 Products Table
```sql
CREATE TABLE products (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  brand_id BIGINT UNSIGNED NOT NULL,
  sku VARCHAR(100) NOT NULL,
  name VARCHAR(255) NOT NULL,
  msrp DECIMAL(10,2) NOT NULL, -- Manufacturer's Suggested Retail Price
  points_award INT NOT NULL, -- Points awarded for scanning
  product_line VARCHAR(100),
  product_form VARCHAR(100), -- e.g., "Vape Cartridge", "Flower", "Concentrate"
  strain_name VARCHAR(100),
  strain_type ENUM('Sativa', 'Indica', 'Hybrid'),
  potency_thc_percent DECIMAL(5,2),
  potency_cbd_percent DECIMAL(5,2),
  dominant_terpene VARCHAR(100),
  tags JSON, -- Array of tags like ["effect-energetic", "flavor-sweet"]
  is_featured BOOLEAN DEFAULT FALSE,
  is_new BOOLEAN DEFAULT FALSE,
  is_limited BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (brand_id) REFERENCES brands(id),
  UNIQUE KEY unique_sku_brand (sku, brand_id),
  INDEX idx_brand_id (brand_id),
  INDEX idx_sku (sku)
);
```

#### 2.2 Ranks Table
```sql
CREATE TABLE ranks (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  brand_id BIGINT UNSIGNED NOT NULL,
  key VARCHAR(50) NOT NULL,
  name VARCHAR(100) NOT NULL,
  points_required INT UNSIGNED NOT NULL,
  point_multiplier DECIMAL(3,2) DEFAULT 1.00,
  benefits TEXT,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (brand_id) REFERENCES brands(id),
  UNIQUE KEY unique_key_brand (key, brand_id),
  INDEX idx_brand_id (brand_id)
);
```

### 3. Achievement Engine Tables

#### 3.1 Achievement Configurations Table
```sql
CREATE TABLE achievement_configs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  brand_id BIGINT UNSIGNED NULL, -- NULL for global achievements
  name VARCHAR(255) NOT NULL,
  description TEXT,
  achievement_key VARCHAR(100) NOT NULL,
  achievement_type ENUM('scan_count', 'purchase_amount', 'referral_count', 'wishlist_items', 'achievement_unlocked', 'custom') NOT NULL,
  criteria JSON NOT NULL, -- Contains metric, operator, value, timeframe
  rewards JSON NOT NULL, -- Contains points, multiplier, badge, exclusive_access, etc.
  visibility JSON, -- Contains active, show_progress, priority settings
  ai_enhancement JSON, -- Contains personalization settings based on Customer.io
  metadata JSON, -- Contains category, rarity, tags
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  UNIQUE KEY unique_key_brand (achievement_key, brand_id),
  INDEX idx_brand_id (brand_id),
  INDEX idx_active (is_active)
);
```

#### 3.2 User Achievements Table
```sql
CREATE TABLE user_achievements (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  achievement_config_id BIGINT UNSIGNED NOT NULL,
  progress INT UNSIGNED DEFAULT 0,
  completed BOOLEAN DEFAULT FALSE,
  completed_at TIMESTAMP NULL,
  points_rewarded INT UNSIGNED DEFAULT 0, -- Points actually awarded
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (achievement_config_id) REFERENCES achievement_configs(id),
  UNIQUE KEY unique_user_achievement (user_id, achievement_config_id),
  INDEX idx_user_id (user_id),
  INDEX idx_completed (completed)
);
```

### 4. Event & Interaction Tables

#### 4.1 User Action Log Table
```sql
CREATE TABLE user_action_log (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  action_type ENUM('product_scanned', 'referral_initiated', 'referral_converted', 'wishlist_added', 'achievement_unlocked', 'reward_redeemed', 'user_session_started', 'user_session_ended') NOT NULL,
  action_data JSON, -- Contains specific data for each action type
  product_id BIGINT UNSIGNED NULL,
  points_awarded INT DEFAULT 0,
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id),
  INDEX idx_user_action (user_id, action_type),
  INDEX idx_created_at (created_at),
  INDEX idx_product_id (product_id)
);
```

#### 4.2 Customer.io Events Queue Table
```sql
CREATE TABLE customer_io_event_queue (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  event_name VARCHAR(255) NOT NULL,
  event_data JSON NOT NULL, -- Contains user_snapshot, product_snapshot, event_context
  status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
  priority INT DEFAULT 10, -- Lower number is higher priority
  attempt_count INT DEFAULT 0,
  last_attempt_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  processed_at TIMESTAMP NULL,
  
  FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_status_priority (status, priority, created_at),
  INDEX idx_user_id (user_id)
);
```

### 5. Customer.io Integration Tables

#### 5.1 AI Profiles Table
```sql
CREATE TABLE user_ai_profiles (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  churn_probability DECIMAL(5,4) DEFAULT 0.0000, -- 0.0000 to 1.0000
  predicted_lifetime_value DECIMAL(10,2) DEFAULT 0.00,
  engagement_score DECIMAL(5,4) DEFAULT 0.0000, -- 0.0000 to 1.0000
  product_affinity_scores JSON, -- e.g., {"concentrates": 0.85, "vapes": 0.62}
  purchase_probability DECIMAL(5,4) DEFAULT 0.0000,
  recommended_segment VARCHAR(100),
  next_best_action VARCHAR(100),
  ai_insights JSON, -- Additional insights from Customer.io
  calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_engagement_score (engagement_score),
  INDEX idx_churn_probability (churn_probability),
  INDEX idx_segment (recommended_segment)
);
```

#### 5.2 Webhook Log Table
```sql
CREATE TABLE customer_io_webhook_log (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  webhook_type VARCHAR(100) NOT NULL, -- 'prediction_update', 'segment_change', etc.
  user_id BIGINT UNSIGNED,
  payload JSON NOT NULL, -- Raw payload from Customer.io
  processed_successfully BOOLEAN,
  error_message TEXT,
  processed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_user_id (user_id),
  INDEX idx_webhook_type (webhook_type),
  INDEX idx_created_at (created_at)
);
```

### 6. Wishlist & Goal Tracking Tables

#### 6.1 Wishlists Table
```sql
CREATE TABLE wishlists (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_product (user_id, product_id),
  INDEX idx_user_id (user_id)
);
```

#### 6.2 User Goals Table
```sql
CREATE TABLE user_goals (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED,
  goal_type ENUM('wishlist_item', 'achievement', 'custom') NOT NULL,
  target_points INT UNSIGNED,
  current_points INT UNSIGNED DEFAULT 0,
  goal_name VARCHAR(255),
  is_active BOOLEAN DEFAULT TRUE,
  started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP NULL,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id),
  INDEX idx_user_active (user_id, is_active),
  INDEX idx_completed (completed_at)
);
```

### 7. Referral System Tables

#### 7.1 Referral Codes Table
```sql
CREATE TABLE referral_codes (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(50) UNIQUE NOT NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_code (code),
  INDEX idx_user_id (user_id)
);
```

#### 7.2 Referral Tracking Table
```sql
CREATE TABLE referrals (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  referrer_user_id BIGINT UNSIGNED NOT NULL, -- The user who referred
  referee_user_id BIGINT UNSIGNED NOT NULL, -- The user who was referred
  referral_code VARCHAR(50) NOT NULL,
  email VARCHAR(255), -- Referee's email
  status ENUM('pending', 'converted', 'expired') DEFAULT 'pending',
  converted_at TIMESTAMP NULL,
  points_awarded_to_referrer INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (referrer_user_id) REFERENCES users(id),
  FOREIGN KEY (referee_user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_referrer_status (referrer_user_id, status),
  INDEX idx_code_email (referral_code, email),
  INDEX idx_converted_at (converted_at)
);
```

### 8. Configuration Tables

#### 8.1 Custom Fields Table
```sql
CREATE TABLE custom_fields (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  brand_id BIGINT UNSIGNED NOT NULL,
  field_key VARCHAR(100) NOT NULL,
  field_label VARCHAR(255) NOT NULL,
  field_type ENUM('text', 'select', 'checkbox', 'radio') NOT NULL,
  field_options JSON, -- For select/radio fields
  is_required BOOLEAN DEFAULT FALSE,
  field_order INT DEFAULT 0,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (brand_id) REFERENCES brands(id),
  UNIQUE KEY unique_key_brand (field_key, brand_id),
  INDEX idx_brand_id (brand_id)
);
```

#### 8.2 User Custom Field Values Table
```sql
CREATE TABLE user_custom_field_values (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  custom_field_id BIGINT UNSIGNED NOT NULL,
  field_value TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (custom_field_id) REFERENCES custom_fields(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_field (user_id, custom_field_id),
  INDEX idx_user_id (user_id)
);
```

### 9. Performance & Optimization Considerations

#### 9.1 Indexing Strategy
- All foreign key columns should have corresponding indexes
- Frequently queried columns should be indexed (user_id, created_at, status)
- Composite indexes for multi-column queries
- Consider partial indexes for boolean flags when appropriate

#### 9.2 Partitioning Strategy
- Partition large tables like `user_action_log` by date (monthly)
- Partition `customer_io_event_queue` by status and date
- Consider sharding by brand_id for multi-tenant scaling

#### 9.3 Data Archiving
- Archive old event logs older than 1 year
- Archive webhook logs older than 6 months
- Maintain foreign key constraints during archiving operations