<?php

use Soap\ShoppingCart\CartItem;

it('can be cast to an array', function () {
    $cartItem = new CartItem(1, 'Some item', 10.00, 550, ['size' => 'XL', 'color' => 'red']);
    $cartItem->setQuantity(2);

    expect($cartItem->toArray())->toEqual([
        'id' => 1,
        'name' => 'Some item',
        'price' => 10.00,
        'rowId' => '07d5da5550494c62daf9993cf954303f',
        'qty' => 2,
        'options' => [
            'size' => 'XL',
            'color' => 'red',
        ],
        'tax' => 0,
        'subtotal' => 20.00,
        'discount' => 0.0,
        'weight' => 550.0,
    ]);
});

it('can be cast to JSON', function () {
    $cartItem = new CartItem(1, 'Some item', 10.00, 550, ['size' => 'XL', 'color' => 'red']);
    $cartItem->setQuantity(2);

    expect($cartItem->toJson())->toBeJson();

    $json = '{"rowId":"07d5da5550494c62daf9993cf954303f","id":1,"name":"Some item","qty":2,"price":10,"weight":550,"options":{"size":"XL","color":"red"},"discount":0,"tax":0,"subtotal":20}';

    expect($cartItem->toJson())->toBe($json);
});

it('can format total price correctly', function () {
    $cartItem = new CartItem(1, 'Some item', 10.00, 550, ['size' => 'XL', 'color' => 'red']);
    $cartItem->setQuantity(2);

    expect($cartItem->priceTotal())->toBe('20.00');
});
