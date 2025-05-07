<?php

use Soap\ShoppingCart\CouponManager;

it('resolves the same ShoppingCart instance within a request', function () {
    Route::get('/test-cart', function () {
        $cart1 = app('shopping-cart');
        $cart2 = app('shopping-cart');

        return response()->json([
            'id1' => spl_object_id($cart1),
            'id2' => spl_object_id($cart2),
        ]);
    });

    $response = $this->get('/test-cart')->assertOk();

    $json = $response->json();

    expect($json['id1'])->toEqual($json['id2']); // ✅ ตรงใน request เดียว
});

it('creates a new CouponManager instance per request', function () {
    $manager1 = app(CouponManager::class);
    $manager2 = app(CouponManager::class);

    expect(spl_object_id($manager1))->toEqual(spl_object_id($manager2)) // 🟢 shared in one request
        ->and($manager1)->toBeInstanceOf(CouponManager::class);
});

it('does not share scoped instance across separate requests', function () {
    // Simulate request 1
    $cartRequest1 = app('shopping-cart');

    // Simulate ending request
    app()->forgetInstance('shopping-cart');

    // Simulate request 2
    $cartRequest2 = app('shopping-cart');

    expect(spl_object_id($cartRequest1))->not->toEqual(spl_object_id($cartRequest2));
});
