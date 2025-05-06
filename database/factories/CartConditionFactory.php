<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Soap\ShoppingCart\Models\CartCondition;

class CartConditionFactory extends Factory
{
    protected $model = CartCondition::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug,
            'expression' => 'cart.subtotal() > 500',
            'value' => 10,
            'type' => 'percentage',
            'target' => 'subtotal',
            'is_active' => true,
            'published_up' => now()->subDays(1),
            'published_down' => now()->addDays(7),
        ];
    }

    public function inactive()
    {
        return $this->state(['is_active' => false]);
    }
}
