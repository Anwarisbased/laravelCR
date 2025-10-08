## System Architecture Diagrams

**Document Version:** 1.0  
**Status:** Draft  
**Date:** October 8, 2025

### Overview
This document contains system architecture diagrams for the CannaRewards Synergy Engine platform. These diagrams visually represent the system components, data flows, and interactions with external services like Customer.io.

### 1. High-Level Architecture Overview
```mermaid
graph TB
    subgraph "Frontend Layer"
        A[Next.js PWA Client]
        B[Service Worker]
    end
    
    subgraph "Backend Layer"
        C[Laravel API]
        D[Event Queue]
        E[Database]
        F[Redis Cache]
    end
    
    subgraph "External Services"
        G[Customer.io]
        H[CDN]
    end
    
    subgraph "Monitoring"
        I[APM Tool]
        J[Log Aggregator]
    end
    
    A --> C
    B --> C
    C --> E
    C --> F
    C --> D
    D --> G
    E --> C
    F --> C
    C --> H
    C --> I
    C --> J
    G --> C
```

### 2. Customer.io Integration Flow
```mermaid
sequenceDiagram
    participant U as User
    participant PWA as Next.js PWA
    participant API as Laravel API
    participant Queue as Queue Worker
    participant CIO as Customer.io
    participant Webhook as Webhook Handler
    
    U->>PWA: Interact with App (scan, referral, etc.)
    PWA->>API: Send Event Data
    API->>Queue: Queue Event for Customer.io
    Queue->>CIO: Send Behavioral Event
    CIO->>CIO: Process Event & Generate Insights
    CIO->>Webhook: Send Insights via Webhook
    Webhook->>API: Process Insights
    API->>PWA: Update Personalized Experience
    PWA->>U: Show Personalized Content
```

### 3. Data Flow Architecture
```mermaid
graph LR
    subgraph "Data Sources"
        A[User Actions]
        B[Product Scans]
        C[Referral Events]
        D[Wishlist Updates]
    end
    
    subgraph "Processing Layer"
        E[Laravel Event Service]
        F[Data Transformation]
        G[AI Insights Processor]
    end
    
    subgraph "Data Destinations"
        H[Customer.io]
        I[Local Database]
        J[Redis Cache]
        K[PWA Client]
    end
    
    A --> E
    B --> E
    C --> E
    D --> E
    E --> F
    F --> H
    F --> I
    I --> G
    G --> J
    H --> G
    G --> K
```

### 4. Security Architecture
```mermaid
graph TD
    subgraph "Perimeter Security"
        A[DDoS Protection]
        B[WAF - Web Application Firewall]
        C[Rate Limiting]
    end
    
    subgraph "API Layer"
        D[JWT Authentication]
        E[API Gateway]
        F[Input Validation]
    end
    
    subgraph "Application Security"
        G[Laravel Security]
        H[Customer.io Credentials]
        I[Data Encryption]
    end
    
    subgraph "Data Layer"
        J[Database Security]
        K[Redis Security]
        L[Backup Encryption]
    end
    
    A --> B
    B --> C
    C --> E
    E --> D
    E --> F
    D --> G
    F --> G
    G --> H
    G --> I
    G --> J
    G --> K
    I --> L
```

### 5. Performance Architecture
```mermaid
graph LR
    subgraph "Caching Layer"
        A[CDN - Static Assets]
        B[Redis - API Responses]
        C[Browser Cache - Assets]
    end
    
    subgraph "Application Layer"
        D[Laravel App]
        E[API Rate Limiting]
        F[Background Jobs]
    end
    
    subgraph "Database Layer"
        G[Primary DB]
        H[Read Replicas]
        I[Connection Pooling]
    end
    
    subgraph "External Services"
        J[Customer.io API]
        K[Queue System]
        L[Monitoring Tools]
    end
    
    A --> D
    B --> D
    C --> D
    D --> E
    D --> F
    D --> G
    D --> J
    G --> H
    G --> I
    F --> K
    D --> L
```