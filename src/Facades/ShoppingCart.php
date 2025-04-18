<?php

namespace Soap\ShoppingCart\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Soap\ShoppingCart\ShoppingCart
 */
class ShoppingCart extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'shopping-cart';
    }
}
