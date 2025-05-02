<?php

use Soap\ShoppingCart\ShoppingCart;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

it('calculates subtotal and total with no discount correclty ', function () {

    $user = User::create([
        'name' => 'Test User',
        'email' => 'john@example.net',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    actingAs($user);

    // Mock cart instance
    $cart = app(ShoppingCart::class);
    $cart->instance('testing')->destroy(); // Reset cart

    // Add items, add('id', 'name', 'qty', 'price')
    $cart->add('P001', 'Product A', 2, 500); // 2 x 500 = 1000
    $cart->add('P002', 'Product B', 1, 200); // 1 x 200 = 200

    expect($cart->finalSubtotalFloat())->toBe(1200.0)->and($cart->totalLevelDiscountFloat())->toBe(0.0)
        ->and($cart->finalPayableFloat())->toBe(1200.0);
    expect($cart->subtotal)->toBe('1,200.00')->and($cart->totalLevelDiscount())->toBe('0.00')
        ->and($cart->total)->toBe('1,200.00'); // 1200 - 0
});
