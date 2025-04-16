<?php

namespace Soap\ShoppingCart\Pipelines;

class ApplyTax
{
    public function handle(CalculationContext $context, \Closure $next)
    {
        // Example fixed tax rate:
        $taxRate = 0.2; // 20%
        $before = $context->currentAmount;

        $context->currentAmount += $context->currentAmount * $taxRate;
        $context->savings['tax'] = $before * $taxRate;

        return $next($context);
    }
}
