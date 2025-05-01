<?php

namespace Soap\ShoppingCart\Pipelines;

class ApplySubtotalDiscounts
{
    public function handle(CalculationContext $context, \Closure $next)
    {
        $percentDiscount = $context->percentSubtotalDiscount ?? 0;
        $fixedDiscount = $context->fixedSubtotalDiscount ?? 0;

        $discountFromPercent = round($context->subtotalAfterItemDiscounts * ($percentDiscount / 100), 2);
        $context->subtotalLevelDiscount = $discountFromPercent + $fixedDiscount;

        $context->subtotalDiscountMetadata = [
            'codes' => $context->appliedCouponCodes ?? [],
            'percent' => $percentDiscount,
            'fixed' => $fixedDiscount,
            'total' => $context->subtotalLevelDiscount,
        ];

        return $next($context);
    }
}
