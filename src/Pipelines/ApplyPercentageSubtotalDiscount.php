<?php

namespace Soap\ShoppingCart\Pipelines;

class ApplyPercentageSubtotalDiscount
{
    public function handle(CalculationContext $context, \Closure $next)
    {
        // Example fixed discount:
        $discount = 10; // 10% discount from subtotal adjustment
        $before = $context->currentAmount;

        $context->currentAmount -= $context->currentAmount * ($discount / 100);
        $context->savings['percentage_subtotal'] = $before - $context->currentAmount;

        return $next($context);
    }
}
