<?php

use MichaelRubel\Couponables\Models\Coupon;
use Soap\ShoppingCart\ShoppingCart;

if (! function_exists('getCart')) {
    function getCart(): ShoppingCart
    {
        return app()->make('shopping-cart');
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

if (! function_exists('createTestCoupon')) {
    function createTestCoupon(string $code, array $overrides = []): Coupon
    {
        return Coupon::factory()->create(array_merge([
            'code' => $code,
            'type' => 'percentage',
            'value' => 10,
            'data' => [
                'applies_to' => 'subtotal',
                'min_order_value' => 0,
                'discount_limit' => 500,
            ],
            'is_enabled' => true,
            'expires_at' => now()->addDay(),
        ], $overrides));
    }
}
