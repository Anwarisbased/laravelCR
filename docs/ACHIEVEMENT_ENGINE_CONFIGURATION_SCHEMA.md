## Achievement Engine Configuration Schema

**Document Version:** 1.0  
**Status:** Draft  
**Date:** October 8, 2025

### Overview
This document specifies the configuration schema for the Synergy Engine's Achievement Engine, allowing brands to customize achievement rules without code changes. The system enables brand-specific achievement criteria while leveraging Customer.io's AI insights for personalization.

### 1. Configuration Structure

#### 1.1 Achievement Configuration Schema
Achievements are configured using a standardized JSON schema that defines rules, rewards, and conditions:

```json
{
  "achievement_id": "string (unique identifier)",
  "name": "string (human-readable name)",
  "description": "string (detailed description)",
  "type": "enum (scan_count, purchase_amount, referral_count, wishlist_items, achievement_unlocked, custom)",
  "criteria": {
    "metric": "string (the metric to track: 'scans', 'points_earned', 'referrals', etc.)",
    "operator": "enum (equals, greater_than, less_than, between, in, not_in)",
    "value": "mixed (the target value for the condition)",
    "timeframe": "string (optional, e.g., '7d', '30d', 'all_time')"
  },
  "rewards": {
    "points": "integer (points reward)",
    "multiplier": "number (point multiplier bonus)",
    "badge": "string (badge identifier)",
    "exclusive_access": "boolean (grants access to special features)",
    "custom_reward": "string (identifier for custom rewards)"
  },
  "visibility": {
    "active": "boolean (whether achievement is active)",
    "show_progress": "boolean (show progress bar to user)",
    "brand_restricted": "string or array (optional brand ID or IDs this applies to)"
  },
  "ai_enhancement": {
    "personalized": "boolean (use Customer.io AI for personalization)",
    "priority": "integer (0-100, higher is more important)",
    "trigger_conditions": {
      "churn_risk_multiplier": "number (boost priority if user has high churn risk)",
      "engagement_score_threshold": "number (only show if engagement score is above threshold)"
    }
  },
  "metadata": {
    "category": "string (grouping category)",
    "rarity": "enum (common, uncommon, rare, epic, legendary)",
    "tags": "array (for filtering and searching)",
    "created_at": "timestamp",
    "updated_at": "timestamp"
  }
}
```

#### 1.2 Configuration Storage
- **Primary Storage**: Database table `achievement_configs` with JSON field for criteria
- **Cache Layer**: Redis cache to reduce database queries
- **Brand Override**: Allow brand-specific overrides of global achievements

### 2. Achievement Types

#### 2.1 Scan-Based Achievements
```json
{
  "achievement_id": "first_scan",
  "name": "First Scan",
  "description": "Complete your first product scan",
  "type": "scan_count",
  "criteria": {
    "metric": "scans",
    "operator": "greater_than",
    "value": 0
  },
  "rewards": {
    "points": 100,
    "badge": "first-scan-badge"
  },
  "visibility": {
    "active": true,
    "brand_restricted": "brand-456"
  }
}
```

#### 2.2 Product-Specific Achievements
```json
{
  "achievement_id": "concentrate_explorer",
  "name": "Concentrate Explorer",
  "description": "Scan 5 concentrate products",
  "type": "scan_count",
  "criteria": {
    "metric": "scans",
    "operator": "greater_than",
    "value": 4,
    "product_filters": {
      "product_form": "concentrate",
      "strain_type": ["Sativa", "Indica"]
    }
  },
  "rewards": {
    "points": 500,
    "exclusive_access": true
  },
  "ai_enhancement": {
    "priority": 85,
    "trigger_conditions": {
      "product_affinity_threshold": 0.7
    }
  }
}
```

#### 2.3 Referral-Based Achievements
```json
{
  "achievement_id": "social_butterfly",
  "name": "Social Butterfly",
  "description": "Successfully refer 3 new users who complete a scan",
  "type": "referral_count",
  "criteria": {
    "metric": "referrals_completed",
    "operator": "greater_than",
    "value": 2
  },
  "rewards": {
    "points": 1500,
    "multiplier": 1.5
  },
  "ai_enhancement": {
    "personalized": true,
    "priority": 90,
    "trigger_conditions": {
      "engagement_score_threshold": 0.6
    }
  }
}
```

#### 2.4 Wishlist-Based Achievements
```json
{
  "achievement_id": "wishlist_wizard",
  "name": "Wishlist Wizard",
  "description": "Add 10 items to your wishlist",
  "type": "wishlist_items",
  "criteria": {
    "metric": "wishlist_items_count",
    "operator": "greater_than",
    "value": 9
  },
  "rewards": {
    "points": 200,
    "custom_reward": "early_access_new_products"
  },
  "ai_enhancement": {
    "personalized": true,
    "priority": 70,
    "trigger_conditions": {
      "purchase_probability": 0.7
    }
  }
}
```

### 3. Brand Customization Interface

#### 3.1 Admin Configuration Options
The system provides a Filament-based admin interface for brand administrators:

- **Global Templates**: Predefined achievement templates that brands can customize
- **Brand-Specific Rules**: Achievements exclusive to specific brands
- **Reward Customization**: Adjust rewards based on brand preferences
- **Activation Controls**: Enable/disable achievements per brand
- **AI Parameter Tuning**: Adjust how Customer.io insights influence achievement presentation

#### 3.2 Configuration Validation
- JSON Schema validation to ensure configuration integrity
- Pre-flight validation for achievement criteria
- Automated testing of achievement conditions against sample data

### 4. AI Integration for Personalization

#### 4.1 Customer.io Data Integration
The achievement engine consumes these Customer.io predictions:
- Churn probability scores
- Product affinity scores
- Engagement scores
- Predicted lifetime value
- Recommended segments

#### 4.2 Personalized Achievement Presentation
Achievements are sorted and presented based on:
- User's AI-derived product preferences
- Churn risk (prioritize retention-focused achievements)
- Engagement level (match challenge appropriately)
- Purchase intent (promote relevant product categories)

### 5. Implementation Requirements

#### 5.1 Configuration Management
- Support for versioned achievement configurations
- Rollback capability for configuration changes
- Configuration import/export for multi-brand deployments
- Caching of frequently accessed configurations

#### 5.2 Performance Requirements
- Achievement evaluation must be fast (<50ms)
- Cache configurations with 5-minute expiration
- Batch processing for complex achievement calculations
- Asynchronous updates for non-critical achievements

#### 5.3 Data Persistence
- Store achievement progress for each user in `user_achievements` table
- Maintain achievement history and unlocking timestamps
- Track achievement-related events for analysis