<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->paragraph,
            'short_description' => fake()->sentence,
            'sku' => fake()->bothify('SKU-?????-#####'),
            'points_cost' => fake()->numberBetween(100, 5000),
            'points_award' => fake()->numberBetween(0, 100),
            'required_rank_key' => 'bronze',
            'is_active' => true,
            'is_featured' => fake()->boolean,
            'is_new' => fake()->boolean,
            'status' => 'active',
            'image_urls' => [fake()->imageUrl()],
            'tags' => [fake()->word],
        ];
    }
}
