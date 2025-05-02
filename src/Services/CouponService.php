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

    public function applyCoupon(string $couponCode, $orderAmount = 0, int|string|null $userId = null, ?string $guard = null): ?CouponInterface
    {
        $service = app(CouponServiceContract::class);
        $coupon = $service->getCoupon($couponCode);

        $user = $this->resolveUser($userId, $guard);
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

    /**
     * Resolve the user based on the provided user ID or guard.
     * Supports multiple guards with different models.
     */
    protected function resolveUser(int|string|null $userId = null, ?string $guard = null): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        if ($userId) {
            if ($guard) {
                $provider = config("auth.guards.{$guard}.provider");

                if (! $provider) {
                    return null;
                }

                $modelClass = config("auth.providers.{$provider}.model");

                if (! class_exists($modelClass)) {
                    return null;
                }

                return $modelClass::find($userId);
            }

            foreach (config('auth.guards') as $guardName => $guardConfig) {
                $provider = $guardConfig['provider'] ?? null;

                if (! $provider) {
                    continue;
                }

                $modelClass = config('auth.providers.'.$provider.'.model');

                if (class_exists($modelClass)) {
                    $user = $modelClass::find($userId);

                    if ($user) {
                        return $user;
                    }
                }
            }

            return null;
        }

        if ($guard) {
            if (auth($guard)->check()) {
                return auth($guard)->user();
            }

            return null;
        }

        foreach (array_keys(config('auth.guards')) as $guardName) {
            if (auth($guardName)->check()) {
                return auth($guardName)->user();
            }
        }

        return null;
    }

    protected function assertModel(Authenticatable $user): Model
    {
        if (! $user instanceof Model) {
            throw new \InvalidArgumentException('The redeemer must be an Eloquent Model instance.');
        }

        return $user;
    }
}
