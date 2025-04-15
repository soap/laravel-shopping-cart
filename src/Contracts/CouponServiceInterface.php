<?php

namespace Soap\ShoppingCart\Contracts;

interface CouponServiceInterface
{
    /**
     * Retrieve a coupon by its code.
     */
    public function getCouponByCode(string $couponCode): ?CouponInterface;

    /**
     * Verify if the coupon is valid.
     */
    public function verifyCoupon(CouponInterface $coupon): bool;

    /**
     * Apply the coupon to the cart.
     */
    public function applyCoupon(CouponInterface $coupon): void;
}
