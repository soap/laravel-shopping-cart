<?php

namespace Soap\ShoppingCart\Pipelines;

class CalculationContext
{
    /**
     * @var array รายการ CartItem ทั้งหมด
     */
    public array $items = [];

    /**
     * ส่วนลดเปอร์เซ็นต์ที่ระดับ subtotal (รวมจากหลายคูปอง)
     */
    public float $percentSubtotalDiscount = 0.0;

    /**
     * ส่วนลดคงที่ที่ระดับ subtotal (รวมจากหลายคูปอง)
     */
    public float $fixedSubtotalDiscount = 0.0;

    /**
     * โค้ดของคูปองที่ถูก apply แล้ว (หลายใบ)
     */
    public array $appliedCouponCodes = [];

    /**
     * ส่วนลดรวมจาก subtotal ทั้งหมด (คำนวณจาก % + fixed)
     */
    public float $subtotalLevelDiscount = 0.0;

    /**
     * สรุปรายละเอียดของคูปอง (สำหรับ metadata)
     */
    public array $subtotalDiscountMetadata = [];

    /**
     * ยอดรวมหลังลดรายการแต่ละชิ้น (ก่อนลด subtotal-level)
     */
    public float $subtotalAfterItemDiscounts = 0.0;

    /**
     * ยอดรวมหลังลดทุกอย่าง
     */
    public float $subtotalAfterSubtotalDiscounts = 0.0;

    /**
     * ยอดรวมหลังลดทุกอย่าง (รวมค่าขนส่ง)
     */
    public array $couponBreakdown = [];

    /**
     * สำหรับคำนวณค่าขนส่ง / ภาษีภายนอก
     */
    public float $shipping = 0.0;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }
}
