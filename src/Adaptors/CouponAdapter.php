<?php

namespace Soap\ShoppingCart\Adaptors;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use Soap\ShoppingCart\Contracts\CouponInterface as ExternalCoupon;

class CouponAdapter implements ExternalCoupon
{
    protected $coupon;

    public function __construct(CouponContract $coupon)
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

    public function getDiscountValue(): float
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

    public function isDisabled(): bool
    {
        return $this->coupon->isDisabled();
    }

    public function isOverQuantity(): bool
    {
        return $this->coupon->isOverQuantity();
    }

    public function isAllowedToRedeemBy(Model $user): bool
    {
        return $this->coupon->isAllowedToRedeemBy($user);
    }

    public function isOverLimitFor(Model $user): bool
    {
        return $this->coupon->isOverLimit($user);
    }

    /**
     * Get minimum order value for the coupon to be valid.
     * This is custom data for Couponables package's coupon model
     */
    public function getMinOrderValue(): ?float
    {
        if ($this->coupon->data && $this->coupon->data->has('min_order_value')) {
            return $this->coupon->data->get('min_order_value');
        }

        return null;
    }

    public function getDiscountLimit(): ?float
    {
        if ($this->coupon->data && $this->coupon->data->has('discount_limit')) {
            return $this->coupon->data->get('discount_limit');
        }

        return null;
    }
}
