<?php

use Soap\ShoppingCart\Facades\ShoppingCart as ShoppingCartFacade;

it('can bind a class in the service provider and resolve via facade', function () {
    $cartInstance = app('shopping-cart'); // resolves the class binding
    expect($cartInstance)->toBeInstanceOf(\Soap\ShoppingCart\ShoppingCart::class);

    // Using the facade alias, you can check the same:
    $facadeRoot = ShoppingCartFacade::getFacadeRoot();
    expect($facadeRoot)->toBeInstanceOf(\Soap\ShoppingCart\ShoppingCart::class);
});

it('returns the same instance from the container and the facade', function () {
    // Resolve the binding directly from the container.
    $containerCart = app('shopping-cart');
    // Resolve the underlying instance via the facade.
    $facadeCart = ShoppingCartFacade::getFacadeRoot();

    expect($facadeCart)->toBe($containerCart);
});

it('shares the same cart state between facade and app', function () {
    ShoppingCartFacade::add('product_123', 'Product ABC', 1, 99);

    $cart = app('shopping-cart');
    expect($cart->content()->count())->toBe(1);
    expect($cart->content()->first()->id)->toBe('product_123');
});
