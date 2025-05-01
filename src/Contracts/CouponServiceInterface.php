<?php

namespace Soap\ShoppingCart\Contracts;

interface CouponServiceInterface
{
    public function getCoupons(): array;

    /**
     * Retrieve a coupon by its code.
     */
    public function getCouponByCode(string $couponCode): ?CouponInterface;

    /**
     * Verify if the coupon is valid.
     */
    public function verifyCoupon(string $couponCode, $orderAmount = 0, int|string|null $userId = null): bool;

    /**
     * Apply the coupon to the cart.
     */
    public function applyCoupon(string $couponCode, $orderAmount = 0, int|string|null $userId = null): void;
}
