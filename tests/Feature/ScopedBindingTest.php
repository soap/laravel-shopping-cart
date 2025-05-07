<?php

use Illuminate\Support\Facades\App;
use Soap\ShoppingCart\Coupon\CouponManager;
use Soap\ShoppingCart\ShoppingCart;

it('creates a new ShoppingCart instance per request', function () {
    $cart1 = App::make(ShoppingCart::class);
    $cart2 = App::make(ShoppingCart::class);

    expect(spl_object_id($cart1))->toEqual(spl_object_id($cart2)) // 🟢 เพราะยังอยู่ใน request เดียว
        ->and($cart1)->toBeInstanceOf(ShoppingCart::class);
});

it('creates a new CouponManager instance per request', function () {
    $manager1 = App::make(CouponManager::class);
    $manager2 = App::make(CouponManager::class);

    expect(spl_object_id($manager1))->toEqual(spl_object_id($manager2)) // 🟢 shared in one request
        ->and($manager1)->toBeInstanceOf(CouponManager::class);
});

it('does not share scoped instance across separate requests', function () {
    // Simulate request 1
    $cartRequest1 = App::make(ShoppingCart::class);

    // Simulate ending request
    App::forgetInstance(ShoppingCart::class);

    // Simulate request 2
    $cartRequest2 = App::make(ShoppingCart::class);

    expect(spl_object_id($cartRequest1))->not->toEqual(spl_object_id($cartRequest2));
});
