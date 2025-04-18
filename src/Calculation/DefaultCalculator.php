<?php

namespace Soap\ShoppingCart\Calculation;

use Soap\ShoppingCart\CartItem;
use Soap\ShoppingCart\Contracts\CalculatorInterface;

class DefaultCalculator implements CalculatorInterface
{
    public static function getAttribute(string $attribute, CartItem $cartItem)
    {
        $decimals = config('shopping-cart.format.decimals', 2);

        switch ($attribute) {
            case 'discount':
                // ส่วนลดที่คำนวณจากราคาปิด
                return $cartItem->price * ($cartItem->getDiscountRate() / 100);
            case 'tax':
                return round($cartItem->priceTarget * ($cartItem->taxRate / 100), $decimals);
            case 'priceTax':
                // ราคาสุทธิ + ภาษี
                return round($cartItem->priceTarget + $cartItem->tax, $decimals);
            case 'discountTotal':
                // ส่วนลดรวม
                return round($cartItem->discount * $cartItem->qty, $decimals);
            case 'priceTotal':
                // ราคารวม
                return round($cartItem->price * $cartItem->qty, $decimals);
            case 'subtotal':
                // ราคาสุทธิ หลังหักส่วนลดแล้ว
                return max(round($cartItem->priceTotal - $cartItem->discountTotal, $decimals), 0);
            case 'priceTarget':
                // ราคาต่อหน่วยสุทธิ หลังหักส่วนลดแล้ว
                return round(($cartItem->priceTotal - $cartItem->discountTotal) / $cartItem->qty, $decimals);
            case 'taxTotal':
                // ภาษีรวมต่อรายการ
                return round($cartItem->subtotal * ($cartItem->taxRate / 100), $decimals);
            case 'total':
                // ราคารวมต่อรายการ + ภาษี
                return round($cartItem->subtotal + $cartItem->taxTotal, $decimals);
            default:
                return;
        }
    }
}
