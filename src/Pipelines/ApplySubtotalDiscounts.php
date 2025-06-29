<?php

namespace Soap\ShoppingCart\Pipelines;

/**
 * Calculate subtotal-level discounts from coupons
 *
 * This step calculates the total discount amount from subtotal-level coupons,
 * but does NOT allocate them to individual items yet.
 *
 * Key features:
 * - Only considers DISCOUNTABLE items for discount calculation base
 * - Processes percentage and fixed amount coupons separately
 * - Respects coupon order and prevents over-discounting
 * - Prepares metadata for tracking and allocation
 *
 * The actual allocation to individual items happens in AllocateSubtotalDiscounts step.
 */
class ApplySubtotalDiscounts
{
    public function handle(CalculationContext $context, \Closure $next)
    {
        // Calculate the discountable subtotal base (only items with isDiscountable = true)
        $discountableSubtotal = $this->calculateDiscountableSubtotal($context->items);
        $nonDiscountableSubtotal = $context->subtotalAfterItemDiscounts - $discountableSubtotal;

        // Track remaining discountable amount as we apply coupons sequentially
        $remainingDiscountableAmount = $discountableSubtotal;

        // Calculate percentage-based discounts first
        $percentageDiscount = 0;
        if ($context->percentSubtotalDiscount > 0 && $discountableSubtotal > 0) {
            $percentageDiscount = $discountableSubtotal * ($context->percentSubtotalDiscount / 100);
            $remainingDiscountableAmount -= $percentageDiscount;
        }

        // Calculate fixed amount discounts (limited by remaining discountable amount)
        $fixedDiscount = 0;
        if ($context->fixedSubtotalDiscount > 0 && $remainingDiscountableAmount > 0) {
            $fixedDiscount = min($context->fixedSubtotalDiscount, $remainingDiscountableAmount);
        }

        // Total subtotal-level discount
        $totalSubtotalDiscount = $percentageDiscount + $fixedDiscount;

        // Store results in context
        $context->subtotalLevelDiscount = $totalSubtotalDiscount;
        $context->subtotalAfterSubtotalDiscounts = $context->subtotalAfterItemDiscounts - $totalSubtotalDiscount;

        // Store detailed metadata for tracking and display
        $context->subtotalDiscountMetadata = [
            'discountable_subtotal' => $discountableSubtotal,
            'non_discountable_subtotal' => $nonDiscountableSubtotal,
            'percentage_discount' => $percentageDiscount,
            'percentage_rate' => $context->percentSubtotalDiscount,
            'fixed_discount' => $fixedDiscount,
            'fixed_amount_requested' => $context->fixedSubtotalDiscount,
            'total_subtotal_discount' => $totalSubtotalDiscount,
            'remaining_after_percentage' => max(0, $remainingDiscountableAmount + $fixedDiscount), // before fixed discount
            'final_discountable_amount' => max(0, $remainingDiscountableAmount),
        ];

        return $next($context);
    }

    /**
     * Calculate subtotal of items that can receive subtotal-level discounts
     *
     * Only items with isDiscountable = true are included in the calculation base.
     * Uses subtotalAfterItemDiscount to respect item-level discounts already applied.
     */
    private function calculateDiscountableSubtotal(array $items): float
    {
        $discountableSubtotal = 0;

        foreach ($items as $item) {
            // Only include items that can receive subtotal-level discounts
            if ($item->isDiscountable) {
                // Use the subtotal after item-level discounts have been applied
                $discountableSubtotal += $item->subtotalAfterItemDiscount ?? ($item->qty * $item->price);
            }
        }

        return $discountableSubtotal;
    }
}
