<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'order_number' => 'CR-' . str_pad(fake()->unique()->numberBetween(1000, 999999), 6, '0', STR_PAD_LEFT),
            'status' => fake()->randomElement(['pending', 'processing', 'shipped', 'delivered', 'cancelled']),
            'points_cost' => fake()->numberBetween(0, 10000),
            'is_canna_redemption' => fake()->boolean,
            'shipping_first_name' => fake()->firstName,
            'shipping_last_name' => fake()->lastName,
            'shipping_address_1' => fake()->streetAddress,
            'shipping_city' => fake()->city,
            'shipping_state' => fake()->stateAbbr,
            'shipping_postcode' => fake()->postcode,
            'shipping_country' => 'US',
        ];
    }
}
