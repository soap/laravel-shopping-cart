<?php

namespace Soap\LaravelShoppingCart\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Soap\ShoppingCart\ShoppingCart
 */
class ShoppingCart extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Soap\LaravelShoppingCart\ShoppingCart::class;
    }
}
