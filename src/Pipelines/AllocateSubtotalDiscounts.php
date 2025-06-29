<?php

namespace Soap\ShoppingCart\Pipelines;

/**
 * Updated AllocateSubtotalDiscounts pipeline step
 *
 * Now properly handles isDiscountable and fixes bugs in the original implementation
 */
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
            // Use the updated allocator that respects isDiscountable
            $allocated = $this->allocator->allocate($context->items, $context->subtotalLevelDiscount);

            // Get subtotal coupon code for items that receive discount
            $subtotalCoupons = $context->appliedCouponCodes['subtotal'] ?? [];
            $primaryCouponCode = $subtotalCoupons[0] ?? null;
            foreach ($context->items as $item) {
                // Calculate proportional weight for tracking
                $item->proportionalWeight = $context->subtotalAfterItemDiscounts > 0
                    ? $item->subtotalAfterItemDiscount / $context->subtotalAfterItemDiscounts
                    : 0;

                // Apply allocated discount (0 for non-discountable items)
                $item->appliedSubtotalDiscount = $allocated[$item->id] ?? 0;
                $item->subtotalLevelDiscountTotal = $item->appliedSubtotalDiscount;

                // Only set coupon code for items that actually received discount
                if ($item->appliedSubtotalDiscount > 0) {
                    $item->appliedCouponCode = $primaryCouponCode;
                } else {
                    $item->appliedCouponCode = null;
                }
            }

            // Calculate subtotal after subtotal discounts
            $context->subtotalAfterSubtotalDiscounts = $context->subtotalAfterItemDiscounts - $context->subtotalLevelDiscount;

            // Update coupon breakdown with allocated amounts
            $this->updateCouponBreakdown($context, $context->subtotalLevelDiscount);

        } else {
            // No subtotal discount to allocate
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

    /**
     * Update coupon breakdown to reflect allocated discount amounts
     */
    private function updateCouponBreakdown(CalculationContext $context, float $totalAllocated): void
    {
        foreach ($context->couponBreakdown as &$couponEntry) {
            if (in_array($couponEntry['code'], $context->appliedCouponCodes['subtotal'] ?? [])) {
                $couponEntry['allocated'] = $totalAllocated;
            }
        }
    }
}
