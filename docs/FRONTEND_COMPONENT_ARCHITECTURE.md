## Frontend Component Architecture for Next.js PWA

**Document Version:** 1.0  
**Status:** Draft  
**Date:** October 8, 2025

### Overview
This document specifies the frontend architecture for the CannaRewards Progressive Web App (PWA) built with Next.js. It details the component structure, state management, theming, and integration points with the backend API and Customer.io.

### 1. Architecture Overview

#### 1.1 Technology Stack
- **Framework**: Next.js 14+ with App Router
- **Styling**: Tailwind CSS with custom theme integration
- **UI Components**: shadcn/ui with custom CannaRewards styling
- **State Management**: React Query for server state, Zustand for client state
- **Forms**: React Hook Form with Zod validation
- **API Communication**: Axios with custom interceptors
- **PWA Features**: Next.js PWA plugin with custom manifest

#### 1.2 Directory Structure
```
src/
├── app/                    # Next.js app router pages
│   ├── (auth)/            # Authentication routes
│   │   ├── login/
│   │   ├── register/
│   │   └── forgot-password/
│   ├── (dashboard)/       # Main dashboard routes
│   │   ├── profile/
│   │   ├── scans/
│   │   ├── wishlist/
│   │   ├── achievements/
│   │   └── referrals/
│   ├── api/               # API route handlers
│   └── globals.css        # Global styles
├── components/            # Reusable UI components
│   ├── ui/               # Base UI components (shadcn/ui)
│   ├── cards/            # Custom card components
│   ├── forms/            # Form components
│   ├── pwa/              # PWA-specific components
│   └── marketing/        # Marketing-specific components
├── hooks/                 # Custom React hooks
├── lib/                   # Utilities and constants
├── services/              # API service layer
├── store/                 # Client state management
├── types/                 # TypeScript type definitions
└── providers/            # React context providers
```

### 2. Component Architecture

#### 2.1 Base UI Components
All base components extend shadcn/ui components with CannaRewards-specific styling:

```tsx
// components/ui/button.tsx
import { Button as UIButton } from "@/components/ui/button"
import { cn } from "@/lib/utils"

interface CannaButtonProps extends React.ComponentProps<typeof UIButton> {
  variant?: "primary" | "secondary" | "outline" | "ghost"
  size?: "sm" | "md" | "lg"
}

const CannaButton = ({ className, variant, size, ...props }: CannaButtonProps) => {
  return (
    <UIButton
      className={cn(
        "bg-[var(--primary)] hover:bg-[var(--primary-600)]",
        "text-[var(--primary-foreground)]",
        className
      )}
      variant={variant}
      size={size}
      {...props}
    />
  )
}

export { CannaButton as Button }
```

#### 2.2 PWA-Specific Components

**2.2.1 Theme Provider**:
```tsx
// providers/ThemeProvider.tsx
'use client'

import { ThemeProvider as NextThemesProvider } from "next-themes"
import { type ThemeProviderProps } from "next-themes/dist/types"
import { useEffect, useState } from "react"

export function ThemeProvider({ children, ...props }: ThemeProviderProps) {
  const [mounted, setMounted] = useState(false)

  useEffect(() => {
    setMounted(true)
    
    // Fetch theme variables from backend API
    fetch('/api/theme/config')
      .then(res => res.json())
      .then(theme => {
        Object.entries(theme).forEach(([key, value]) => {
          document.documentElement.style.setProperty(`--${key}`, value);
        });
      })
  }, [])

  if (!mounted) {
    return <div className="min-h-screen" /> // Prevent hydration mismatch
  }

  return (
    <NextThemesProvider {...props}>
      {children}
    </NextThemesProvider>
  )
}
```

**2.2.2 Achievement Display Component**:
```tsx
// components/cards/AchievementCard.tsx
'use client'

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Progress } from "@/components/ui/progress"
import { useUser } from "@/hooks/useUser"

interface AchievementCardProps {
  achievement: {
    id: string
    name: string
    description: string
    completed: boolean
    progress: number
    target: number
    pointsReward: number
    rarity: 'common' | 'uncommon' | 'rare' | 'epic' | 'legendary'
    badge: string
  }
}

export function AchievementCard({ achievement }: AchievementCardProps) {
  const { data: user, mutate: updateUser } = useUser()
  const isPersonalized = achievement.rarity === 'legendary' // Based on Customer.io insights

  return (
    <Card className={isPersonalized ? "ring-2 ring-[var(--primary)]" : ""}>
      <CardHeader className="pb-2">
        <div className="flex justify-between items-start">
          <CardTitle className="text-lg">{achievement.name}</CardTitle>
          <Badge 
            variant="secondary" 
            className={`
              ${achievement.rarity === 'common' ? 'bg-gray-200' : 
                achievement.rarity === 'uncommon' ? 'bg-green-200' : 
                achievement.rarity === 'rare' ? 'bg-blue-200' : 
                achievement.rarity === 'epic' ? 'bg-purple-200' : 
                'bg-yellow-200'}
            `}
          >
            {achievement.rarity}
          </Badge>
        </div>
        <p className="text-sm text-muted-foreground">{achievement.description}</p>
      </CardHeader>
      <CardContent>
        <div className="flex items-center gap-2 mb-2">
          <span className="text-sm font-medium">+{achievement.pointsReward} points</span>
          {!achievement.completed && (
            <span className="text-sm text-muted-foreground">
              {achievement.progress}/{achievement.target}
            </span>
          )}
        </div>
        {!achievement.completed && (
          <Progress value={(achievement.progress / achievement.target) * 100} />
        )}
      </CardContent>
    </Card>
  )
}
```

**2.2.3 PWA Card Component (for displaying synergistic content)**:
```tsx
// components/cards/PWACard.tsx
'use client'

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { useUser } from "@/hooks/useUser"
import { CustomerIoInsights } from "@/types/customer-io"

interface PWACardProps {
  card: {
    id: string
    type: 'achievement' | 'referral' | 'wishlist' | 'recommendation'
    title: string
    description: string
    action?: {
      text: string
      url: string
      type: 'primary' | 'secondary'
    }
    priority: number // Based on Customer.io insights
    relevance?: CustomerIoInsights // AI-derived relevance data
  }
}

export function PWACard({ card }: PWACardProps) {
  const { data: user } = useUser()
  
  // Determine if this card is AI-personalized based on user insights
  const isPersonalized = card.relevance?.predicted_user_interest > 0.7
  
  return (
    <Card className={`${isPersonalized ? "ring-2 ring-[var(--primary)] border-[var(--primary)]" : ""}`}>
      <CardHeader>
        <CardTitle>{card.title}</CardTitle>
      </CardHeader>
      <CardContent>
        <p className="mb-4">{card.description}</p>
        {card.action && (
          <Button 
            variant={card.action.type === 'primary' ? "default" : "outline"}
            className="w-full"
            onClick={() => window.location.href = card.action!.url}
          >
            {card.action.text}
          </Button>
        )}
      </CardContent>
    </Card>
  )
}
```

#### 2.3 State Management

**2.3.1 User Store**:
```tsx
// store/userStore.ts
import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface UserState {
  user: any | null
  setUser: (user: any) => void
  updateUser: (data: any) => void
  logout: () => void
  aiInsights: any | null
  setAiInsights: (insights: any) => void
  achievements: any[]
  setAchievements: (achievements: any[]) => void
}

export const useUserStore = create<UserState>()(
  persist(
    (set, get) => ({
      user: null,
      aiInsights: null,
      achievements: [],
      
      setUser: (user) => set({ user }),
      
      updateUser: (data) => set((state) => ({ 
        user: { ...state.user, ...data } 
      })),
      
      setAiInsights: (insights) => set({ aiInsights: insights }),
      
      setAchievements: (achievements) => set({ achievements }),
      
      logout: () => {
        set({ user: null, aiInsights: null, achievements: [] })
      }
    }),
    {
      name: 'cannarewards-user-storage',
    }
  )
)
```

**2.3.2 API Service Layer**:
```tsx
// services/api.ts
import axios from 'axios'

const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api'

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor to handle errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Handle unauthorized access
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
```

### 3. PWA Features

#### 3.1 Manifest Configuration
```json
{
  "name": "CannaRewards",
  "short_name": "CR",
  "description": "Cannabis Loyalty Platform",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#10b981",
  "icons": [
    {
      "src": "/icons/icon-192x192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/icons/icon-512x512.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ]
}
```

#### 3.2 Offline Capability
- Service worker implementation for caching
- Offline-first approach for user data
- Background sync for pending actions

### 4. Theming Integration

#### 4.1 Dynamic Theme Loading
- Fetch theme variables from backend API endpoint `/api/theme/config`
- Apply CSS custom properties dynamically
- Support for brand-specific theming

#### 4.2 Theme Schema
```ts
// types/theme.ts
export interface ThemeConfig {
  primary: {
    DEFAULT: string
    50: string
    100: string
    200: string
    300: string
    400: string
    500: string
    600: string
    700: string
    800: string
    900: string
  }
  secondary: {
    // Similar structure as primary
  }
  background: string
  card: string
  border: string
  borderRadius: {
    sm: string
    md: string
    lg: string
    xl: string
  }
  typography: {
    fontFamily: string
    fontSize: {
      sm: string
      base: string
      lg: string
      xl: string
    }
    fontWeight: {
      normal: string
      medium: string
      semibold: string
      bold: string
    }
  }
}
```

### 5. Customer.io Integration in Frontend

#### 5.1 AI-Personalized Content
- Fetch user insights from backend (derived from Customer.io)
- Render personalized cards based on AI predictions
- Adjust UI element visibility based on user segments

#### 5.2 Event Tracking
```tsx
// hooks/useTrackEvent.ts
import { useEffect } from 'react'

export const useTrackEvent = (eventName: string, properties?: any) => {
  useEffect(() => {
    // Track event in Customer.io via backend
    fetch('/api/events/track', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        name: eventName,
        data: properties,
      }),
    })
  }, [eventName, properties])
}
```

### 6. Performance Optimizations

#### 6.1 Code Splitting
- Dynamic imports for heavy components
- Route-based code splitting
- Component-level lazy loading

#### 6.2 Image Optimization
- Next.js Image component with WebP support
- Automatic image sizing and optimization
- Lazy loading for off-screen images

#### 6.3 Caching Strategy
- React Query for server state caching
- SWR for data fetching with caching
- Component memoization for expensive renders