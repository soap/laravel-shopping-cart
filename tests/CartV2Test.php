<?php

use Soap\ShoppingCart\Calculation\CustomCartItemCalculator;
use Soap\ShoppingCart\CartItem;
use Soap\ShoppingCart\ShoppingCart;

it('calculates subtotalFloat correctly using calculator from config without discount', function () {
    // Arrange: set config ให้แน่ใจว่ามันใช้ CustomCartItemCalculator
    config()->set('shopping-cart.calculator', CustomCartItemCalculator::class);
    config()->set('shopping-cart.tax', 0.0); // ไม่ต้องการ tax ใน test นี้

    $cart = new ShoppingCart(
        app('session'),
        app('events')
    );

    // Act: ใช้ add() ปกติ ไม่ต้อง manually calculate
    $cart->add([
        'id' => '1',
        'name' => 'Item 1',
        'qty' => 2,
        'price' => 100,
        // ไม่มี discount field เพราะไม่ต้องการส่วนลด
    ]);

    $cart->add([
        'id' => '2',
        'name' => 'Item 2',
        'qty' => 1,
        'price' => 200,
    ]);

    $subtotal = $cart->subtotalFloat();

    // คิดตัวเลข
    // Item 1: 100 * 2 = 200
    // Item 2: 200 * 1 = 200
    // รวม = 400

    // Assert
    expect($subtotal)->toBe(400.0);

    // และเพื่อให้มั่นใจว่าแต่ละ CartItem มีการคำนวณ finalSubtotal แล้วจริง
    $cart->content()->each(function (CartItem $item) {
        expect($item->finalSubtotal)->not()->toBeNull();
    });
});

it('applies coupon discount to total without item-level discount or tax', function () {
    // Arrange
    config()->set('cart.cart_item_calculator', CustomCartItemCalculator::class);

    // Mock coupon service to return a 10% discount coupon
    // หรือใช้ real CouponService ถ้าคุณมี migration/setup พร้อม
    $couponService = Mockery::mock(\Soap\ShoppingCart\Contracts\CouponServiceInterface::class);
    $couponService
        ->shouldReceive('getCouponByCode')
        ->with('SAVE10')
        ->andReturn((object) [
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 10, // 10%
            'applies_to' => 'subtotal',
        ]);
    app()->instance(\Soap\ShoppingCart\Contracts\CouponServiceInterface::class, $couponService);

    // Init cart
    $cart = new ShoppingCart(app('session'), app('events'));

    // Add items without discount or tax
    $cart->add([
        'id' => '1',
        'name' => 'Product A',
        'qty' => 2,
        'price' => 100,
    ]);

    $cart->add([
        'id' => '2',
        'name' => 'Product B',
        'qty' => 1,
        'price' => 200,
    ]);

    // Add coupon via manager
    $manager = new CouponManager($cart);
    $manager->add('SAVE10');

    // Act
    $subtotal = $cart->subtotalFloat(); // 2*100 + 1*200 = 400
    $total = $cart->total();            // 400 - 10% = 360

    // Assert
    expect($subtotal)->toBe(400.0);
    expect($total)->toBe(360.0);

    // Check that finalSubtotal is set on each item
    $cart->content()->each(function (CartItem $item) {
        expect($item->finalSubtotal)->toBeGreaterThan(0);
    });
});
