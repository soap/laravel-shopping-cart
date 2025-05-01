<?php

namespace Soap\ShoppingCart\Calculation;

use Soap\ShoppingCart\ShoppingCart;

class CartCalculator
{
    public function calculate(ShoppingCart $cart): void
    {
        $context = $cart->discounts;

        // คำนวณ netSubtotal: ยอดรวมหลังหักส่วนลดระดับรายการและยอดรวมย่อย
        $context->netSubtotal = max(
            $context->subtotalAfterItemDiscounts - $context->subtotalLevelDiscount,
            0.0
        );

        // คำนวณ taxAmount: ภาษีที่คำนวณจาก netSubtotal และอัตราภาษีของแต่ละรายการ
        $context->taxAmount = $cart->taxFloat();

        // คำนวณ grossTotalBeforeTotalDiscount: ยอดรวมก่อนหักส่วนลดระดับยอดรวมทั้งหมด
        $context->grossTotalBeforeTotalDiscount = $context->netSubtotal + $context->taxAmount + $context->shipping;

        // คำนวณ totalLevelDiscount: ส่วนลดระดับยอดรวมทั้งหมด
        $context->totalLevelDiscount = min(
            $context->fixedTotalDiscount + ($context->grossTotalBeforeTotalDiscount * ($context->percentTotalDiscount / 100)),
            $context->grossTotalBeforeTotalDiscount
        );

        // คำนวณ totalAfterDiscounts: ยอดรวมหลังหักส่วนลดระดับยอดรวมทั้งหมด
        $context->totalAfterDiscounts = max(
            $context->grossTotalBeforeTotalDiscount - $context->totalLevelDiscount,
            0.0
        );

        $context->totalDiscountMetadata = [
            'codes' => $context->appliedCouponCodes['total'] ?? [],
            'percent' => $context->percentTotalDiscount,
            'fixed' => $context->fixedTotalDiscount,
            'total' => $context->totalLevelDiscount,
        ];

        // อัปเดตค่าคำนวณกลับไปยังตะกร้าสินค้าข
        $cart->discounts = $context;
    }
}
