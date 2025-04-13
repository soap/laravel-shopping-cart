<?php

namespace Soap\ShoppingCart\Contracts;

use Soap\ShoppingCart\CartItem;

interface CalculatorInterface
{
    public static function getAttribute(string $attribute, CartItem $cartItem);
}
