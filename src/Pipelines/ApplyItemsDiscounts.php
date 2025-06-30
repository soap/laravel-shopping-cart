<?php

namespace Soap\ShoppingCart\Pipelines;

/**
 * Apply item-level discounts to each cart item
 *
 * This step calculates item-level discounts and prepares subtotal for next step.
 * Uses the same logic as CustomCartItemCalculator for consistency.
 *
 * Handles:
 * - Individual item discount rates (percentage per unit)
 * - Individual item discount amounts (fixed per unit)
 * - Calculates subtotalAfterItemDiscount for each item
 * - Calculates total subtotalAfterItemDiscounts for context
 *
 * Note: This step doesn't check isDiscountable because item-level discounts
 * are applied directly to specific items (not distributed from coupons)
 */
class ApplyItemsDiscounts
{
    public function handle(CalculationContext $context, \Closure $next)
    {
        $subtotalAfterItemDiscounts = 0.0;

        foreach ($context->items as $item) {
            // Get discount values (same logic as CustomCartItemCalculator)
            $price = $item->price;                              // unit price
            $qty = $item->qty;                                  // quantity
            $rate = ($item->discountRate ?? 0) / 100.0;        // convert percentage to fraction
            $fixed = $item->discountAmount ?? 0;               // fixed discount per unit

            // Calculate item-level discount per unit (matches Calculator logic)
            $itemLevelDiscountPerUnit = max(0, $price * $rate + $fixed);

            // Calculate subtotal after item-level discount (matches Calculator)
            $subtotalAfterItemDiscount = max(0, ($price - $itemLevelDiscountPerUnit) * $qty);

            // Store in item for use by Calculator and next pipeline steps
            $item->itemLevelDiscountPerUnit = $itemLevelDiscountPerUnit;
            $item->subtotalAfterItemDiscount = $subtotalAfterItemDiscount;

            // Add to total for context
            $subtotalAfterItemDiscounts += $subtotalAfterItemDiscount;
        }

        // Store the total subtotal after all item-level discounts
        $context->subtotalAfterItemDiscounts = $subtotalAfterItemDiscounts;

        return $next($context);
    }
}
