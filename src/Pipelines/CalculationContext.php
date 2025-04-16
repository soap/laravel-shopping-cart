<?php

namespace Soap\ShoppingCart\Pipelines;

class CalculationContext
{
    /**
     * The original amount of the cart before any discounts or coupons are applied.
     */
    public float $originalAmount;

    /**
     * The current amount of the cart after applying discounts and coupons.
     */
    public float $currentAmount;

    /**
     * The total amount of discounts applied to the cart.
     *
     * @var array
     */
    public array $savings = [];

    public function __construct(float $amount)
    {
        $this->originalAmount = $amount;
        $this->currentAmount = $amount;
    }
}
