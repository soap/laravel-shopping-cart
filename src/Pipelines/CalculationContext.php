<?php

namespace Soap\ShoppingCart\Pipelines;

class CalculationContext
{
    /**
     * 🛒 รายการสินค้าในตะกร้า
     *
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
    public array $appliedCouponCodes = [
        'item' => [],
        'subtotal' => [],
        'total' => [],
    ];

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

    public float $netSubtotal = 0.0;

    public float $taxAmount = 0.0;

    public float $grossTotalBeforeTotalDiscount = 0.0;

    /**
     * ยอดรวมหลังลดทุกอย่าง (รวมค่าขนส่ง)
     */
    public array $couponBreakdown = [];

    public float $totalAfterDiscounts = 0.0;

    /**
     * สำหรับคำนวณค่าขนส่ง / ภาษีภายนอก
     */
    public float $shipping = 0.0;

    /**
     * ส่วนลดเปอร์เซ็นต์ที่ระดับ total (จากคูปองหลายใบ)
     */
    public float $percentTotalDiscount = 0.0;

    /**
     * ส่วนลดคงที่ที่ระดับ total (จากคูปองหลายใบ)
     */
    public float $fixedTotalDiscount = 0.0;

    /**
     * ส่วนลดรวมจาก total-level (ใช้ในขั้นคำนวณ)
     */
    public float $totalLevelDiscount = 0.0;

    /**
     * รายละเอียดของคูปองระดับ total (metadata)
     */
    public array $totalDiscountMetadata = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }
}
