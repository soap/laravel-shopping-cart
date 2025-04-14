<?php

namespace Soap\ShoppingCart\Traits;

trait CanBeBought
{
    /**
     * Get the identifier of the Buyable item.
     */
    public function getBuyableIdentifier($options = null): int|string
    {
        return method_exists($this, 'getKey') ? $this->getKey() : $this->id;
    }

    /**
     * Get the name, title or description of the Buyable item.
     */
    public function getBuyableDescription($options = null): string
    {
        if (($name = $this->getAttribute('name'))) {
            return $name;
        }

        if (($title = $this->getAttribute('title'))) {
            return $title;
        }

        if (($description = $this->getAttribute('description'))) {
            return $description;
        }
    }

    /**
     * Get the price of the Buyable item.
     */
    public function getBuyablePrice($options = null): float
    {
        if (($price = $this->getAttribute('price'))) {
            return $price;
        }
    }

    /**
     * Get the weight of the Buyable item.
     */
    public function getBuyableWeight($options = null): float
    {
        if (($weight = $this->getAttribute('weight'))) {
            return $weight;
        }

        return 0;
    }
}
