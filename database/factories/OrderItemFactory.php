<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => \App\Models\Order::factory(),
            'product_id' => \App\Models\Product::factory(),
            'product_name' => fake()->word,
            'product_sku' => fake()->bothify('SKU-?????-#####'),
            'quantity' => fake()->numberBetween(1, 5),
            'points_value' => fake()->numberBetween(100, 5000),
        ];
    }
}
