<?php

use Illuminate\Support\Facades\DB;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

it('applies a coupon and reserves it', function () {
    $user = User::factory()->create();

    $coupon = createTestCoupon('WELCOME10', [
        'data' => [
            'applies_to' => 'total',
            'min_order_value' => 0,
            'discount_limit' => 500,
        ],
    ]);

    actingAs($user);

    $cart = getCart();
    $cart->add(1, 'Test Product', 1, 100.0);
    expect($cart->total())->toBe('100.00');

    $cart->applyCoupon($coupon->code);

    $couponCodes = collect($cart->getResolvedCoupons())->pluck('code')->all();
    expect($couponCodes)->toContain('WELCOME10');

    // ตรวจว่าคูปองถูก reserve ใน reservation store
    $this->assertDatabaseHas('coupon_reservations', [
        'coupon_code' => 'WELCOME10',
        'reserver_id' => $user->id,
        'reserver_type' => $user::class,
    ]);

    // ตรวจว่า session มีข้อมูลคูปอง
    expect(session('coupons.default'))->not->toBeEmpty();
});

it('removes a coupon and releases it from the reservation store', function () {
    $user = User::factory()->create();

    $coupon = createTestCoupon('WELCOME10', [
        'data' => [
            'applies_to' => 'total',
            'min_order_value' => 0,
            'discount_limit' => 500,
        ],
    ]);

    actingAs($user);

    $cart = getCart();
    $cart->add(1, 'Test Product', 1, 100.0);
    $cart->applyCoupon($coupon->code);

    // ตรวจว่าถูก reserve แล้วจริง
    $this->assertDatabaseHas('coupon_reservations', [
        'coupon_code' => 'WELCOME10',
        'reserver_id' => $user->id,
        'reserver_type' => $user::class,
    ]);

    // ✅ Remove coupon
    $cart->removeCoupon($coupon->code);

    // ตรวจว่า coupon ถูกลบออกจาก session แล้ว
    expect(session('coupons.default'))->toBeArray()->and(session('coupons.default'))->not->toHaveKey('WELCOME10');

    // ✅ ตรวจว่า reservation ถูกลบแล้ว
    $this->assertDatabaseMissing('coupon_reservations', [
        'coupon_code' => 'WELCOME10',
        'reserver_id' => $user->id,
        'reserver_type' => $user::class,
    ]);
});

it('applies a coupon and uses it on checkout', function () {
    $user = User::factory()->create();

    $coupon = createTestCoupon('WELCOME10', [
        'quantity' => 5,
        'data' => [
            'applies_to' => 'total',
            'discount_type' => 'fixed',
            'value' => 20,
        ],
    ]);

    actingAs($user);

    $cart = getCart();
    $cart->add(1, 'Test Product', 1, 100.0);
    $cart->applyCoupon('WELCOME10');

    // ก่อน checkout ตรวจว่า coupon ถูก apply และยังอยู่ใน reservation
    expect($cart->getResolvedCoupons())->not->toBeEmpty();

    $this->assertDatabaseHas('coupon_reservations', [
        'coupon_code' => 'WELCOME10',
        'reserver_id' => $user->id,
        'reserver_type' => User::class,
    ]);

    // ✅ ทำการ checkout
    $cart->checkout(); // สมมุติว่า checkout จะ handle ทุกอย่าง

    // ตรวจว่า coupon ถูก mark ว่าใช้แล้ว (Couponables)
    $this->assertDatabaseHas('couponables', [
        'couponable_id' => $user->id,
        'couponable_type' => User::class,
        'coupon_id' => $coupon->id,
    ]);

    // ดึงแถวจาก DB แล้ว assert ว่า redeemed_at ไม่ null
    $row = DB::table('couponables')
        ->where('couponable_id', $user->id)
        ->where('couponable_type', User::class)
        ->where('coupon_id', $coupon->id)
        ->first();

    expect($row->redeemed_at)->not->toBeNull();

    // ตรวจว่า coupon reservation หายไป

    $this->assertDatabaseMissing('coupon_reservations', [
        'coupon_code' => 'WELCOME10',
        'reserver_id' => $user->id,
    ]);

    // ตรวจว่า quantity ลดลง (ถ้าใช้ระบบหัก quantity)
    $coupon->refresh();
    expect($coupon->quantity)->toBe(4);
});
