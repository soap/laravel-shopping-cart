<?php

namespace Soap\ShoppingCart\Pipelines;

/**
 * Updated DiscountAllocator to handle isDiscountable property
 * 
 * Only allocates discounts to items that are discountable.
 * Non-discountable items will receive 0 discount allocation.
 */
class DiscountAllocator
{
    /**
     * @param  array  $items  array of CartItem รายการที่ต้องการแจกจ่ายส่วนลด
     * @param  float  $netSubtotalDiscount  ส่วนลดระดับ subtotal ที่ต้องแจกจ่าย
     * @return array [item_id => discountAmount]
     */
    public function allocate(array $items, float $netSubtotalDiscount): array
    {
        $result = [];
        
        // Filter only discountable items for allocation base
        $discountableItems = array_filter($items, fn($item) => $item->isDiscountable);
        
        // Calculate total base from discountable items only
        $totalBase = collect($discountableItems)->sum(function ($item) {
            return $item->subtotalAfterItemDiscount; // จาก CustomCartItemCalculator
        });

        if ($totalBase <= 0 || $netSubtotalDiscount <= 0 || empty($discountableItems)) {
            // Return zero allocation for all items
            return array_fill_keys(array_map(fn ($item) => $item->id, $items), 0);
        }

        $remaining = $netSubtotalDiscount;
        $lastDiscountableId = end($discountableItems)->id;

        // Initialize all items with 0 discount
        foreach ($items as $item) {
            $result[$item->id] = 0;
        }

        // Allocate proportionally to discountable items only
        foreach ($discountableItems as $item) {
            $proportion = $item->subtotalAfterItemDiscount / $totalBase;
            $allocated = round($netSubtotalDiscount * $proportion, 2);
            $result[$item->id] = $allocated;
            $remaining -= $allocated;
        }

        // Add any remaining rounding difference to the last discountable item
        if (abs($remaining) > 0 && isset($result[$lastDiscountableId])) {
            $result[$lastDiscountableId] += $remaining;
        }

        return $result;
    }
}
