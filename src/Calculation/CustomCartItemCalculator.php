<?php

namespace Soap\ShoppingCart\Calculation;

use Soap\ShoppingCart\Contracts\CalculatorInterface;

class CustomCartItemCalculator implements CalculatorInterface
{
    public static function getAttribute(string $attribute, $item): mixed
    {
        $price = $item->price;
        $qty = $item->qty;
        $rate = $item->discountRate ?? 0;
        $fixed = $item->discountAmount ?? 0;
        $applied = $item->appliedSubtotalDiscount ?? 0;

        return match ($attribute) {
            // Subtotal before any discount
            'initialSubtotal' => $price * $qty,
            // Subtotal after item-level discount
            'subtotalAfterItemDiscount' => max(0, $price - ($price * $rate) - $fixed) * $qty,
            // Subtotal after all discounts for this item used to calculate tax
            'taxableSubtotal' => max(0, $price - ($price * $rate) - $fixed - $applied) * $qty,
            // Tax amount of this item
            'tax' => self::getAttribute('taxableSubtotal', $item) * ($item->taxRate / 100),
            // total discount for item level
            'itemLevelDiscountTotal' => ($price * $qty) - self::getAttribute('subtotalAfterItemDiscount', $item),

            'subtotalLevelDiscountTotal' => $applied,

            'totalDiscount' => self::getAttribute('itemLevelDiscountTotal', $item) + $applied,

            'finalSubtotal' => self::getAttribute('subtotalAfterItemDiscount', $item) - $applied,

            'discountBreakdown' => array_filter([
                [
                    'label' => 'ส่วนลดรายการสินค้า',
                    'amount' => self::getAttribute('itemLevelDiscountTotal', $item),
                    'type' => 'item',
                ],
                $applied > 0 ? [
                    'label' => 'ส่วนลดคูปอง'.($item->appliedCouponCode ? " ({$item->appliedCouponCode})" : ''),
                    'amount' => $applied,
                    'type' => 'subtotal',
                    'coupon_code' => $item->appliedCouponCode ?? null,
                ] : null,
            ]),
            default => null,
        };
    }
}
