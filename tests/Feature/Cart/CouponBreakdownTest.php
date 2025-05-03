<?php

use MichaelRubel\Couponables\Models\Coupon;
use Soap\ShoppingCart\Facades\ShoppingCart;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

dataset('couponCases', [
    'percentage on subtotal' => [
        [
            'coupon' => [
                'code' => 'SAVE10',
                'type' => 'percentage',
                'value' => 10,
                'limit' => 1,
                'quantity' => 1,
                'data' => [
                    'applies_to' => 'subtotal',
                    'min_order_value' => 0,
                    'discount_limit' => 500,
                ],
                'is_enabled' => true,
                'expires_at' => now()->addDay(2),
            ],
            'expectedSubtotal' => 900.0,
            'expectedTotal' => 900.0, // 10% ของ 1000
            'expectedDiscount' => 100.0,
            'expected' => [
                'level' => 'subtotal',
                'type' => 'percentage',
                'value' => 10,
                // 'amount' => 100.0, // 10% of 1000
            ],
        ],
    ],
    'fixed on subtotal' => [
        [
            'coupon' => [
                'code' => 'TAKE50',
                'type' => 'subtraction',
                'value' => 50,
                'limit' => 1,
                'quantity' => 1,
                'data' => [
                    'applies_to' => 'subtotal',
                    'min_order_value' => 0,
                    'discount_limit' => 500,
                ],
                'is_enabled' => true,
                'expires_at' => now()->addDay(2),
            ],
            'expectedSubtotal' => 950.0,
            'expectedTotal' => 950.0,
            'expectedDiscount' => 50.0,
            'expected' => [
                'level' => 'subtotal',
                'type' => 'subtraction',
                'value' => 50,
                // 'amount' => 50.0,
            ],
        ],
    ],
    'percentage on total' => [
        [
            'coupon' => [
                'code' => 'ALL5',
                'type' => 'percentage',
                'value' => 5,
                'limit' => 1,
                'quantity' => 1,
                'data' => [
                    'applies_to' => 'total',
                    'min_order_value' => 0,
                    'discount_limit' => 500,
                ],
                'is_enabled' => true,
                'expires_at' => now()->addDay(2),
            ],
            'expectedSubtotal' => 1000.0,
            'expectedTotal' => 950.0, // 5% ของ 1000
            'expectedDiscount' => 50.0,
            'expected' => [
                'level' => 'total',
                'type' => 'percentage',
                'value' => 5,
                // 'amount' => 50.0, // 5% of 1000
            ],
        ],
    ],
    'fixed on total' => [
        [
            'coupon' => [
                'code' => 'ALL100',
                'type' => 'subtraction',
                'value' => 100,
                'limit' => 1,
                'quantity' => 1,
                'data' => [
                    'applies_to' => 'total',
                    'min_order_value' => 0,
                    'discount_limit' => 500,
                ],
                'is_enabled' => true,
                'expires_at' => now()->addDay(2),
            ],
            'expectedSubtotal' => 1000.0,
            'expectedTotal' => 900.0,
            'expectedDiscount' => 100.0,
            'expected' => [
                'level' => 'total',
                'type' => 'subtraction',
                'value' => 100.0,
                // 'amount' => 100.0,
            ],
        ],
    ],
]);

it('applies coupon correctly and tracks coupon breakdown', function ($data) {
    // Arrange
    $coupon = $coupon = Coupon::factory()->create($data['coupon']);

    ShoppingCart::destroy();

    actingAs($user = User::create([
        'name' => 'Test User',
        'email' => 'tt@example.net',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]));

    // Act
    $cart = app('shopping-cart');
    $cart->add(1, 'Course A', 1000, 1); // 1 x 1000 = 1000
    $result = $cart->applyCoupon($coupon->code); // Apply the coupon

    // Assert
    $breakdown = $cart->getCouponBreakdown();

    expect($result)->toBeTrue();
    expect($breakdown)->toHaveCount(1);

    $entry = $breakdown[0];
    expect($entry['code'])->toBe($data['coupon']['code']);
    expect($entry['level'])->toBe($data['expected']['level']);
    expect($entry['type'])->toBe($data['expected']['type']);
    expect($entry['value'])->toEqual($data['expected']['value']);
    // expect($entry['amount'])->toBe($data['expected']['amount']);

    expect($cart->initialSubtotalFloat())->toEqual(1000.0);
    expect($cart->finalSubtotalFloat())->toEqual($data['expectedSubtotal']);
    expect($cart->finalPayableFloat())->toEqual($data['expectedTotal']);
    expect($cart->discountFloat())->toEqual($data['expectedDiscount']);
})->with('couponCases');
