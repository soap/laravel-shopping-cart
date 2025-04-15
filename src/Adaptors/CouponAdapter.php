<?php

namespace Soap\ShoppingCart\Adaptors;

use Carbon\Carbon;
use MichaelRubel\Couponables\Models\Coupon;
use Soap\ShoppingCart\Contracts\CouponInterface as ExternalCoupon;

class CouponAdapter implements ExternalCoupon
{
    protected $coupon;

    public function __construct(Coupon $coupon)
    {
        $this->coupon = $coupon;
    }

    public function getCode(): string
    {
        // MichaelRubel\Couponables\Models\Coupon has a code property
        return $this->coupon->code;
    }

    public function getDiscountType(): string
    {
        return $this->coupon->type; // 'percentage', 'subtraction', or 'fixed'
    }

    public function getDiscountValue($total): float
    {
        return $this->coupon->value; // This is the discount value
    }

    public function getAppliesTarget(): ?string
    {
        if ($this->coupon->data->has('applies_to')) {
            return $this->coupon->data->get('applies_to');
        }

        return null;
    }

    public function getExpiresAt(): ?Carbon
    {
        return $this->coupon->expires_at;
    }

    public function isExpired(): bool
    {
        return $this->coupon->isExpired();
    }

    /**
     * Get minimum order value for the coupon to be valid.
     * This is custom data for Couponables package's coupon model
     */
    public function getMinOrderValue(): ?float
    {
        if ($this->coupon->data->has('min_order_total')) {
            return $this->coupon->data->get('min_order_total');
        }

        return null;
    }
}
