<?php

namespace Soap\ShoppingCart;

use Illuminate\Pipeline\Pipeline;
use Soap\ShoppingCart\Pipelines\AllocateSubtotalDiscounts;
use Soap\ShoppingCart\Pipelines\ApplyItemsDiscounts;
use Soap\ShoppingCart\Pipelines\ApplySubtotalDiscounts;
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

    public function calculation(): void
    {
        $context = new CalculationContext($this->cart->content()->all());
        $context->appliedCouponCodes = [];
        $context->couponBreakdown = [];

        foreach ($this->couponManager->getAppliedCoupons() as $coupon) {
            if ($coupon->applies_to === 'subtotal') {
                if ($coupon->type === 'percent') {
                    $context->percentSubtotalDiscount += $coupon->value;
                } elseif ($coupon->type === 'fixed') {
                    $context->fixedSubtotalDiscount += $coupon->value;
                }

                $context->appliedCouponCodes[] = $coupon->code;

                $context->couponBreakdown[] = [
                    'code' => $coupon->code,
                    'label' => $coupon->label ?? "คูปอง {$coupon->code}",
                    'type' => $coupon->type,
                    'value' => $coupon->value,
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
