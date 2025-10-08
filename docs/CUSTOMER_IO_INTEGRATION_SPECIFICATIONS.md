## Customer.io Integration Specifications

**Document Version:** 1.0  
**Status:** Draft  
**Date:** October 8, 2025

### Overview
This document specifies the integration between the CannaRewards platform and Customer.io for bidirectional data flow, AI-powered insights, and personalized marketing automation.

### 1. Integration Architecture

#### 1.1 Event-Driven Data Flow
- **CannaRewards → Customer.io**: Behavioral events sent via HTTP requests to Customer.io API
- **Customer.io → CannaRewards**: Predictions and insights delivered via webhooks to designated endpoints

#### 1.2 Primary Systems
- **Producer**: CannaRewards Laravel API (event source)
- **Processor**: Customer.io (AI/ML analytics)
- **Consumer**: CannaRewards application (insights utilization)

### 2. Data Synchronization

#### 2.1 Behavioral Events Sent to Customer.io
The following events will be sent in real-time when user actions occur:

**2.1.1 Product Scan Event (`product_scanned`)**  
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
        "product_form": "Vape Cartridge",
        "strain_type": "Sativa"
      }
    },
    "user_snapshot": {
      "identity": {
        "user_id": 123,
        "email": "jane.doe@example.com"
      },
      "economy": {
        "points_balance": 1850,
        "lifetime_points": 6100
      },
      "status": {
        "rank_key": "gold",
        "rank_name": "Gold"
      },
      "profile_data": {
        "custom_strain_preference": "Sativa"
      }
    }
  },
  "timestamp": "2024-05-22T18:35:12Z"
}
```

**2.1.2 Referral Events (`referral_initiated`, `referral_converted`)**  
```json
{
  "name": "referral_initiated",
  "identifiers": {
    "id": "user-123"
  },
  "data": {
    "referral_data": {
      "referring_user_id": 123,
      "referral_code": "JANE1A2B",
      "referral_email": "friend@example.com"
    }
  },
  "timestamp": "2024-05-22T18:35:12Z"
}
```

**2.1.3 Wishlist Events (`wishlist_added`, `wishlist_removed`)**  
```json
{
  "name": "wishlist_added", 
  "identifiers": {
    "id": "user-123"
  },
  "data": {
    "product_snapshot": {
      "identity": {
        "product_id": 45,
        "sku": "BD-VAPE-1G",
        "product_name": "Blue Dream 1g Vape"
      }
    },
    "user_snapshot": {
      "identity": {
        "user_id": 123,
        "email": "jane.doe@example.com"
      }
    }
  },
  "timestamp": "2024-05-22T18:35:12Z"
}
```

**2.1.4 Achievement Events (`achievement_unlocked`)**  
```json
{
  "name": "achievement_unlocked",
  "identifiers": {
    "id": "user-123"
  },
  "data": {
    "achievement_key": "first_scan",
    "points_reward": 100,
    "achievement_rarity": "common",
    "user_snapshot": {
      "identity": {
        "user_id": 123,
        "email": "jane.doe@example.com"
      }
    }
  },
  "timestamp": "2024-05-22T18:35:12Z"
}
```

**2.1.5 Reward Redemption Events (`reward_redeemed`)**  
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
        "reward_name": "Premium T-Shirt"
      },
      "economy": {
        "points_cost": 5000
      }
    },
    "points_spent": 5000,
    "user_snapshot": {
      "identity": {
        "user_id": 123,
        "email": "jane.doe@example.com"
      }
    }
  },
  "timestamp": "2024-05-22T18:35:12Z"
}
```

**2.1.6 User Session Events (`user_session_started`, `user_session_ended`)**  
```json
{
  "name": "user_session_started",
  "identifiers": {
    "id": "user-123"
  },
  "data": {
    "session_details": {
      "device_type": "mobile",
      "browser": "Safari",
      "location": {
        "city": "Los Angeles",
        "region": "California",
        "country": "USA"
      }
    },
    "user_snapshot": {
      "identity": {
        "user_id": 123,
        "email": "jane.doe@example.com"
      }
    }
  },
  "timestamp": "2024-05-22T18:35:12Z"
}
```

#### 2.2 Customer.io Insights Received by CannaRewards
Customer.io will send the following predictions via webhooks to `/webhooks/customer-io`:

**2.2.1 Churn Risk Prediction**
```json
POST /webhooks/customer-io
{
  "user_id": 123,
  "predictions": {
    "churn_probability": 0.78,
    "engagement_score": 0.65,
    "recommended_segment": "high_risk",
    "predicted_ltv": 500.25,
    "next_best_action": "send_retention_offer"
  }
}
```

**2.2.2 Product Affinity Predictions**
```json
POST /webhooks/customer-io
{
  "user_id": 123,
  "predictions": {
    "product_affinity_scores": {
      "concentrates": 0.85,
      "vapes": 0.62,
      "flower": 0.41
    },
    "recommended_segment": "concentrate_lover",
    "next_best_action": "feature_concentrate_products"
  }
}
```

**2.2.3 Purchase Intent Predictions**
```json
POST /webhooks/customer-io
{
  "user_id": 123,
  "predictions": {
    "purchase_probability": 0.82,
    "predicted_purchase_value": 85.50,
    "recommended_segment": "high_intent_buyer",
    "next_best_action": "send_personalized_offer"
  }
}
```

### 3. API Configuration

#### 3.1 Customer.io API Credentials
- **Site ID**: Provided by Customer.io
- **API Key**: Securely stored in environment variables
- **Tracking URL**: `https://track.customer.io/api/v1`

#### 3.2 Webhook Configuration
- **Endpoint**: `https://your-domain.com/api/webhooks/customer-io`
- **Authentication**: HMAC verification using Customer.io's webhook signing key
- **Retry Policy**: Customer.io handles retries (3-5 attempts over 24 hours)

### 4. Implementation Requirements

#### 4.1 Event Sending Requirements
- Events must be sent synchronously for critical user interactions
- Events should be queued and retried in case of API failures
- Rate limiting should be implemented to avoid hitting Customer.io's API limits
- Events must include complete user and product snapshots as defined above

#### 4.2 Webhook Processing Requirements
- Webhook endpoint must validate signatures from Customer.io
- Received predictions must be stored in the `ai_features` JSON column of the `users` table
- System must handle webhook failures gracefully (log and potentially retry)
- Predictions should be cached to minimize database lookups

### 5. Error Handling and Monitoring

#### 5.1 Retry Logic
- Failed API calls to Customer.io should be retried with exponential backoff
- Webhook processing failures should be logged and potentially queued for later retry

#### 5.2 Monitoring
- Track successful/failed event deliveries to Customer.io
- Monitor webhook processing times and success rates
- Alert on any failures in the bidirectional data flow

### 6. Security Considerations
- All data transmission to Customer.io must use HTTPS
- API keys and webhook signing keys must be stored securely
- User data sent to Customer.io must comply with privacy regulations
- Customer.io's security practices must be validated for PII handling