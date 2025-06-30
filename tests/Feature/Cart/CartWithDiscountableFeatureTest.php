<?php

describe('Updated DiscountAllocator with isDiscountable', function () {
    beforeEach(function () {
        $this->allocator = new \Soap\ShoppingCart\Pipelines\DiscountAllocator;

        $this->items = [
            new \Soap\ShoppingCart\CartItem('boat_001', 'ค่าเรือสปีดโบ๊ท', 1000, 0, [], true),
            new \Soap\ShoppingCart\CartItem('food_001', 'อาหารกลางวัน', 500, 0, [], true),
            new \Soap\ShoppingCart\CartItem('insurance_001', 'ประกันการเดินทาง', 200, 0, [], false),
            new \Soap\ShoppingCart\CartItem('fee_001', 'ค่าธรรมเนียมอุทยาน', 100, 0, [], false),
        ];

        // Simulate subtotalAfterItemDiscount from previous steps
        $this->items[0]->setQuantity(2);
        $this->items[0]->subtotalAfterItemDiscount = 1800; // After 10% item discount

        $this->items[1]->setQuantity(4);
        $this->items[1]->subtotalAfterItemDiscount = 1800; // After 50 THB item discount

        $this->items[2]->setQuantity(2);
        $this->items[2]->subtotalAfterItemDiscount = 400; // No item discount

        $this->items[3]->setQuantity(1);
        $this->items[3]->subtotalAfterItemDiscount = 95; // After 5% item discount
    });

    test('allocates discount only to discountable items', function () {
        $result = $this->allocator->allocate($this->items, 740);

        // Discountable base: 1800 + 1800 = 3600
        // Boat: (1800/3600) * 740 = 370
        // Food: (1800/3600) * 740 = 370
        expect($result['boat_001'])->toBe(370.0);
        expect($result['food_001'])->toBe(370.0);
        expect($result['insurance_001'])->toBe(0); // Non-discountable
        expect($result['fee_001'])->toBe(0); // Non-discountable

        // Total allocated should equal discount amount
        expect(array_sum($result))->toBe(740.0);
    });

    test('handles zero discount amount', function () {
        $result = $this->allocator->allocate($this->items, 0);

        foreach ($result as $allocation) {
            expect($allocation)->toBe(0);
        }
    });

    test('handles cart with no discountable items', function () {
        // Make all items non-discountable
        foreach ($this->items as $item) {
            $item->isDiscountable = false;
        }

        $result = $this->allocator->allocate($this->items, 500);

        foreach ($result as $allocation) {
            expect($allocation)->toBe(0);
        }
    });

    test('handles rounding precision correctly', function () {
        // Use amount that creates rounding issues
        $result = $this->allocator->allocate($this->items, 100.15);

        $boatAllocation = $result['boat_001'];
        $foodAllocation = $result['food_001'];

        // Should be rounded to 2 decimal places
        expect(round($boatAllocation, 2))->toBe(51.99); // Expected rounded value for boat allocation
        expect(round($foodAllocation, 2))->toBe(48.16); // Expected rounded value for food allocation

        // Total should equal exactly the discount amount (accounting for rounding adjustment)
        expect(round($boatAllocation + $foodAllocation, 2))->toBe(100.15);
    });
});

describe('Updated AllocateSubtotalDiscounts Pipeline Step', function () {
    beforeEach(function () {
        $this->allocator = new \Soap\ShoppingCart\Pipelines\DiscountAllocator;
        $this->step = new \Soap\ShoppingCart\Pipelines\AllocateSubtotalDiscounts($this->allocator);

        $this->items = [
            new \Soap\ShoppingCart\CartItem('boat_001', 'ค่าเรือสปีดโบ๊ท', 1000, 0, [], true),
            new \Soap\ShoppingCart\CartItem('food_001', 'อาหารกลางวัน', 500, 0, [], true),
            new \Soap\ShoppingCart\CartItem('insurance_001', 'ประกันการเดินทาง', 200, 0, [], false),
        ];

        $this->items[0]->setQuantity(2);
        $this->items[0]->subtotalAfterItemDiscount = 1800;
        $this->items[1]->setQuantity(4);
        $this->items[1]->subtotalAfterItemDiscount = 1800;
        $this->items[2]->setQuantity(2);
        $this->items[2]->subtotalAfterItemDiscount = 400;

        $this->context = new \Soap\ShoppingCart\Pipelines\CalculationContext($this->items);
        $this->context->subtotalAfterItemDiscounts = 4000;
        $this->context->subtotalLevelDiscount = 600;
        $this->context->appliedCouponCodes = [
            'subtotal' => ['EARLY20', 'SAVE100'],
        ];
        $this->context->couponBreakdown = [
            [
                'code' => 'EARLY20',
                'label' => 'Early Bird 20%',
                'type' => 'percentage',
                'value' => 20,
                'level' => 'subtotal',
                'allocated' => 0.0,
            ],
        ];
    });

    test('properly integrates with discount allocator and updates context', function () {
        $result = $this->step->handle($this->context, fn ($ctx) => $ctx);

        // Check item allocations
        expect($this->items[0]->appliedSubtotalDiscount)->toBe(300.0); // (1800/3600) * 600
        expect($this->items[1]->appliedSubtotalDiscount)->toBe(300.0); // (1800/3600) * 600
        expect($this->items[2]->appliedSubtotalDiscount)->toBe(0); // Non-discountable

        // Check coupon codes
        expect($this->items[0]->appliedCouponCode)->toBe('EARLY20');
        expect($this->items[1]->appliedCouponCode)->toBe('EARLY20');
        expect($this->items[2]->appliedCouponCode)->toBeNull();

        // Check context update
        expect($result->subtotalAfterSubtotalDiscounts)->toBe(3400.0); // 4000 - 600

        // Check coupon breakdown update
        expect($result->couponBreakdown[0]['allocated'])->toBe(600.0);
    });

    test('handles zero subtotal discount correctly', function () {
        $this->context->subtotalLevelDiscount = 0;

        $result = $this->step->handle($this->context, fn ($ctx) => $ctx);

        foreach ($this->items as $item) {
            expect($item->appliedSubtotalDiscount)->toBe(0);
            expect($item->appliedCouponCode)->toBeNull();
        }

        expect($result->subtotalAfterSubtotalDiscounts)->toBe(4000.0); // No change
    });
});
