<?php

namespace Soap\ShoppingCart\Services;

use MichaelRubel\Couponables\Models\Coupon as ExternalCoupon;
use Soap\ShoppingCart\Adaptors\CouponAdapter;
use Soap\ShoppingCart\Contracts\CouponInterface;
use Soap\ShoppingCart\Contracts\CouponServiceInterface;

class CouponService implements CouponServiceInterface
{
    public function getCoupons(): array
    {
        // This method should return an array of coupons.
        // For example, you might fetch them from a database or an external API.
        return ExternalCoupon::all()->map(function ($coupon) {
            return new CouponAdapter($coupon);
        })->toArray();
    }

    public function getCouponByCode(string $couponCode): ?CouponInterface
    {
        $externalCoupon = ExternalCoupon::where('code', $couponCode)->first();

        if (! $externalCoupon) {
            return null;
        }

        return new CouponAdapter($externalCoupon);
    }

    public function verifyCoupon(string $couponCode, $orderAmount = 0, int|string|null $userId = null): bool
    {
        $coupon = $this->getCouponByCode($couponCode);

        if (! $coupon) {
            return false;
        }

        if ($coupon->isExpired()) {
            return false;
        }

        if ($coupon->getMinOrderValue() !== null && $orderAmount < $coupon->getMinOrderValue()) {
            return false;
        }

        return true;
    }

    public function applyCoupon(string $couponCode, $orderAmount = 0, int|string|null $userId = null): void
    {
        // Apply the coupon to the cart.
        // This is where you would implement the logic to apply the coupon discount to the cart.
        // For example, you might update the cart's total or add a discount line item.
    }
}
