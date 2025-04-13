<?php

namespace Soap\ShoppingCart\Contracts;

interface BuyableInterface
{
    /**
     * Get the buyable identifier.
     */
    public function getBuyableIdentifier($options = null): int|string;

    /**
     * Get the buyable description.
     */
    public function getBuyableDescription($options = null): string;

    /**
     * Get the buyable price.
     */
    public function getBuyablePrice($options = null): float;

    /**
     * Get the weight of the Buyable item.
     *
     * @return float
     */
    public function getBuyableWeight($options = null);
}
