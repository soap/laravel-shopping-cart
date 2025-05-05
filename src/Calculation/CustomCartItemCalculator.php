<?php

namespace Soap\ShoppingCart\Calculation;

use Soap\ShoppingCart\Contracts\CalculatorInterface;

/**
 * This class provides dynamic attribute calculation for a CartItem.
 * Used to calculate derived fields like subtotal, tax, discount, etc.
 */
class CustomCartItemCalculator implements CalculatorInterface
{
    public static function getAttribute(string $attribute, $item): mixed
    {
        $decimals = config('shopping-cart.format.decimals', 2);

        // Basic values
        $price = $item->price;                // unit price before discount
        $qty = $item->qty;                    // quantity
        $rate = ($item->discountRate ?? 0) / 100.0; // item-level discount percentage (converted to fraction)
        $fixed = $item->discountAmount ?? 0;  // fixed discount amount per unit
        $applied = $item->appliedSubtotalDiscount ?? 0; // discount from subtotal-level allocation

        // Item-level discount (before subtotal discount), per unit
        $itemLevelDiscount = max(0, $price * $rate + $fixed);

        // Subtotal after item-level discount
        $subtotalAfterItemDiscount = max(0, ($price - $itemLevelDiscount) * $qty);

        // Final subtotal = after all discounts (item + subtotal level)
        $finalSubtotal = max(0, $subtotalAfterItemDiscount - $applied);

        // Price per unit after all discounts (target price)
        $priceTarget = $qty > 0 ? round($finalSubtotal / $qty, $decimals) : 0;

        // Tax per unit (if tax rate is applied on priceTarget)
        $tax = round($priceTarget * ($item->taxRate / 100), $decimals);

        // Total tax for the line
        $taxTotal = round($tax * $qty, $decimals);

        // Unit discount = total discount per unit (item + subtotal-level)
        $unitDiscount = $qty > 0
            ? round($itemLevelDiscount - ($applied / $qty), $decimals)
            : 0;

        // Return value based on requested attribute
        return match ($attribute) {
            // Total price before discount
            'priceTotal', 'initialSubtotal' => round($price * $qty, $decimals),

            // Subtotal after item-level discount only
            'subtotalAfterItemDiscount' => $subtotalAfterItemDiscount,

            // Discount from subtotal-level coupon (already allocated to this item)
            'subtotalLevelDiscountTotal' => $applied,

            // Final subtotal = subtotalAfterItemDiscount - subtotalLevelDiscount
            'finalSubtotal', 'subtotal' => $finalSubtotal,

            // Final price per unit (after all discounts)
            'priceTarget' => $priceTarget,

            // Tax per unit
            'tax' => $tax,

            // Price per unit + tax
            'priceTax' => round($priceTarget + $tax, $decimals),

            // Total tax for the line
            'taxTotal' => $taxTotal,

            // Discount per unit (item + subtotal-level)
            'unitDiscount' => $unitDiscount,

            // Total discount from item-level only
            'itemLevelDiscountTotal' => round(($price * $qty) - $subtotalAfterItemDiscount, $decimals),

            // Total discount from all levels
            'totalDiscount' => round(($price * $qty) - $finalSubtotal, $decimals),

            // Same as unitDiscount
            'discount' => $unitDiscount,

            // Total discount (same as totalDiscount)
            'discountTotal' => round(($price * $qty) - $finalSubtotal, $decimals),

            // Final total = subtotal + tax
            'total' => round($finalSubtotal + $taxTotal, $decimals),

            // Breakdown array for display purposes (item-level and coupon)
            'discountBreakdown' => array_filter([
                [
                    'label' => 'ส่วนลดรายการสินค้า',
                    'amount' => round(($price * $qty) - $subtotalAfterItemDiscount, $decimals),
                    'type' => 'item',
                ],
                $applied > 0 ? [
                    'label' => 'ส่วนลดคูปอง'.($item->appliedCouponCode ? " ({$item->appliedCouponCode})" : ''),
                    'amount' => round($applied, $decimals),
                    'type' => 'subtotal',
                    'coupon_code' => $item->appliedCouponCode ?? null,
                ] : null,
            ]),

            // Fallback for undefined fields
            default => null,
        };
    }
}
