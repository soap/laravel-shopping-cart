<?php

namespace Soap\ShoppingCart\Services;

use MichaelRubel\LaravelCouponables\Models\Coupon as ExternalCoupon;
use Soap\ShoppingCart\Adaptors\CouponAdapter;
use Soap\ShoppingCart\Contracts\CouponInterface;
use Soap\ShoppingCart\Contracts\CouponServiceInterface;

class CouponService implements CouponServiceInterface
{
    public function getCouponByCode(string $couponCode): ?CouponInterface
    {
        $externalCoupon = ExternalCoupon::where('code', $couponCode)->first();

        if (! $externalCoupon) {
            return null;
        }

        return new CouponAdapter($externalCoupon);
    }

    public function verifyCoupon(CouponInterface $coupon): bool
    {
        if ($coupon->isExpired()) {
            return false;
        }

        if ($coupon->getMinOrderTotal() !== null && /* compare with current cart subtotal */ false) {
            return false;
        }

        return true;
    }

    public function applyCoupon(CouponInterface $coupon): void
    {
        // Apply the coupon to the cart.
        // This is where you would implement the logic to apply the coupon discount to the cart.
        // For example, you might update the cart's total or add a discount line item.
    }
}
