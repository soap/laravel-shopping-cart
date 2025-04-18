<?php

use Illuminate\Pipeline\Pipeline;
use Soap\ShoppingCart\Calculation\CustomCartItemCalculator;
use Soap\ShoppingCart\CartItem;
use Soap\ShoppingCart\Pipelines\AllocateSubtotalDiscounts;
use Soap\ShoppingCart\Pipelines\ApplyItemsDiscounts;
use Soap\ShoppingCart\Pipelines\ApplySubtotalDiscounts;
use Soap\ShoppingCart\Pipelines\CalculationContext;

it('can calculate correctly for subtotal discount only', function () {
    config()->set('shopping-cart.calculator', CustomCartItemCalculator::class);
    // Mock cart items
    $items = [
        CartItem::fromArray([
            'id' => 1,
            'name' => 'Item A',
            'price' => 100,
        ]),
        CartItem::fromArray([
            'id' => 2,
            'name' => 'Item B',
            'price' => 200,
        ]),
    ];
    $items[0]->setQuantity(2)->setTaxRate(0); // Item A quantity and tax rate
    $items[1]->setQuantity(1)->setTaxRate(0); // Item B quantity and tax rate

    // Prepare calculation context with coupons
    $context = new CalculationContext($items);
    $context->percentSubtotalDiscount = 5; // 5% coupon
    $context->fixedSubtotalDiscount = 30;  // 30 fixed coupon
    $context->appliedCouponCodes = ['SAVE5'];

    $result = app(Pipeline::class)
        ->send($context)
        ->through([
            ApplyItemsDiscounts::class,
            ApplySubtotalDiscounts::class,
            AllocateSubtotalDiscounts::class,
        ])
        ->thenReturn();

    $itemA = $result->items[0];
    $itemB = $result->items[1];

    // Subtotals before any discounts
    expect($itemA->originalSubtotal)->toBe(200.00);
    expect($itemB->originalSubtotal)->toBe(200.00);

    // No item-level discounts
    expect($itemA->subtotalAfterItemDiscount)->toBe(200.00);
    expect($itemB->subtotalAfterItemDiscount)->toBe(200.00);

    // Final subtotal should be lower due to coupon allocation
    expect($itemA->finalSubtotal)->toBeLessThan($itemA->subtotalAfterItemDiscount);
    expect($itemB->finalSubtotal)->toBeLessThan($itemB->subtotalAfterItemDiscount);

    $breakdownA = $itemA->discountBreakdown; // Get discount breakdown for item A from calculator
    $breakdownB = $itemB->discountBreakdown; // Get discount breakdown for item B from calculator

    expect(collect($breakdownA)->where('type', 'subtotal'))->toHaveCount(1);
    expect(collect($breakdownB)->where('type', 'subtotal'))->toHaveCount(1);

    expect(collect($breakdownA)->firstWhere('type', 'subtotal')['coupon_code'])->toBe('SAVE5');
    expect(collect($breakdownB)->firstWhere('type', 'subtotal')['coupon_code'])->toBe('SAVE5');
});
