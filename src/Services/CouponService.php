<?php

namespace Soap\ShoppingCart\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use MichaelRubel\Couponables\Models\Coupon as ExternalCoupon;
use MichaelRubel\Couponables\Services\Contracts\CouponServiceContract;
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

    public function applyCoupon(string $couponCode, $orderAmount = 0, ?Authenticatable $user = null): ?CouponInterface
    {
        $service = app(CouponServiceContract::class);
        $coupon = $service->getCoupon($couponCode);

        if (! $user) {
            throw new \Exception('No authenticated user found to apply coupon.');
        }

        $user = $this->assertModel($user);

        // MichaelRubel\Couponables\Services\Contracts\CouponServiceContract::applyCoupon() method
        $appliedCoupon = $service->applyCoupon($coupon, $user, null);

        if (! $appliedCoupon) {
            return null;
        }

        return new CouponAdapter($appliedCoupon);
    }

    protected function assertModel(Authenticatable $user): Model
    {
        if (! $user instanceof Model) {
            throw new \InvalidArgumentException('The redeemer must be an Eloquent Model instance.');
        }

        return $user;
    }
}
