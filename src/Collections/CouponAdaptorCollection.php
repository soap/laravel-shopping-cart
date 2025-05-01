<?php

namespace Soap\ShoppingCart\Collections;

use Illuminate\Support\Collection;
use Soap\ShoppingCart\Adaptors\CouponAdapter;

/**
 * @extends Collection<int, CouponAdapter>
 */
class CouponAdapterCollection extends Collection
{
    public function __construct(array $items = [])
    {
        parent::__construct($this->validate($items));
    }

    protected function validate(array $items): array
    {
        foreach ($items as $item) {
            if (! $item instanceof CouponAdapter) {
                throw new \InvalidArgumentException('All items must be instances of CouponAdapter.');
            }
        }

        return $items;
    }

    public function getByCode(string $code): ?CouponAdapter
    {
        return $this->first(function (CouponAdapter $coupon) use ($code) {
            return $coupon->getCode() === $code;
        });
    }
}
