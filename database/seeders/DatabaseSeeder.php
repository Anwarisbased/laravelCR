<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- USERS ---
        // We create a generic user for manual testing, but tests should create their own.
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'meta' => [
                '_canna_points_balance' => 10000,
                '_canna_lifetime_points' => 10000,
                '_canna_current_rank_key' => 'gold'
            ]
        ]);

        // --- RANKS ---
        Rank::create(['name' => 'Bronze', 'key' => 'bronze', 'points_required' => 1000, 'point_multiplier' => 1.2]);
        Rank::create(['name' => 'Silver', 'key' => 'silver', 'points_required' => 5000, 'point_multiplier' => 1.5]);
        Rank::create(['name' => 'Gold',   'key' => 'gold',   'points_required' => 10000, 'point_multiplier' => 2.0]);

        // --- PRODUCTS ---
        Product::create(['id' => 1, 'name' => 'Test Redemption Product', 'sku' => 'PWT-REDEEM-001', 'points_cost' => 5000]);
        Product::create(['id' => 204, 'name' => 'Laravel Welcome Gift', 'sku' => 'PWT-GIFT-001', 'points_cost' => 0]);
        Product::create(['id' => 205, 'name' => 'Standard Scannable Product', 'sku' => 'PWT-SCAN-001', 'points_award' => 400]);
        
        // --- CONFIGURATION ---
        // (This part is fine as is)
        \Illuminate\Support\Facades\Cache::put('wp_option_users_can_register', 1, now()->addYear());
        \Illuminate\Support\Facades\Cache::put('wp_option_canna_rewards_options', [
            'welcome_reward_product' => 204,
            // ... other options
        ], now()->addYear());
    }
}
