<?php

namespace Soap\ShoppingCart\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface CouponServiceInterface
{
    public function getCoupons(): array;

    /**
     * Retrieve a coupon by its code.
     */
    public function getCouponByCode(string $couponCode): ?CouponInterface;

    /**
     * Apply the coupon to the cart.
     */
    public function applyCoupon(string $couponCode, $orderAmount = 0, ?Authenticatable $user = null): ?CouponInterface;
}
