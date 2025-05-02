<?php

use MichaelRubel\Couponables\Models\Coupon;
use Soap\ShoppingCart\ShoppingCart;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

it('applies fixed subtotal level discount correctly', function () {

    $user = User::create([
        'name' => 'Test User',
        'email' => 'john@example.net',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    actingAs($user);

    // Create a coupon ลดท้ายบิล 150 บาท
    // Coupon ต้องสร้างก่อน ShoppingCart เพราะ CouponManager จะโหลดข้อมูล Coupon จาก DB
    $coupon = Coupon::factory()->create([
        'code' => 'SUBTOTAL150',
        'type' => 'substraction',
        'value' => 150,
        'limit' => 1,
        'quantity' => 1,
        'data' => [
            'applies_to' => 'subtotal',
            'min_order_value' => 0,
            'discount_limit' => 150,
        ],
        'is_enabled' => true,
        'expires_at' => now()->addDay(2),
    ]);

    // Mock cart instance
    $cart = app(ShoppingCart::class);
    $cart->instance('testing')->destroy(); // Reset cart

    // Add items, add('id', 'name', 'qty', 'price')
    $cart->add('P001', 'Product A', 2, 500); // 2 x 500 = 1000
    $cart->add('P002', 'Product B', 1, 200); // 1 x 200 = 200

    // Apply the coupon
    $cart->applyCoupon('SUBTOTAL150'); // discount calculation runs after applying coupon

    expect($cart->initialSubtotalFloat())->toBe(1200.0)
        ->and($cart->finalSubtotalFloat())->toBe(1050.0)
        ->and($cart->subtotalLevelDiscountFloat())->toBe(150.0)
        ->and($cart->totalLevelDiscountFloat())->toBe(0.0)
        ->and($cart->finalPayableFloat())->toBe(1050.0);
});

it('applies percent subtotal level discount correctly', function () {

    $user = User::create([
        'name' => 'Test User',
        'email' => 'john@example.net',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    actingAs($user);

    // Create a coupon ลดท้ายบิล 150 บาท
    // Coupon ต้องสร้างก่อน ShoppingCart เพราะ CouponManager จะโหลดข้อมูล Coupon จาก DB
    $coupon = Coupon::factory()->create([
        'code' => 'SUBTOTAL10p',
        'type' => 'percent',
        'value' => 10,
        'limit' => 1,
        'quantity' => 1,
        'data' => [
            'applies_to' => 'subtotal',
            'min_order_value' => 0,
            'discount_limit' => 150,
        ],
        'is_enabled' => true,
        'expires_at' => now()->addDay(2),
    ]);

    // Mock cart instance
    $cart = app(ShoppingCart::class);
    $cart->instance('testing')->destroy(); // Reset cart

    // Add items, add('id', 'name', 'qty', 'price')
    $cart->add('P001', 'Product A', 2, 500); // 2 x 500 = 1000
    $cart->add('P002', 'Product B', 1, 200); // 1 x 200 = 200

    // Apply the coupon
    $cart->applyCoupon('SUBTOTAL10p'); // discount calculation runs after applying coupon

    expect($cart->initialSubtotal(1200.0))
        ->and($cart->finalSubtotalFloat())->toBe(1080.0)
        ->and($cart->subtotalLevelDiscountFloat())->toBe(120.0)
        ->and($cart->totalLevelDiscountFloat())->toBe(0.0)
        ->and($cart->finalPayableFloat())->toBe(1080.0);
});
