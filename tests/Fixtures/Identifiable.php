<?php

namespace Soap\ShoppingCart\Tests\Fixtures;

use Soap\ShoppingCart\Contracts\InstanceIdentifierInterface;

class Identifiable implements InstanceIdentifierInterface
{
    /**
     * @var int|string
     */
    private $identifier;

    /**
     * @var int
     */
    private $discountRate;

    /**
     * BuyableProduct constructor.
     *
     * @param  int|string  $id
     * @param  string  $name
     * @param  float  $price
     */
    public function __construct($identifier = 'identifier', $discountRate = 0)
    {
        $this->identifier = $identifier;
        $this->discountRate = $discountRate;
    }

    /**
     * Get the unique identifier to load the Cart from.
     */
    public function getInstanceIdentifier($options = null): int|string
    {
        return $this->identifier;
    }

    /**
     * Get the unique identifier to load the Cart from.
     *
     * @return int|string
     */
    public function getInstanceGlobalDiscount($options = null)
    {
        return $this->discountRate;
    }
}
