<?php

namespace Soap\ShoppingCart\Adaptors;

use MichaelRubel\Couponables\Models\Coupon;
use Soap\ShoppingCart\Contracts\CouponInterface;

class CouponFactory
{
    public static function fromDTO(CouponDTO $dto): CouponInterface
    {
        $model = Coupon::where('code', $dto->code)
            ->where('is_enabled', true)
            ->whereDate('expires_at', '>=', now())
            ->first();

        if (! $model) {
            return new NullCouponAdapter($dto);
        }

        return new CouponAdapter($model);
    }
}
