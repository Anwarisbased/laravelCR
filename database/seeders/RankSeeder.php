<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Rank;

class RankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default ranks
        Rank::updateOrCreate([
            'key' => 'bronze',
        ], [
            'name' => 'Bronze Member',
            'points_required' => 0,
            'point_multiplier' => 1.0,
        ]);

        Rank::updateOrCreate([
            'key' => 'silver',
        ], [
            'name' => 'Silver Member',
            'points_required' => 500,
            'point_multiplier' => 1.25,
        ]);

        Rank::updateOrCreate([
            'key' => 'gold',
        ], [
            'name' => 'Gold Member',
            'points_required' => 1500,
            'point_multiplier' => 1.5,
        ]);

        Rank::updateOrCreate([
            'key' => 'platinum',
        ], [
            'name' => 'Platinum Member',
            'points_required' => 5000,
            'point_multiplier' => 2.0,
        ]);
    }
}
