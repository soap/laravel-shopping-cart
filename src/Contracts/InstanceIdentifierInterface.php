<?php

namespace Soap\ShoppingCart\Contracts;

interface InstanceIdentifierInterface
{
    /**
     * Get the unique identifier to load the Shopping Cart from.
     */
    public function getInstanceIdentifier($options = null): int|string;
}
