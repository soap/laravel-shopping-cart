<?php

namespace Soap\ShoppingCart;

use Soap\ShoppingCart\Traits\HasCouponsSupport;

class DiscountManager
{
    use HasCouponsSupport;

    protected $cart;

    protected $couponManager;

    protected $conditionManager;

    public function __construct(ShoppingCart $cart, CouponManager $couponManager, ConditionManager $conditionManager)
    {
        $this->cart = $cart;
        $this->couponManager = $couponManager;
        $this->conditionManager = $conditionManager;
    }

    public function coupons()
    {
        return $this->couponManager;
    }

    public function conditions()
    {
        return $this->conditionManager;
    }
}
