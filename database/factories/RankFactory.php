<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rank>
 */
class RankFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word . ' Member',
            'key' => strtolower(fake()->word),
            'points_required' => fake()->numberBetween(0, 1000),
            'point_multiplier' => fake()->randomElement([1.0, 1.2, 1.5, 2.0]),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
