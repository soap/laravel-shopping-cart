<?php

use Illuminate\Support\Facades\Event;
use Soap\ShoppingCart\ShoppingCart;
use Soap\ShoppingCart\Tests\Fixtures\BuyableProduct;

beforeEach(function () {
    $this->app->make('config')->set('shopping-cart', [
        'destroy_on_logout' => true,
        'format' => [
            'decimals' => 2,
            'decimal_point' => '.',
            'thousand_seperator' => ',',
        ],
    ]);

});

it('has default instance', function () {
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');

    $cart = new ShoppingCart($session, $dispatcher);
    expect($cart->currentInstance())->toBe(ShoppingCart::DEFAULT_INSTANCE);
});

it('can has multiple instances', function () {
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');

    $cart = new ShoppingCart($session, $dispatcher);
    $cart->add(new BuyableProduct([
        'id' => 1,
        'name' => 'First item',
    ]));

    $cart->instance('wishlist')->add(new BuyableProduct([
        'id' => 2,
        'name' => 'Second item',
    ]));

    expect($cart->instance('wishlist')->count())->toBe(1);
    expect($cart->instance(ShoppingCart::DEFAULT_INSTANCE)->count())->toBe(1);
});

it('can add item to cart', function () {
    Event::fake();
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');

    $cart = new ShoppingCart($session, $dispatcher);
    $cart->add(new BuyableProduct([
        'id' => 1,
        'name' => 'First item',
    ]));

    expect($cart->count())->toBe(1);
    Event::assertDispatched('cart.added');
});

it('returns the cartItem of the added item', function () {
    Event::fake();
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');

    $cart = new ShoppingCart($session, $dispatcher);
    $cartItem = $cart->add(new BuyableProduct([
        'id' => 1,
        'name' => 'First item',
    ]));

    expect($cartItem)->toBeInstanceOf(\Soap\ShoppingCart\CartItem::class);
    expect($cartItem->rowId)->toEqual('027c91341fd5cf4d2579b49c4b6a90da');
    Event::assertDispatched('cart.added');
});

it('can add multiple buyable items at once', function () {
    Event::fake();
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');

    $cart = new ShoppingCart($session, $dispatcher);
    $cart->add([
        new BuyableProduct([
            'id' => 1,
            'name' => 'First item',
        ]),
        new BuyableProduct([
            'id' => 2,
            'name' => 'Second item',
        ]),
    ]);

    expect($cart->count())->toBe(2);
    Event::assertDispatched('cart.added');
});

it('can add multiple buyable items with different quantities', function () {
    Event::fake();
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');

    $cart = new ShoppingCart($session, $dispatcher);
    $cart->add(new BuyableProduct([
        'id' => 1,
        'name' => 'First item',
    ]), 2);

    $cart->add(new BuyableProduct([
        'id' => 2,
        'name' => 'Second item',
    ]), 3);

    expect($cart->count())->toBe(5);
    Event::assertDispatched('cart.added');
});

it('return an array of cart items when adding multiple items at once', function () {
    Event::fake();
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');

    $cart = new ShoppingCart($session, $dispatcher);
    $cartItems = $cart->add([
        new BuyableProduct([
            'id' => 1,
            'name' => 'First item',
        ]),
        new BuyableProduct([
            'id' => 2,
            'name' => 'Second item',
        ]),
    ]);

    expect($cartItems)->toBeArray();
    expect(count($cartItems))->toBe(2);

    expect($cartItems[0])->toBeInstanceOf(\Soap\ShoppingCart\CartItem::class);
    expect($cartItems[1])->toBeInstanceOf(\Soap\ShoppingCart\CartItem::class);

    Event::assertDispatched('cart.added');
});

it('can add item from attributes', function () {
    Event::fake();
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');

    $cart = new ShoppingCart($session, $dispatcher);
    $cartItem = $cart->add(1, 'First item', 1, 10.00);

    expect($cartItem)->toBeInstanceOf(\Soap\ShoppingCart\CartItem::class);
    expect($cart->count())->toBe(1);
    Event::assertDispatched('cart.added');
});

it('can add item from an array', function () {
    Event::fake();
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');

    $cart = new ShoppingCart($session, $dispatcher);
    $cartItem = $cart->add([
        'id' => 1,
        'name' => 'First item',
        'qty' => 1,
        'price' => 10.00,
        'weight' => 550,
    ]);

    expect($cartItem)->toBeInstanceOf(\Soap\ShoppingCart\CartItem::class);
    expect($cart->count())->toBe(1);
    Event::assertDispatched('cart.added');
});

it('cann ad multiple array items at once', function () {
    Event::fake();
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');

    $cart = new ShoppingCart($session, $dispatcher);
    $cartItems = $cart->add([
        [
            'id' => 1,
            'name' => 'First item',
            'qty' => 1,
            'price' => 10.00,
            'weight' => 550,
        ],
        [
            'id' => 2,
            'name' => 'Second item',
            'qty' => 2,
            'price' => 20.00,
            'weight' => 1500,
        ],
    ]);

    expect($cartItems)->toBeArray();
    expect(count($cartItems))->toBe(2);

    expect($cartItems[0])->toBeInstanceOf(\Soap\ShoppingCart\CartItem::class);
    expect($cartItems[1])->toBeInstanceOf(\Soap\ShoppingCart\CartItem::class);

    Event::assertDispatched('cart.added');
});

it('can add item with options', function () {
    Event::fake();
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');

    $cart = new ShoppingCart($session, $dispatcher);
    $options = [
        'color' => 'red',
        'size' => 'L',
    ];
    $cartItem = $cart->add(new BuyableProduct([
        'id' => 1,
        'name' => 'First item',
    ]), 1, $options);

    expect($cartItem)->toBeInstanceOf(\Soap\ShoppingCart\CartItem::class);
    expect($cartItem->options->size)->toEqual('L');
    expect($cartItem->options->color)->toEqual('red');
    Event::assertDispatched('cart.added');
});

it('will validate the identifier', function () {
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');
    $cart = new ShoppingCart($session, $dispatcher);

    $cart->add(null, 'Some title', 1, 10.00);
})->throws(\InvalidArgumentException::class, 'Please supply a valid identifier.');

it('will validate the name', function () {
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');
    $cart = new ShoppingCart($session, $dispatcher);

    $cart->add(1, null, 1, 10.00);
})->throws(\InvalidArgumentException::class, 'Please supply a valid name.');

it('will validate the quantity', function () {
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');
    $cart = new ShoppingCart($session, $dispatcher);

    $cart->add(1, 'Some title', null, 10.00);
})->throws(\InvalidArgumentException::class, 'Please supply a valid quantity.');

/*
it('will validate the quantity as a positive integer', function () {
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');
    $cart = new ShoppingCart($session, $dispatcher);

    $cart->add(1, 'Some title', -1, 10.00);
})->throws(\InvalidArgumentException::class, 'Please supply a valid quantity.');
*/

it('will validate the price', function () {
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');
    $cart = new ShoppingCart($session, $dispatcher);

    $cart->add(1, 'Some title', 1, null);
})->throws(\InvalidArgumentException::class, 'Please supply a valid price.');

it('will validate the price as a positive number', function () {
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');
    $cart = new ShoppingCart($session, $dispatcher);

    $cart->add(1, 'Some title', 1, -10.00);
})->throws(\InvalidArgumentException::class, 'Please supply a valid price.');

it('will validate the weight', function () {
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');
    $cart = new ShoppingCart($session, $dispatcher);

    $cart->add(1, 'Some title', 1, 10.00, null);
})->throws(\InvalidArgumentException::class, 'Please supply a valid weight.');

/*
it('will validate the weight as a positive number', function () {
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');
    $cart = new ShoppingCart($session, $dispatcher);

    $cart->add(1, 'Some title', 1, 10.00, -10.00);
})->throws(\InvalidArgumentException::class, 'Please supply a valid weight.');
*/

it('will update the cart if item already exists in the cart', function () {
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');

    $cart = new ShoppingCart($session, $dispatcher);
    $cart->add(new BuyableProduct([
        'id' => 1,
        'name' => 'First item',
    ]), 2);

    $cartItem = $cart->add(new BuyableProduct([
        'id' => 1,
        'name' => 'First item',
    ]), 3);

    expect($cartItem)->toBeInstanceOf(\Soap\ShoppingCart\CartItem::class);
    expect($cartItem->qty)->toEqual(5);
    expect($cart->count())->toBe(5);
    expect($cart->content()->count())->toBe(1);
});

it('will keep update then quantity when an item is added multiple times', function () {
    $session = $this->app->make('session');
    $dispatcher = $this->app->make('events');

    $cart = new ShoppingCart($session, $dispatcher);
    $item = new BuyableProduct;

    $cart->add($item);
    $cart->add($item);
    $cart->add($item);

    expect($cart->count())->toBe(3);
    expect($cart->content()->count())->toBe(1);
});
