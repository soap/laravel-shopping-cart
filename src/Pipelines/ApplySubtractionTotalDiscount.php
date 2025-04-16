<?php

namespace Soap\ShoppingCart\Pipelines;

class ApplySubtractionTotalDiscount
{
    public function handle(CalculationContext $context, \Closure $next)
    {
        $discount = 15; // Fixed discount from total adjustment
        $before = $context->currentAmount;

        $context->currentAmount = max($context->currentAmount - $discount, 0);
        $context->savings['subtraction_total'] = $before - $context->currentAmount;

        return $next($context);
    }
}
