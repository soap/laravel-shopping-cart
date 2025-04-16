<?php

namespace Soap\ShoppingCart;

use Illuminate\Pipeline\Pipeline;
use Soap\ShoppingCart\Pipelines;
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

    public function finalCalculation(): Pipelines\CalculationContext
    {
        $context = new Pipelines\CalculationContext($this->cart->subtotalFloat());

        // Discount on subtotal amount, order of execution
        $subtotalPipes = [
            Pipelines\ApplyPercentageSubtotalDiscount::class,
            Pipelines\ApplySubtractionSubtotalDiscount::class,
        ];

        $finalPipes = array_merge(
            $subtotalPipes,
            [
                Pipelines\ApplyPercentageTotalDiscount::class,
                Pipelines\ApplySubtractionTotalDiscount::class,
            ]);

        // Run the pipeline
        $finalContext = app(Pipeline::class)
            ->send($context)
            ->through($finalPipes)
            ->thenReturn();

        return $finalContext;
    }
}
