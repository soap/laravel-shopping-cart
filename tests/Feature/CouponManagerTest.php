<?php

use Illuminate\Support\Facades\Session;
use Soap\ShoppingCart\Adaptors\CouponAdapter;
use Soap\ShoppingCart\CouponManager;

beforeEach(function () {
    Session::start();
    Session::flush();
});

it('can add coupon to session and retrieve it', function () {
    $instance = 'test_add_coupon';
    $coupon = createTestCoupon('SAVE10%');

    $adapter = new CouponAdapter($coupon);

    $manager = app()->make(CouponManager::class)->instance($instance);
    try {
        $manager->addFromAdapter($adapter);
    } catch (\Exception $e) {
        dump($manager->all()); // ดูว่าอะไรอยู่ใน session
        throw $e;
    }

    $all = $manager->all();

    expect($all)->toHaveKey('SAVE10%');
    expect($all['SAVE10%']['coupon']['code'])->toBe('SAVE10%');
    expect(Session::get("coupons.{$instance}"))->not->toBeNull();
});

it('can mark and unmark coupon as applied', function () {
    $instance = 'test_mark_and_unmark_coupon';

    $coupon = createTestCoupon('SAVE20%', ['value' => 20]);

    $adapter = new CouponAdapter($coupon);
    $manager = app()->make(CouponManager::class)->instance($instance);
    try {
        $manager->addFromAdapter($adapter);
    } catch (\Exception $e) {
        dump($manager->all()); // ดูว่าอะไรอยู่ใน session
        throw $e;
    }

    // Apply
    $manager->markAsApplied('SAVE20%', 20);

    $applied = $manager->get('SAVE20%');
    expect($applied['discount'])->toBe(20.0);
    expect($applied['applied'])->toBeTrue();

    // Unapply
    $manager->markAsUnapplied('SAVE20%');
    $unapplied = $manager->get('SAVE20%');

    expect($unapplied['applied'])->toBeFalse();
    expect($unapplied['discount'])->toBe(0);
});

it('can resolve coupon adapters from session', function () {
    $instance = 'test_resolve_coupon_adapters';

    $coupon = createTestCoupon('WELCOME50', ['type' => 'subtraction', 'value' => 50]);

    $adapter = new CouponAdapter($coupon);
    $manager = app()->make(CouponManager::class)->instance($instance);
    try {
        $manager->addFromAdapter($adapter);
    } catch (\Exception $e) {
        dump($manager->all()); // ดูว่าอะไรอยู่ใน session
        throw $e;
    }
    $manager->markAsApplied('WELCOME50', 50);

    $resolved = $manager->resolvedCoupons(true);

    expect($resolved)->toHaveCount(1);
    expect($resolved[0]['coupon']->getCode())->toBe('WELCOME50');
    expect($resolved[0]['discount'])->toBe(50.0);
    expect($resolved[0]['applied'])->toBeTrue();
});
