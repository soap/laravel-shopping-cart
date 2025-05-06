<?php

namespace Soap\ShoppingCart;

use Illuminate\Pipeline\Pipeline;
use Soap\ShoppingCart\Pipelines\AllocateSubtotalDiscounts;
use Soap\ShoppingCart\Pipelines\ApplyItemsDiscounts;
use Soap\ShoppingCart\Pipelines\ApplySubtotalDiscounts;
use Soap\ShoppingCart\Pipelines\ApplyTotalDiscounts;
use Soap\ShoppingCart\Pipelines\CalculationContext;
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

    public function checkout()
    {
        $this->couponManager->applyCoupnsUsage($this->cart);
    }

    /**
     * Calculate the discounts for the cart.
     * Get discount from coupon and condition manager.
     * The discount has three levels; item, subtotal, and total.
     * for subtotal level, we need to distribute the discount to each item.
     * We need to calculate the discount for each item first.
     * Then we need to calculate the subtotal discount.
     * Then distribute the subtotal discount to each item.
     * After that we need to calculate the total discount.
     *
     * Then the calculator class will use the discount to calculate to required values.
     */
    public function calculateDiscounts(): void
    {
        $context = new CalculationContext($this->cart->content()->all());
        $context->appliedCouponCodes = [];
        $context->couponBreakdown = [];
        /** Coupon discount */
        foreach ($this->couponManager->appliedCoupons() as $coupon) {
            if ($coupon->getAppliesTarget() === 'subtotal') {
                if ($coupon->getDiscountType() === 'percentage') {
                    $context->percentSubtotalDiscount += $coupon->getDiscountValue();
                } elseif ($coupon->getDiscountType() === 'subtraction') {
                    $context->fixedSubtotalDiscount += $coupon->getDiscountValue();
                }

                $context->appliedCouponCodes['subtotal'][] = $coupon->getCode();

                $context->couponBreakdown[] = [
                    'code' => $coupon->getCode(),
                    'label' => $coupon->label ?? "คูปอง {$coupon->getCode()}",
                    'type' => $coupon->getDiscountType(),
                    'value' => $coupon->getDiscountValue(),
                    'level' => $coupon->getAppliesTarget(),
                    'allocated' => 0.0,
                ];
            } elseif ($coupon->getAppliesTarget() === 'total') {
                if ($coupon->getDiscountType() === 'percentage') {
                    $context->percentTotalDiscount += $coupon->getDiscountValue();
                } elseif ($coupon->getDiscountType() === 'subtraction') {
                    $context->fixedTotalDiscount += $coupon->getDiscountValue();
                }

                $context->appliedCouponCodes['total'][] = $coupon->getCode();

                $context->couponBreakdown[] = [
                    'code' => $coupon->getCode(),
                    'label' => $coupon->label ?? "คูปอง {$coupon->getCode()}",
                    'type' => $coupon->getDiscountType(),
                    'value' => $coupon->getDiscountValue(),
                    'level' => $coupon->getAppliesTarget(),
                    'allocated' => 0.0,
                ];
            }
        }

        $context = app(Pipeline::class)
            ->send($context)
            ->through([
                ApplyItemsDiscounts::class,
                ApplySubtotalDiscounts::class,
                AllocateSubtotalDiscounts::class,
                ApplyTotalDiscounts::class,
            ])
            ->thenReturn();

        // รวมยอด allocated discount ตามโค้ด
        foreach ($context->items as $item) {
            $code = $item->appliedCouponCode ?? null;
            if (! $code || $item->appliedSubtotalDiscount <= 0) {
                continue;
            }

            foreach ($context->couponBreakdown as &$entry) {
                if ($entry['code'] === $code) {
                    $entry['allocated'] += $item->appliedSubtotalDiscount;
                    break;
                }
            }
        }

        $this->cart->discounts = $context;
    }
}
