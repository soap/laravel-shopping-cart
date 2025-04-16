<?php

namespace Soap\ShoppingCart\Pipelines;

class ApplyPercentageTotalDiscount
{
    public function handle(CalculationContext $context, \Closure $next)
    {
        // Example fixed discount:
        $discount = 10; // 10% discount from total adjustment
        $before = $context->currentAmount;

        $context->currentAmount -= $context->currentAmount * ($discount / 100);
        $context->savings['percentage_total'] = $before - $context->currentAmount;

        return $next($context);
    }
}
