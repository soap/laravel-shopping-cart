<?php

namespace Soap\ShoppingCart\Pipelines;

class DiscountAllocator
{
    /**
     * @param  array  $items  array of CarItem รายการที่ต้องการแจกจ่ายส่วนลด
     * @param  float  $netSubtotalDiscount  ส่วนลดระดับ subtotal ที่ต้องแจกจ่าย
     * @return array [item_id => discountAmount]
     */
    public function allocate(array $items, float $netSubtotalDiscount): array
    {
        $result = [];
        $totalBase = collect($items)->sum(function ($item) {
            return $item->subtotalAfterItemDiscount; // จาก CustomCartItemCalculator
        });
        // ray('totalBase', $totalBase);
        // ray('netSubtotalDiscount', $netSubtotalDiscount);
        if ($totalBase <= 0 || $netSubtotalDiscount <= 0) {
            return array_fill_keys(array_map(fn ($item) => $item->id, $items), 0);
        }

        $remaining = $netSubtotalDiscount;
        $lastId = end($items)->id;

        foreach ($items as $item) {
            $proportion = $item->subtotalAfterItemDiscount / $totalBase;
            $allocated = round($netSubtotalDiscount * $proportion, 2);
            $result[$item->id] = $allocated;
            $remaining -= $allocated;
        }

        // ปรับปรุงผลลัพธ์ให้แน่ใจว่าไม่มีการสูญเสียจากการปัดเศษ
        // Add any remaining rounding difference to the last item
        if (abs($remaining) > 0 && isset($result[$lastId])) {
            $result[$lastId] += $remaining;
        }

        return $result;
    }
}
