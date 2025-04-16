<?php

namespace Soap\ShoppingCart\Pipelines;

class ApplySubtractionSubtotalDiscount
{
    public function handle(CalculationContext $context, \Closure $next)
    {
        // Example fixed discount:
        $discount = 20;
        $before = $context->currentAmount;

        $context->currentAmount = max($context->currentAmount - $discount, 0);
        $context->savings['subtraction_subtotal'] = $before - $context->currentAmount;

        return $next($context);
    }
}
