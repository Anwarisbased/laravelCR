## Event Tracking Plan for Customer.io Integration

**Document Version:** 1.0  
**Status:** Draft  
**Date:** October 8, 2025

### Overview
This document defines the complete event tracking plan for the CannaRewards platform's integration with Customer.io. It specifies all events sent to Customer.io, their schemas, and the contexts in which they are triggered.

### 1. Event Taxonomy

#### 1.1 Event Categories
The platform tracks events across these categories:
- **Product Interaction Events**: User engagement with products
- **User Behavior Events**: Core user actions in the app
- **Economic Events**: Point earning/spending activities
- **Social Events**: Referral and sharing activities
- **Goal Progress Events**: Wishlist and achievement progression
- **Session Events**: User session lifecycle

### 2. Core Event Definitions

#### 2.1 Product Scan Event (`product_scanned`)
**Trigger**: User successfully scans a QR code on product packaging  
**Context**: Primary user engagement mechanism

```json
{
  "name": "product_scanned",
  "identifiers": {
    "id": "user-123"
  },
  "data": {
    "product_snapshot": {
      "identity": {
        "product_id": 45,
        "sku": "BD-VAPE-1G",
        "product_name": "Blue Dream 1g Vape"
      },
      "economy": {
        "points_award": 400,
        "msrp": 45.00
      },
      "taxonomy": {
        "product_line": "Signature Series",
        "product_form": "Vape Cartridge",
        "strain_name": "Blue Dream",
        "strain_type": "Sativa",
        "tags": ["effect-energetic", "flavor-sweet", "new-release"]
      },
      "attributes": {
        "potency_thc_percent": 88.5,
        "potency_cbd_percent": 0.8,
        "dominant_terpene": "Myrcene"
      },
      "merchandising": {
        "is_featured": false,
        "is_new": true,
        "is_limited": false,
        "is_digital": false
      }
    },
    "user_snapshot": {
      "identity": {
        "user_id": 123,
        "email": "jane.doe@example.com",
        "first_name": "Jane",
        "is_guest": false,
        "created_at": "2024-05-21T10:00:00Z"
      },
      "economy": {
        "points_balance": 1850,
        "lifetime_points": 6100,
        "points_spent_total": 4250,
        "currency_name": "Buds"
      },
      "status": {
        "rank_key": "gold",
        "rank_name": "Gold",
        "rank_multiplier": 2.0,
        "status_name": "Status"
      },
      "engagement": {
        "total_scans": 12,
        "total_redemptions": 2,
        "total_achievements_unlocked": 5,
        "days_since_signup": 90,
        "days_since_last_session": 1,
        "days_since_last_scan": 5,
        "days_since_last_redemption": 25,
        "is_dormant": false,
        "is_power_user": true
      },
      "profile_data": {
        "phone_number": "+15551234567",
        "custom_strain_preference": "Sativa",
        "custom_consumption_method": "Vape"
      },
      "compliance_and_contact": {
        "is_age_verified": true,
        "age_verified_at": "2024-05-21T10:00:00Z",
        "has_marketing_consent": true,
        "marketing_consent_updated_at": "2024-05-21T10:00:00Z"
      },
      "referral_data": {
        "referral_code": "JANE1A2B",
        "referred_by_user_id": 456,
        "total_referrals_completed": 3
      }
    },
    "event_context": {
      "time": {
        "timestamp_utc": "2024-05-22T18:35:12Z",
        "timestamp_local": "2024-05-22T11:35:12-07:00",
        "day_of_week_local": "Wednesday",
        "hour_of_day_local": 11,
        "is_weekend": false
      },
      "device": {
        "device_type": "mobile",
        "os": "iOS",
        "browser": "Safari",
        "user_agent": "Mozilla/5.0..."
      },
      "location": {
        "ip_address": "216.3.128.12",
        "geo_city": "Los Angeles",
        "geo_region": "California",
        "geo_country": "USA"
      }
    }
  },
  "timestamp": "2024-05-22T18:35:12Z"
}
```

#### 2.2 Referral Initiated Event (`referral_initiated`)
**Trigger**: User generates or shares a referral link  
**Context**: Social engagement and user acquisition

```json
{
  "name": "referral_initiated",
  "identifiers": {
    "id": "user-123"
  },
  "data": {
    "referral_data": {
      "referring_user_id": 123,
      "referring_user_email": "jane.doe@example.com",
      "referral_code": "JANE1A2B",
      "referral_method": "share_link", // or "direct_email"
      "target_audience": "friends"
    },
    "user_snapshot": {
      // Full user snapshot as in product_scanned
    },
    "event_context": {
      // Full event_context as in product_scanned
    }
  },
  "timestamp": "2024-05-22T18:35:12Z"
}
```

#### 2.3 Referral Converted Event (`referral_converted`)
**Trigger**: Referred user completes their first scan  
**Context**: Successful referral completion

```json
{
  "name": "referral_converted",
  "identifiers": {
    "id": "user-456" // The NEW user who was referred
  },
  "data": {
    "referral_data": {
      "referrer_user_id": 123,
      "referring_user_email": "jane.doe@example.com",
      "referred_user_id": 456,
      "referral_code_used": "JANE1A2B",
      "points_awarded_to_referrer": 500
    },
    "user_snapshot": {
      // Full user snapshot for the NEW user (referred user)
    },
    "event_context": {
      // Full event_context as in product_scanned
    }
  },
  "timestamp": "2024-05-22T18:35:12Z"
}
```

#### 2.4 Wishlist Added Event (`wishlist_item_added`)
**Trigger**: User adds a product to their wishlist  
**Context**: Purchase intent and future engagement

```json
{
  "name": "wishlist_item_added",
  "identifiers": {
    "id": "user-123"
  },
  "data": {
    "product_snapshot": {
      // Full product_snapshot as in product_scanned
    },
    "user_snapshot": {
      // Full user snapshot as in product_scanned
    },
    "wishlist_data": {
      "wishlist_item_id": "wishlist-item-789",
      "points_needed": 4500,
      "referrals_needed": 2,
      "time_to_goal": "estimated_days"
    },
    "event_context": {
      // Full event_context as in product_scanned
    }
  },
  "timestamp": "2024-05-22T18:35:12Z"
}
```

#### 2.5 Achievement Unlocked Event (`achievement_unlocked`)
**Trigger**: User fulfills achievement criteria  
**Context**: Gamification and engagement milestone

```json
{
  "name": "achievement_unlocked",
  "identifiers": {
    "id": "user-123"
  },
  "data": {
    "achievement_data": {
      "achievement_id": "first_scan",
      "achievement_name": "First Scan",
      "achievement_description": "Complete your first product scan",
      "points_reward": 100,
      "badge_id": "first-scan-badge",
      "achievement_rarity": "common",
      "achievement_category": "milestone"
    },
    "user_snapshot": {
      // Full user snapshot as in product_scanned
    },
    "event_context": {
      // Full event_context as in product_scanned
    }
  },
  "timestamp": "2024-05-22T18:35:12Z"
}
```

#### 2.6 Reward Redeemed Event (`reward_redeemed`)
**Trigger**: User redeems points for a reward  
**Context**: Economic transaction and value exchange

```json
{
  "name": "reward_redeemed",
  "identifiers": {
    "id": "user-123"
  },
  "data": {
    "reward_snapshot": {
      "identity": {
        "reward_id": 789,
        "reward_name": "Premium T-Shirt",
        "reward_sku": "TSHIRT-001"
      },
      "economy": {
        "points_cost": 5000,
        "retail_value": 25.00
      },
      "merchandising": {
        "is_digital": false,
        "requires_shipping": true
      }
    },
    "transaction_data": {
      "points_spent": 5000,
      "order_id": "order-12345"
    },
    "user_snapshot": {
      // Full user snapshot as in product_scanned
    },
    "event_context": {
      // Full event_context as in product_scanned
    }
  },
  "timestamp": "2024-05-22T18:35:12Z"
}
```

#### 2.7 User Session Started Event (`user_session_started`)
**Trigger**: User logs into the application  
**Context**: Engagement and retention metrics

```json
{
  "name": "user_session_started",
  "identifiers": {
    "id": "user-123"
  },
  "data": {
    "session_data": {
      "session_id": "session-abc123",
      "login_method": "email",
      "returning_user": true
    },
    "user_snapshot": {
      // Full user snapshot as in product_scanned
    },
    "event_context": {
      // Full event_context as in product_scanned
    }
  },
  "timestamp": "2024-05-22T18:35:12Z"
}
```

#### 2.8 User Session Ended Event (`user_session_ended`)
**Trigger**: User logs out or session expires  
**Context**: Session duration and engagement metrics

```json
{
  "name": "user_session_ended",
  "identifiers": {
    "id": "user-123"
  },
  "data": {
    "session_data": {
      "session_id": "session-abc123",
      "duration_seconds": 1200,
      "pages_viewed": 5,
      "actions_performed": 3
    },
    "user_snapshot": {
      // Full user snapshot as in product_scanned
    },
    "event_context": {
      // Full event_context as in product_scanned
    }
  },
  "timestamp": "2024-05-22T18:55:12Z"
}
```

### 3. Tracking Implementation Guidelines

#### 3.1 Data Consistency
- All events must include a complete `user_snapshot` to provide context
- Use standardized `product_snapshot` format across all product-related events
- Include `event_context` with time, device, and location data for all events

#### 3.2 Event Naming Conventions
- Use past tense: `product_scanned` not `product_scan`
- Use underscores for multi-word names: `user_session_started`
- Be specific but concise: `wishlist_item_added` not `item_added_to_wishlist`

#### 3.3 Sensitive Data Handling
- Do not send PII in event names or keys
- Sanitize all data before sending to Customer.io
- Hash email addresses if needed for identification

### 4. Customer.io Segmentation Opportunities

#### 4.1 Behavioral Segments
- High-value scannners: Users with high MSRP scan values
- Inactive users: Users with high days_since_last_session
- Referral enthusiasts: Users with high referral count

#### 4.2 Predictive Segments
- Churn risk: Based on engagement metrics and Customer.io ML predictions
- Purchase intent: Based on wishlist activity and scan patterns
- Lifetime value: Based on economic metrics and engagement patterns