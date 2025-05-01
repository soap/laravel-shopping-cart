<?php

namespace Soap\ShoppingCart\Pipelines;

class ApplyTotalDiscounts
{
    public function handle(CalculationContext $context, \Closure $next): CalculationContext
    {
        $base = $context->grossTotalBeforeTotalDiscount
            ?? $context->netSubtotal + $context->shipping + $context->taxAmount;

        $discount = 0.0;

        if ($context->percentTotalDiscount > 0) {
            $discount += $base * ($context->percentTotalDiscount / 100);
        }

        if ($context->fixedTotalDiscount > 0) {
            $discount += $context->fixedTotalDiscount;
        }

        // ห้ามให้ discount เกินฐานคำนวณ
        $context->totalLevelDiscount = min($discount, $base);

        return $next($context);
    }
}
