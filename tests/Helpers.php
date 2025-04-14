<?php

use Soap\ShoppingCart\ShoppingCart;

if (! function_exists('getCart')) {
    function getCart(): ShoppingCart
    {
        return new ShoppingCart(app()->make('session'), app()->make('events'));
    }
}

if (! function_exists('setConfigFormat')) {
    function setConfigFormat($decimals, $decimalPoint, $thousandSeparator): void
    {
        app('config')->set('shopping-cart.format.decimals', $decimals);
        app('config')->set('shopping-cart.format.decimal_point', $decimalPoint);
        app('config')->set('shopping-cart.format.thousand_separator', $thousandSeparator);
    }
}

if (! function_exists('getCartWithDiscount')) {
    function getCartWithDiscount($discount = 50): ShoppingCart
    {
        $cart = getCart();
        $cart->setGlobalDiscount($discount);

        return $cart;
    }
}

if (! function_exists('itemsInCart')) {
    // Count the number of items in the cart (sum of ech item quantity)
    function itemsInCart($cart): int
    {
        return $cart->count();
    }
}

if (! function_exists('rowsInCart')) {
    // Count the number of rows in the cart (number of unique items)
    function rowsInCart($cart): int
    {
        return $cart->content()->count();
    }
}
