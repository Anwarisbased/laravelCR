## Feature Specifications / User Stories

**Document Version:** 1.0  
**Status:** Draft  
**Date:** October 8, 2025

### Overview
This document specifies the feature requirements for the CannaRewards Synergy Engine platform using user stories and acceptance criteria. These specifications cover the core functionality needed to support the bidirectional data flow with Customer.io and the synergistic features.

### 1. User Account Management Features

#### 1.1 User Registration with On-Pack QR Code
**User Story:** As a consumer, I want to scan an on-pack QR code to register for the loyalty program and receive my first reward, so I can join the program with minimal friction.

**Acceptance Criteria:**
- When a user scans a QR code on product packaging, the PWA displays a welcome screen with a branded reward offer
- The system captures minimal required data (first name, last name, address, email) to create an account and fulfill shipping
- The system creates a user account in the database and generates a referral code
- The system creates a record for the free gift redemption
- The system sends a magic link email to the user for account activation
- The system records the initial scan event in the user_action_log table
- The system sends the scan event to Customer.io via the event queue

**Technical Specifications:**
- API Endpoint: `POST /api/unauthenticated/claim`
- Required fields: `qr_code`, `first_name`, `last_name`, `email`, `address`, `address2`, `city`, `state`, `zip`, `age_verification`
- Response: User account details with referral code and magic link status

#### 1.2 User Login
**User Story:** As a registered user, I want to log into the application securely so I can access my account and rewards.

**Acceptance Criteria:**
- User can log in with email and password
- System validates credentials against stored password hash
- System returns JWT token for subsequent API requests
- System updates last session information
- System sends user session started event to Customer.io

**Technical Specifications:**
- API Endpoint: `POST /api/auth/login`
- Request: `{email: string, password: string}`
- Response: `{success: boolean, data: {user: object, token: string}}`

### 2. Product Scanning Features

#### 2.1 Product Scan Processing
**User Story:** As a user, I want to scan product QR codes to earn points and unlock rewards, so I can engage with the brand and earn value from my purchases.

**Acceptance Criteria:**
- User can scan a valid QR code from the PWA
- System validates QR code exists and is not already used
- System calculates points based on product MSRP (10 points per $1)
- System applies rank multiplier to points earned
- System updates user's point balance
- System logs the scan event in user_action_log
- System sends scan event to Customer.io with complete product and user snapshots
- System checks for achievement completion based on updated scan count
- System updates any related user goals progress

**Technical Specifications:**
- API Endpoint: `POST /api/scans`
- Request: `{qr_code: string}`
- Response: `{success: boolean, data: {points_earned: number, new_balance: number, achievement_unlocked: object}}`

#### 2.2 Welcome Streak Mechanics
**User Story:** As a new user, I want to receive enhanced rewards for my first few scans to encourage continued engagement with the platform.

**Acceptance Criteria:**
- First scan: Base points + 1x physical product reward
- Second scan: 2x point multiplier applied to earned points
- Third scan: Achievement unlock + bonus points
- System tracks scan count for welcome streak eligibility
- System applies appropriate bonuses based on scan sequence
- System records all welcome streak events

### 3. Achievement Engine Features

#### 3.1 Achievement Configuration
**User Story:** As a brand administrator, I want to configure achievement rules for my brand without code changes, so I can customize engagement mechanics for my customers.

**Acceptance Criteria:**
- Admin can create, update, delete achievement configurations via Filament CMS
- Achievement rules can be configured with criteria, rewards, and visibility settings
- Achievement configurations can be brand-specific or global
- Achievement criteria can include scan counts, product types, referral activity, etc.
- Achievement configurations support AI enhancement based on Customer.io insights
- System validates configuration for completeness and correctness before saving

**Technical Specifications:**
- Database Table: `achievement_configs`
- Admin Interface: Filament resource with validation rules
- Configuration Schema: JSON with validation against defined schema

#### 3.2 Achievement Progress Tracking
**User Story:** As a user, I want to see my progress toward achievements so I can understand what actions will unlock rewards.

**Acceptance Criteria:**
- System tracks progress for each achievement a user is pursuing
- System updates progress when relevant actions occur
- System properly checks for achievement completion after each qualifying event
- System awards configured rewards upon achievement completion
- System records achievement unlock events for analytics
- System sends achievement unlocked event to Customer.io

**Technical Specifications:**
- Database Table: `user_achievements`
- Progress Calculation: Real-time based on user_action_log entries
- Achievement Checking: Event-driven via Laravel events

#### 3.3 Personalized Achievement Presentation
**User Story:** As a user, I want to see the most relevant achievements based on my behavior and preferences, so I can focus on the most valuable opportunities to earn rewards.

**Acceptance Criteria:**
- Achievements are prioritized based on Customer.io insights (product affinities, engagement level)
- High-priority achievements are shown more prominently to the user
- Achievement recommendations are updated when Customer.io sends new insights
- System adjusts achievement visibility based on churn risk predictions
- Personalized achievement list is cached for performance

**Technical Specifications:**
- Data Source: `user_ai_profiles` table
- Caching: Redis cache with 5-minute TTL
- Ranking Algorithm: Weighted combination of engagement score, product affinities, and churn risk

### 4. Customer.io Integration Features

#### 4.1 Event Sending to Customer.io
**User Story:** As a system, I want to send user behavioral events to Customer.io in real-time, so Customer.io can build accurate customer profiles and provide AI insights.

**Acceptance Criteria:**
- All significant user actions trigger appropriate event sends to Customer.io
- Events include complete user and product snapshots as defined in event tracking plan
- Events are queued for processing to prevent blocking user actions
- Failed event sends are retried with exponential backoff
- System handles Customer.io API rate limits gracefully
- All events include proper contextual information (device, location, time)

**Technical Specifications:**
- Queue System: Laravel queues with Redis
- Event Schema: JSON matching customer.io requirements
- Retry Logic: Exponential backoff up to 5 attempts

#### 4.2 Customer.io Webhook Processing
**User Story:** As a system, I want to receive and process insights from Customer.io, so I can customize the user experience based on AI predictions.

**Acceptance Criteria:**
- System receives webhook requests from Customer.io at configured endpoint
- Webhook signatures are validated using Customer.io's signing key
- Received insights are stored in user_ai_profiles table
- System updates user experience elements based on new insights
- Failed webhook processing is logged for troubleshooting
- System gracefully handles temporary unavailability of Customer.io services

**Technical Specifications:**
- Webhook Endpoint: `/api/webhooks/customer-io`
- Validation: HMAC signature verification
- Storage: `user_ai_profiles` table with JSON fields

#### 4.3 AI-Driven Personalization
**User Story:** As a user, I want to see a personalized experience based on AI insights, so I can receive content and offers most relevant to me.

**Acceptance Criteria:**
- PWA displays personalized content (cards, offers, achievements) based on Customer.io insights
- User experience adapts based on churn risk predictions
- Product recommendations reflect user affinities from Customer.io
- Next best actions are shown based on AI predictions
- System gracefully degrades when AI insights are temporarily unavailable
- Personalization updates in near real-time as insights change

**Technical Specifications:**
- API Endpoint: `GET /api/personalization`
- Data Source: `user_ai_profiles` table
- Caching: API responses cached with 2-minute TTL

### 5. Wishlist & Goal System Features

#### 5.1 Wishlist Management
**User Story:** As a user, I want to add items to a wishlist so I can track products I'm interested in and see how close I am to being able to afford them.

**Acceptance Criteria:**
- User can add/remove products to/from their wishlist
- System calculates points needed for wishlist items
- System shows progress toward wishlist goals
- User can see how many referrals would be needed to unlock wishlist items
- Wishlist additions trigger events sent to Customer.io
- System considers wishlist activity for product affinity predictions

**Technical Specifications:**
- Database Table: `wishlists`
- API Endpoints: `POST /api/wishlist`, `DELETE /api/wishlist/{product_id}`
- Integration: Triggers Customer.io events and goal tracking

#### 5.2 Goal Tracking
**User Story:** As a user, I want to set and track goals so I can have clear objectives to work toward in the loyalty program.

**Acceptance Criteria:**
- User can set an active goal (typically a wishlist item)
- System tracks progress toward the goal with a progress bar
- System shows alternative paths to reach goals (scans needed, referrals needed)
- System sends goal progress to Customer.io for analysis
- Achieving goals triggers celebrations and new goal suggestions

**Technical Specifications:**
- Database Table: `user_goals`
- Real-time updates via API polling or Server-Sent Events
- Integration with achievement and referral systems

### 6. Referral System Features

#### 6.1 Referral Generation
**User Story:** As a user, I want to share my referral code with friends so I can earn rewards for successful referrals.

**Acceptance Criteria:**
- Every user has a unique referral code
- User can access and share their referral code easily
- System tracks all referral link usage
- Referral events are sent to Customer.io for attribution
- System provides tools to help users share their referral code

**Technical Specifications:**
- Database Table: `referral_codes`
- API Endpoint: `GET /api/referrals/me`
- Integration: Event tracking for referral sharing

#### 6.2 Referral Conversion
**User Story:** As a user, I want to earn rewards when my referrals complete their first scan, so I'm incentivized to promote the program.

**Acceptance Criteria:**
- When a new user registers using a referral code, the relationship is recorded
- When the referred user completes their first scan, the referrer earns points
- Referral conversion triggers events sent to Customer.io
- System tracks and displays total successful referrals for each user
- Referral bonus points are calculated and awarded correctly

**Technical Specifications:**
- Database Table: `referrals`
- Event Trigger: When referred user completes first scan
- Points Award: Configurable via achievement system

### 7. Synergistic Feature Connections

#### 7.1 PWA Card System
**User Story:** As a user, I want to see contextual cards in the PWA that guide me toward valuable actions, so I can maximize my engagement and rewards.

**Acceptance Criteria:**
- System displays cards based on Customer.io insights and user behavior
- Cards might suggest: completing a wishlist goal, making referrals, scanning specific product types
- Cards are personalized based on AI predictions (churn risk, product affinities)
- System adjusts card content as user behavior and AI insights change
- Card interactions trigger appropriate events and tracking
- Cards implement appropriate business logic for synergistic features

**Technical Specifications:**
- API Endpoint: `GET /api/pwa-cards`
- Data Sources: Multiple (wishlist, achievements, referrals, ai_insights)
- Caching: Response cached with 1-minute TTL

#### 7.2 Next Best Action Engine
**User Story:** As a user, I want the system to suggest my next most valuable action, so I can maximize the value I get from the loyalty program.

**Acceptance Criteria:**
- System calculates next best action based on Customer.io insights
- Actions are ranked by predicted value to user and business
- Suggestions adapt based on user's current goals and progress
- System explains why each suggestion was made (transparency)
- User interactions with suggestions are tracked and sent to Customer.io
- System learns from user response to suggestions to improve future recommendations

**Technical Specifications:**
- Algorithm: Combination of Customer.io predictions and business rules
- Data Sources: `user_ai_profiles`, `user_goals`, `user_achievements`
- API Endpoint: `GET /api/next-best-actions`

### 8. Admin Features

#### 8.1 Brand Configuration
**User Story:** As an admin user, I want to configure brand-specific settings so I can customize the experience for my brand.

**Acceptance Criteria:**
- Admin can configure brand-specific achievement rules
- Admin can customize reward economics (points per $1 MSRP)
- Admin can configure referral bonuses and rewards
- Admin can customize PWA branding and theme
- Configuration changes are validated before application
- System maintains separate settings for each brand in the multi-tenant system

**Technical Specifications:**
- Admin Interface: Filament CMS with brand-scoped resources
- Configuration Storage: JSON in brands table
- Multi-tenancy: Tenant isolation via brand_id foreign keys

#### 8.2 Analytics Dashboard
**User Story:** As an admin user, I want to see analytics on user engagement and business metrics, so I can understand the performance of the loyalty program.

**Acceptance Criteria:**
- Dashboard shows key metrics (registrations, scans, redemptions)
- Dashboard includes Customer.io-derived insights (engagement scores, churn risk)
- Admin can drill down into specific time periods and user segments
- Dashboard performance is optimized for large datasets
- Data is updated regularly (near real-time) with caching
- System respects data access controls for multi-tenant environment

**Technical Specifications:**
- Database Views: Optimized for analytical queries
- Caching: Aggregated data cached for 5 minutes
- API Endpoints: `/api/analytics/*` with pagination and filtering