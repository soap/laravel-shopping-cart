<?php

namespace Soap\ShoppingCart\Pipelines;

class AllocateSubtotalDiscounts
{
    protected DiscountAllocator $allocator;

    public function __construct(DiscountAllocator $allocator)
    {
        $this->allocator = $allocator;
    }

    public function handle(CalculationContext $context, \Closure $next)
    {
        if ($context->subtotalLevelDiscount > 0) {
            $allocated = $this->allocator->allocate($context->items, $context->subtotalLevelDiscount);

            foreach ($context->items as $item) {
                $item->proportionalWeight = $context->subtotalAfterItemDiscounts > 0
                    ? $item->subtotalAfterItemDiscount / $context->subtotalAfterItemDiscounts
                    : 0;

                $item->appliedSubtotalDiscount = $allocated[$item->id] ?? 0;
                $item->subtotalLevelDiscountTotal = $item->appliedSubtotalDiscount;
                $item->appliedCouponCode = $context->appliedCouponCodes[0] ?? null;
            }

            $context->subtotalAfterSubtotalDiscounts = collect($context->items)->sum(function ($item) {
                return $item->finalSubtotal;
            });
        } else {
            foreach ($context->items as $item) {
                $item->proportionalWeight = 0;
                $item->appliedSubtotalDiscount = 0;
                $item->subtotalLevelDiscountTotal = 0;
                $item->appliedCouponCode = null;
            }

            $context->subtotalAfterSubtotalDiscounts = $context->subtotalAfterItemDiscounts;
        }

        return $next($context);
    }
}
