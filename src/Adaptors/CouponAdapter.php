<?php

namespace Soap\ShoppingCart\Adaptors;

use Soap\ShoppingCart\Contracts\CouponInterface as ExternalCoupon;

class CouponAdapter implements CouponInterface
{
    protected $coupon;

    public function __construct(ExternalCoupon $coupon)
    {
        $this->coupon = $coupon;
    }

    public function getCode(): string
    {
        return $this->coupon->getCode();
    }

    public function getDiscountType(): string
    {
        return $this->coupon->data->get('discount_type');
    }

    public function getDiscountValue($total): float
    {
        // will be implemented in the future
        // this is a placeholder for the discount value calculation
        return 0;
    }

    public function isExpired(): bool
    {
        return $this->coupon->isExpired();
    }

    /**
     * Get minimum order value for the coupon to be valid.
     * This is custom data for Couponables package's coupon model
     */
    public function getMinOrderTotal(): ?float
    {
        if ($this->coupon->data->has('min_order_total')) {
            return $this->coupon->data->get('min_order_total');
        }

        return null;
    }
}
