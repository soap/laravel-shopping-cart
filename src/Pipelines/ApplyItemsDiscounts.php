<?php

namespace Soap\ShoppingCart\Pipelines;

class ApplyItemsDiscounts
{
    /**
     * ทำหน้าที่หาส่วนลดที่ใช้กับรายการสินค้าจากคูปอง หรือเงื่อนไขอื่นๆ ที่เกิดขึ้นในระดับรายการสินค้า
     * ส่วนการคำนวณส่วนลดในระดับรายการสินค้าจะถูกคำนวณใน CustomCartItemCalculator
     *
     * @return \Soap\ShoppingCart\Pipelines\CalculationContext
     */
    public function handle(CalculationContext $context, \Closure $next)
    {
        foreach ($context->items as $item) {
            // $item->discountRate =
            // $item->discountAmount =
        }
        // คำนวณ subtotal หลังจากลดรายการสินค้าแต่ละรายการแล้ว
        $context->subtotalAfterItemDiscounts = collect($context->items)->sum(function ($item) {
            return $item->subtotalAfterItemDiscount; // จาก CustomCartItemCalculator
        });

        return $next($context);
    }
}
