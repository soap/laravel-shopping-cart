<?php

use Carbon\Carbon;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Soap\ShoppingCart\Calculation\GrossPrice;
use Soap\ShoppingCart\CartItem;
use Soap\ShoppingCart\Exceptions\CartAlreadyStoredException;
use Soap\ShoppingCart\Exceptions\InvalidRowIDException;
use Soap\ShoppingCart\Exceptions\UnknownModelException;
use Soap\ShoppingCart\ShoppingCart;
use Soap\ShoppingCart\Tests\Fixtures\BuyableProduct;
use Soap\ShoppingCart\Tests\Fixtures\BuyableProductTrait;
use Soap\ShoppingCart\Tests\Fixtures\DummyUser;
use Soap\ShoppingCart\Tests\Fixtures\Identifiable;
use Soap\ShoppingCart\Tests\Fixtures\ProductModel;

beforeEach(function () {
    $this->app->make('config')->set('shopping-cart', [
        'database' => [
            'connection' => null,
            'table' => 'shopping_carts',
        ],
        'tax' => 21,
        'destroy_on_logout' => false,
        'format' => [
            'decimals' => 2,
            'decimal_point' => '.',
            'thousand_seperator' => ',',
        ],
    ]);
});

it('has default instance', function () {
    $cart = getCart(); // from Helpers.php
    expect($cart->currentInstance())->toBe(ShoppingCart::DEFAULT_INSTANCE);
});

it('can has multiple instances', function () {
    $cart = getCart();
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

    $cart = getCart();
    $cart->add(new BuyableProduct([
        'id' => 1,
        'name' => 'First item',
    ]));

    expect($cart->count())->toBe(1);
    Event::assertDispatched('cart.added');
});

it('returns the cartItem of the added item', function () {
    Event::fake();

    $cart = getCart();
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
    $cart = getCart();
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

    $cart = getCart();
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

    $cart = getCart();
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

    $cart = getCart();
    $cartItem = $cart->add(1, 'First item', 1, 10.00);

    expect($cartItem)->toBeInstanceOf(\Soap\ShoppingCart\CartItem::class);
    expect($cart->count())->toBe(1);
    Event::assertDispatched('cart.added');
});

it('can add item from an array', function () {
    Event::fake();

    $cart = getCart();
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

    $cart = getCart();
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

    $cart = getCart();
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
    $cart = getCart();
    $cart->add(null, 'Some title', 1, 10.00);

})->throws(\InvalidArgumentException::class, 'Please supply a valid identifier.');

it('will validate the name', function () {
    $cart = getCart();
    $cart->add(1, null, 1, 10.00);

})->throws(\InvalidArgumentException::class, 'Please supply a valid name.');

it('will validate the quantity', function () {
    $cart = getCart();

    $cart->add(1, 'Some title', null, 10.00);
})->throws(\InvalidArgumentException::class, 'Please supply a valid quantity.');

it('will validate the price', function () {
    $cart = getCart();

    $cart->add(1, 'Some title', 1, null);
})->throws(\InvalidArgumentException::class, 'Please supply a valid price.');

it('will validate the price as a positive number', function () {
    $cart = getCart();

    $cart->add(1, 'Some title', 1, -10.00);
})->throws(\InvalidArgumentException::class, 'Please supply a valid price.');

it('will validate the weight', function () {
    $cart = getCart();

    $cart->add(1, 'Some title', 1, 10.00, null);
})->throws(\InvalidArgumentException::class, 'Please supply a valid weight.');

it('applies global discount rate on item level', function () {
    $cart = getCart();
    $cart->setGlobalTaxRate(0);
    $cart->setGlobalDiscount(50);

    $cart->add(new BuyableProduct(['price' => 100]), 1);
    $item = $cart->content()->first();
    expect($item->subtotal())->toEqual(50);
    expect($item->getDiscountRate())->toEqual(50); // และค่านี้ถูกใช้จริง

    expect($cart->total())->toEqual(50.00);
});

it('will update the cart if item already exists in the cart', function () {
    $cart = getCart();
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
    $cart = getCart();
    $item = new BuyableProduct;

    $cart->add($item);
    $cart->add($item);
    $cart->add($item);

    expect($cart->count())->toBe(3);
    expect($cart->content()->count())->toBe(1);
});

it('can update the quantity of an existsing item in the cart', function () {
    $cart = getCart();
    $cartItem = $cart->add(new BuyableProduct([
        'id' => 1,
        'name' => 'First item',
    ]), 2);

    $cartItem = $cart->update($cartItem->rowId, 3);

    expect($cartItem)->toBeInstanceOf(\Soap\ShoppingCart\CartItem::class);
    expect($cartItem->qty)->toEqual(3);
    expect($cart->count())->toBe(3);
});

it('can update an existing item in the cart from a buyable', function () {
    Event::fake();

    $cart = getCart();

    $cart->add(new BuyableProduct([
        'description' => 'Description',
    ]));

    $cart->update('027c91341fd5cf4d2579b49c4b6a90da', new BuyableProductTrait([
        'name' => '',
        'description' => 'Different description',
    ]));

    expect($cart->count())->toBe(1);
    expect($cart->get('027c91341fd5cf4d2579b49c4b6a90da')->name)->toEqual('Different description');
    Event::assertDispatched('cart.updated');
});

it('can update an existing item in the cart from an array', function () {
    Event::fake();

    $cart = getCart();

    $cart->add(new BuyableProduct([
        'description' => 'Description',
    ]));
    $cart->update('027c91341fd5cf4d2579b49c4b6a90da', [
        'name' => 'Different description',
    ]);
    expect($cart->count())->toBe(1);
    expect($cart->get('027c91341fd5cf4d2579b49c4b6a90da')->name)->toEqual('Different description');
    Event::assertDispatched('cart.updated');
});

it('will throw an exception if a rowId was not found', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct);

    $cart->update('none-existing-row-id', new BuyableProduct([
        'description' => 'Different description',
    ]));

})->throws(InvalidRowIDException::class, 'The cart does not contain rowId none-existing-row-id.');

it('will regenerate the rowId if the options changed', function () {
    $cart = getCart();

    $cart->add(new BuyableProduct, 1, ['color' => 'red']);
    // rowId is generated from the id and options, it is hardcoded in the test
    $cart->update('ea65e0bdcd1967c4b3149e9e780177c0', ['options' => ['color' => 'blue']]);

    expect($cart->count())->toEqual(1);
    expect($cart->content()->count())->toEqual(1);
    expect($cart->content()->first()->rowId)->toEqual('7e70a1e9aaadd18c72921a07aae5d011');
    expect($cart->get('7e70a1e9aaadd18c72921a07aae5d011')->options->color)->toEqual('blue');
});

it('will add the item to an existibg row if the options changed to an existing rowId', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct, 1, ['color' => 'red']);
    $cart->add(new BuyableProduct, 1, ['color' => 'blue']);
    // rowId is generated from the id and options, it is hardcoded in the test
    // the rowId of the first item is 'ea65e0bdcd1967c4b3149e9e780177c0'
    // the rowId of the second item is '7e70a1e9aaadd18c72921a07aae5d011'
    // the options of the second item are changed to the same as the first item
    $cart->update('7e70a1e9aaadd18c72921a07aae5d011', ['options' => ['color' => 'red']]);

    expect($cart->count())->toEqual(2);
    expect($cart->content()->count())->toEqual(1);
});

it('will keep items sequence if the options changed', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct, 1, ['color' => 'red']);
    $cart->add(new BuyableProduct, 1, ['color' => 'green']);
    $cart->add(new BuyableProduct, 1, ['color' => 'blue']);

    $cart->update($cart->content()->values()[1]->rowId, ['options' => ['color' => 'yellow']]);
    expect($cart->content()->count())->toEqual(3);
    expect($cart->content()->values()[1]->options->color)->toEqual('yellow');
});

it('can remove an item from the cart', function () {
    Event::fake();

    $cart = getCart();

    $cart->add(new BuyableProduct);

    $cart->remove('027c91341fd5cf4d2579b49c4b6a90da');

    expect($cart->content()->count())->toEqual(0);
    expect($cart->count())->toEqual(0);

    Event::assertDispatched('cart.removed');
});

it('will remove the item if its quantity was set to zero', function () {
    Event::fake();

    $cart = getCart();

    $cart->add(new BuyableProduct);
    $cart->update('027c91341fd5cf4d2579b49c4b6a90da', 0);

    expect($cart->content()->count())->toEqual(0);
    expect($cart->count())->toEqual(0);

    Event::assertDispatched('cart.removed');
});

it('will remove the item if its quantity was set negative', function () {
    Event::fake();

    $cart = getCart();

    $cart->add(new BuyableProduct);
    $cart->update('027c91341fd5cf4d2579b49c4b6a90da', -1);

    expect($cart->content()->count())->toEqual(0);
    expect($cart->count())->toEqual(0);

    Event::assertDispatched('cart.removed');
});

it('can get an item from the cart by its rowid', function () {
    $cart = getCart();

    $cart->add(new BuyableProduct);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

    expect($cartItem)->toBeInstanceOf(CartItem::class);
});

it('can get the content of the cart', function () {
    $cart = getCart();

    $cart->add(new BuyableProduct);
    $cart->add(new BuyableProduct(['id' => 2]));

    $content = $cart->content();

    expect($content)->toBeInstanceOf(Collection::class);
    expect($content->count())->toEqual(2);
});

it('will return an empty collection if the cart is empty', function () {
    $cart = getCart();
    $content = $cart->content();

    expect($content)->toBeInstanceOf(Collection::class);
    expect($content->count())->toEqual(0);
});

it('will include the tax and subtotal when converted to an array', function () {
    $cart = getCart();

    $cart->add(new BuyableProduct);
    $cart->add(new BuyableProduct(['id' => 2]));

    $content = $cart->content();

    expect($content)->toBeInstanceOf(Collection::class);
    expect($content->toArray())->toEqual([
        '027c91341fd5cf4d2579b49c4b6a90da' => [
            'rowId' => '027c91341fd5cf4d2579b49c4b6a90da',
            'id' => 1,
            'name' => 'Item name',
            'qty' => 1,
            'price' => 10.00,
            'tax' => 2.10,
            'subtotal' => 10.0,
            'options' => [],
            'discount' => 0.0,
            'weight' => 0.0,
        ],
        '370d08585360f5c568b18d1f2e4ca1df' => [
            'rowId' => '370d08585360f5c568b18d1f2e4ca1df',
            'id' => 2,
            'name' => 'Item name',
            'qty' => 1,
            'price' => 10.00,
            'tax' => 2.10,
            'subtotal' => 10.0,
            'options' => [],
            'discount' => 0.0,
            'weight' => 0.0,
        ],
    ]);
});

it('can destroy a cart', function () {
    $cart = getCart();

    $cart->add(new BuyableProduct);
    expect($cart->count())->toEqual(1);

    $cart->destroy();
    expect($cart->count())->toEqual(0);
});

it('can get the total price of the cart content', function () {
    $cart = getCart();

    $cart->add(new BuyableProduct(['name' => 'First item'])); // 1 x 10.00
    $cart->add(new BuyableProduct([
        'id' => 2,
        'name' => 'Second item',
        'price' => 25.00,
    ]), 2);

    expect($cart->count())->toEqual(3);
    expect($cart->finalSubtotal())->toEqual(60.00);
});

it('can return a formatted total', function () {
    $cart = getCart();

    $cart->add(new BuyableProduct([
        'name' => 'First item',
        'price' => 1000.00,
    ]));
    $cart->add(new BuyableProduct([
        'id' => 2,
        'name' => 'Second item',
        'price' => 2500.00,
    ]), 2);

    expect($cart->count())->toEqual(3);
    expect($cart->finalSubtotal(2, ',', '.'))->toEqual('6.000,00');
});

it('can search the cart for a specific item', function () {
    $cart = getCart();

    $cart->add(new BuyableProduct(['name' => 'Some item']));
    $cart->add(new BuyableProduct([
        'id' => 2,
        'name' => 'Another item',
    ]));

    $cartItem = $cart->search(function ($cartItem, $rowId) {
        return $cartItem->name == 'Some item';
    });

    expect($cartItem)->toBeInstanceOf(Collection::class);
    expect($cartItem->count())->toEqual(1);
    expect($cartItem->first())->toBeInstanceOf(CartItem::class);
    expect($cartItem->first()->id)->toEqual(1);
});

it('can search the cart for multiple items', function () {
    $cart = getCart();

    $cart->add(new BuyableProduct(['name' => 'Some item']));
    $cart->add(new BuyableProduct([
        'id' => 2,
        'name' => 'Some item',
    ]));
    $cart->add(new BuyableProduct([
        'id' => 3,
        'name' => 'Another item',
    ]));

    $cartItem = $cart->search(function ($cartItem, $rowId) {
        return $cartItem->name == 'Some item';
    });

    expect($cartItem)->toBeInstanceOf(Collection::class);
});

it('can search the cart for a specific item with options', function () {
    $cart = getCart();

    $cart->add(new BuyableProduct(['name' => 'Some item']), 1, ['color' => 'red']);
    $cart->add(new BuyableProduct([
        'id' => 2,
        'name' => 'Another item',
    ]), 1, ['color' => 'blue']);

    $cartItem = $cart->search(function ($cartItem, $rowId) {
        return $cartItem->options->color == 'red';
    });

    expect($cartItem)->toBeInstanceOf(Collection::class);
    expect($cartItem->count())->toEqual(1);
    expect($cartItem->first())->toBeInstanceOf(CartItem::class);
    expect($cartItem->first()->id)->toEqual(1);
});

it('will associate the cart item with a model when you add a buyable', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct);

    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    expect($cartItem->modelFQCN)->toEqual(BuyableProduct::class);
});

it('can associate the cart item with a model', function () {
    $cart = getCart();
    $cart->add(1, 'Test item', 1, 10.00);

    $cart->associate('027c91341fd5cf4d2579b49c4b6a90da', new ProductModel);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    expect($cartItem->modelFQCN)->toEqual(ProductModel::class);
});

it('will throw an exception when a non existing model is being associated', function () {
    $cart = getCart();
    $cart->add(1, 'Test item', 1, 10.00);
    $cart->associate('027c91341fd5cf4d2579b49c4b6a90da', 'SomeModel');
})->throws(UnknownModelException::class, 'The supplied model SomeModel does not exist.');

it('can get the associated model of a cart item', function () {
    $cart = getCart();
    $cart->add(1, 'Test item', 1, 10.00);
    $cart->associate('027c91341fd5cf4d2579b49c4b6a90da', new ProductModel);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

    expect($cartItem->model)->toBeInstanceOf(ProductModel::class);
    expect($cartItem->model->someValue)->toEqual('Some value');
});

it('can calculate the subtotal of a cart item', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct([
        'name' => 'Some title',
        'price' => 9.99,
    ]), 3);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    expect($cartItem->subtotal)->toEqual(29.97);
});

it('can return a formatted subtotal', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct([
        'name' => 'Some title',
        'price' => 500,
    ]), 3);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    expect($cartItem->subtotal(2, ',', '.'))->toEqual('1.500,00');
});

it('can calculate tax based on the default tax rate in the config', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct(['name' => 'Some title']), 1);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    expect($cartItem->tax)->toEqual(2.10);
});

it('can calculate tax based on the specified tax', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct(['name' => 'Some title']), 1);
    $cart->setTax('027c91341fd5cf4d2579b49c4b6a90da', 19);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    expect($cartItem->tax)->toEqual(1.90);
});

it('can return the calculated tax formatted', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct([
        'name' => 'Some title',
        'price' => 10000.00,
    ]), 1);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    expect($cartItem->tax(2, ',', '.'))->toEqual('2.100,00');
});

it('can calculate the total tax for all cart items', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct(['name' => 'Some title']), 1);
    $cart->add(new BuyableProduct([
        'id' => 2,
        'name' => 'Some title',
        'price' => 20.00,
    ]), 2);
    expect($cart->tax)->toEqual(10.50);
});

it('can return formatted total tax', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct([
        'name' => 'Some title',
        'price' => 1000.00,
    ]), 1);
    $cart->add(new BuyableProduct([
        'id' => 2,
        'name' => 'Some title',
        'price' => 2000.00,
    ]), 2);

    expect($cart->tax(2, ',', '.'))->toEqual('1.050,00');
});

it('can access tax as percentage', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct(['name' => 'Some title']), 1);
    $cart->setTax('027c91341fd5cf4d2579b49c4b6a90da', 19);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    expect($cartItem->taxRate)->toEqual(19);
});

it('can return the subtotal', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct, 1);
    $cart->add(new BuyableProduct([
        'id' => 2,
        'price' => 20.00,
    ]), 2);
    expect($cart->subtotal)->toEqual(50.00);
});

it('can return formatted subtotal', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct([
        'price' => 1000.00,
    ]), 1);
    $cart->add(new BuyableProduct([
        'id' => 2,
        'price' => 2000.00,
    ]), 2);
    expect($cart->subtotal(2, ',', ''))->toEqual('5000,00');
});

it('can return cart formatted numbers by config values', function () {
    setConfigFormat(2, ',', '');

    $cart = getCart();
    $cart->add(new BuyableProduct(['price' => 1000.00]), 1);
    $cart->add(new BuyableProduct([
        'id' => 2,
        'price' => 2000.00,
    ]), 2);

    expect($cart->finalSubtotal())->toEqual('5000,00');
    expect($cart->tax())->toEqual('1050,00');
    expect($cart->total())->toEqual('6050,00');

    expect($cart->finalSubtotal())->toEqual('5000,00');
    expect($cart->tax())->toEqual('1050,00');
    expect($cart->total())->toEqual('6050,00');
});

it('can return cartItem formatted numbers by config values', function () {
    setConfigFormat(2, ',', '');
    $cart = getCartWithDiscount(50);
    $cart->add(new BuyableProduct(['price' => 2000.00]), 2);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    expect($cartItem->price())->toEqual('2000,00');
    expect($cartItem->discount())->toEqual('1000,00');
    expect($cartItem->discountTotal())->toEqual('2000,00');
    expect($cartItem->priceTarget())->toEqual('1000,00');
    expect($cartItem->subtotal())->toEqual('2000,00');
    expect($cartItem->tax())->toEqual('210,00');
    expect($cartItem->taxTotal())->toEqual('420,00');
    expect($cartItem->priceTax())->toEqual('1210,00');
    expect($cartItem->total())->toEqual('2420,00');
})->skip('to be fixed');

it('can store the cart in a database', function () {
    $this->artisan('migrate', ['--database' => 'testing']);
    Event::fake();

    $cart = getCart();
    $cart->add(new BuyableProduct);
    $identifier = 123;
    $cart->store($identifier);

    Event::assertDispatched('cart.stored');

    if (app('db')->getDriverName() === 'pgsql') {
        $serialized = base64_encode(serialize($cart->content()));
    } else {
        $serialized = serialize($cart->content());
    }

    $this->assertDatabaseHas('shopping_carts', [
        'identifier' => $identifier,
        'instance' => 'default',
        'content' => $serialized,
    ]);
});

it('can store and retrieve cart from the database with correct timestamps', function () {
    $this->artisan('migrate', ['--database' => 'testing']);
    Event::fake();

    $cart = getCart();
    $cart->add(new BuyableProduct);

    // Sleep as database does not store ms
    $beforeStore = Carbon::now();
    sleep(1);
    $identifier = 123;
    $cart->store($identifier);
    sleep(1);
    $afterStore = Carbon::now();

    $cart->restore($identifier);
    expect($cart->createdAt()->gte($beforeStore) && $cart->createdAt()->lte($afterStore))->toBeTrue();
    expect($cart->updatedAt()->gte($beforeStore) && $cart->updatedAt()->lte($afterStore))->toBeTrue();

    // Second store
    $beforeSecondStore = Carbon::now();
    sleep(1);
    $cart->store($identifier);
    Event::assertDispatched('cart.stored');
    sleep(1);
    $afterSecondStore = Carbon::now();

    $cart->restore($identifier);
    expect($cart->createdAt()->gte($beforeStore) && $cart->createdAt()->lte($afterStore))->toBeTrue();
    expect($cart->updatedAt()->gte($beforeSecondStore) && $cart->updatedAt()->lte($afterSecondStore))->toBeTrue();
});

it('will throw an exception when a cart was already stored using the specified identifier', function () {
    // $this->artisan('migrate', ['--database' => 'testing']);
    Event::fake();

    $cart = getCart();
    $cart->add(new BuyableProduct);
    $identifier = 123;
    $cart->store($identifier);
    Event::assertDispatched('cart.stored');
    $cart->store($identifier);
})->throws(CartAlreadyStoredException::class, 'A cart with identifier 123 was already stored.');

it('can restore a cart from the database', function () {
    // $this->artisan('migrate', ['--database' => 'testing']);
    Event::fake();

    $cart = getCart();
    $cart->add(new BuyableProduct);
    $identifier = 123;
    $cart->store($identifier);
    $cart->destroy();

    expect(itemsInCart($cart))->toEqual(0);

    $cart->restore($identifier);
    Event::assertDispatched('cart.restored');

    expect(itemsInCart($cart))->toEqual(1);

    $this->assertDatabaseMissing('shopping_carts', [
        'identifier' => $identifier,
        'instance' => 'default',
    ]);
});

it('will just keep the current instance if no cart with the given identifier was stored', function () {
    $this->artisan('migrate', ['--database' => 'testing']);
    $cart = getCart();
    $identifier = 123;
    $cart->restore($identifier);
    expect(itemsInCart($cart))->toEqual(0);
});

it('can calculate all values', function () {
    $cart = getCartWithDiscount(50);
    $cart->add(new BuyableProduct(['name' => 'First item']), 2);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    $cart->setTax('027c91341fd5cf4d2579b49c4b6a90da', 19);
    expect($cartItem->price(2))->toEqual(10.00);
    expect($cartItem->discount(2))->toEqual(5.00);
    expect($cartItem->discountTotal(2))->toEqual(10.00);
    expect($cartItem->priceTarget(2))->toEqual(5.00);
    expect($cartItem->subtotal(2))->toEqual(10.00);
    expect($cartItem->tax(2))->toEqual(0.95);
    expect($cartItem->taxTotal(2))->toEqual(1.90);
    expect($cartItem->priceTax(2))->toEqual(5.95);
    expect($cartItem->total(2))->toEqual(11.90);
})->skip('to be fixed');

it('can calculate all values after updating from array', function () {
    $cart = getCartWithDiscount(50);
    $cart->add(new BuyableProduct(['name' => 'First item']), 1);
    $cart->update('027c91341fd5cf4d2579b49c4b6a90da', ['qty' => 2]);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    $cart->setTax('027c91341fd5cf4d2579b49c4b6a90da', 19);
    expect($cartItem->price(2))->toEqual(10.00);
    expect($cartItem->discount(2))->toEqual(5.00);
    expect($cartItem->discountTotal(2))->toEqual(10.00);
    expect($cartItem->priceTarget(2))->toEqual(5.00);
    expect($cartItem->subtotal(2))->toEqual(10.00);
    expect($cartItem->tax(2))->toEqual(0.95);
    expect($cartItem->taxTotal(2))->toEqual(1.90);
    expect($cartItem->priceTax(2))->toEqual(5.95);
    expect($cartItem->total(2))->toEqual(11.90);
});

it('can calculate all values after updating from buyable', function () {
    $cart = getCartWithDiscount(50);
    $cart->add(new BuyableProduct([
        'name' => 'First item',
        'price' => 5.00,
    ]), 2);
    $cart->update('027c91341fd5cf4d2579b49c4b6a90da', new BuyableProduct(['name' => 'First item']));
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    $cart->setTax('027c91341fd5cf4d2579b49c4b6a90da', 19);
    expect($cartItem->price(2))->toEqual(10.00);
    expect($cartItem->discount(2))->toEqual(5.00);
    expect($cartItem->discountTotal(2))->toEqual(10.00);
    expect($cartItem->priceTarget(2))->toEqual(5.00);
    expect($cartItem->subtotal(2))->toEqual(10.00);
    expect($cartItem->tax(2))->toEqual(0.95);
    expect($cartItem->taxTotal(2))->toEqual(1.90);
    expect($cartItem->priceTax(2))->toEqual(5.95);
    expect($cartItem->total(2))->toEqual(11.90);
})->skip('to be fixed');

it('will destroy the cart when the user logs out and the config setting was set to true', function () {
    $this->app['config']->set('shopping-cart.destroy_on_logout', true);
    $this->app->instance(SessionManager::class, Mockery::mock(SessionManager::class, function ($mock) {
        $mock->shouldReceive('forget')->once()->with('cart');
    }));

    $user = new DummyUser;
    \Auth::guard('web')->login($user);   // Log the user in.
    \Auth::guard('web')->logout();       // This should dispatch the Logout event.
});

it('can change tax globally', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct(['name' => 'Item']), 2);
    $cart->setGlobalTax(0);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    expect($cartItem->total(2))->toEqual('20.00');
});

it('can change discount globally', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct(['name' => 'Item']), 2);
    $cart->setGlobalTax(0);
    $cart->setGlobalDiscount(50);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    expect($cartItem->total(decimals: 2))->toEqual('10.00');
});

it('has no rounding errors', function () {
    $cart = getCart();
    $cart->setGlobalTaxRate(0);
    $cart->add(new BuyableProduct([
        'name' => 'Item',
        'price' => 10.004,
    ]), 2);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    expect($cartItem->total(decimals: 2))->toEqual('20.01');
});

it('can merge multiple carts', function () {
    $this->artisan('migrate', ['--database' => 'testing']);
    Event::fake();
    $cart = getCartWithDiscount(50);
    $cart->add(new BuyableProduct(['name' => 'Item']), 1); // 1 x 10.00
    $cart->add(new BuyableProduct([
        'id' => 2,
        'name' => 'Item 2',
    ]), 1);

    $cart->setGlobalTax(0);

    expect($cart->total())->toEqual(10.00);
    $cart->store('test');  // total 10.00

    $cart2 = getCart();
    $cart2->instance('test2');
    $cart2->setGlobalTax(0);
    $cart2->setGlobalDiscount(0);

    expect($cart2->countItems())->toEqual('0');
    $cart2->merge('test');
    expect($cart2->countItems())->toEqual('2');
    expect($cart2->total())->toEqual(20);

    $cart3 = getCart();
    $cart3->instance('test3');
    $cart3->setGlobalTaxRate(0);
    $cart3->setGlobalDiscount(0);
    $cart3->merge('test', true); // keep discount of item

    expect($cart3->total())->toEqual(10);
});

it('cant merge non existing cart', function () {
    $this->artisan('migrate', ['--database' => 'testing']);
    Event::fake();
    $cart = getCartWithDiscount(50);
    $cart->add(new BuyableProduct(['name' => 'Item']), 1);
    $cart->add(new BuyableProduct([
        'id' => 2,
        'name' => 'Item 2',
    ]), 1);
    expect($cart->merge('doesNotExist'))->toBeFalse();
    expect($cart->countItems())->toEqual(2);
});

it('can access cart item properties', function () {
    $cart = getCartWithDiscount(50);
    $cart->add(new BuyableProduct(['name' => 'First item']), 1);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    expect($cartItem->discountRate)->toEqual(50);
});

it('cant access non existant properties', function () {
    $cart = getCartWithDiscount(50);
    $cart->add(new BuyableProduct(['name' => 'First item']), 1);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    expect($cartItem->doesNotExist)->toBeNull();
    expect($cart->doesNotExist)->toBeNull();
});

it('can set cart item discount', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct(['name' => 'First item']), 1);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    $cart->setDiscount('027c91341fd5cf4d2579b49c4b6a90da', 50);
    expect($cartItem->discountRate)->toEqual(50);
});

it('can set cart item weight and calculate total weight', function () {
    $cart = getCart();
    $cart->add(new BuyableProduct([
        'name' => 'First item',
        'weight' => 250,
    ]), 2);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    $cart->setDiscount('027c91341fd5cf4d2579b49c4b6a90da', 50);
    expect($cart->weight(2))->toEqual('500.00');
    expect($cart->weightFloat())->toEqual(500.00);
    expect($cartItem->weightTotal)->toEqual(500.00);
    expect($cartItem->weight(2))->toEqual('250.00');
});

it('cart can create and restore from instance identifier', function () {
    $this->artisan('migrate', ['--database' => 'testing']);
    Event::fake();

    $identifier = new Identifiable('User1', 0);
    $cart = getCart();
    $cart->instance($identifier);
    expect($cart->currentInstance())->toEqual('User1');

    $cart->add(new BuyableProduct([
        'name' => 'First item',
        'weight' => 250,
    ]), 2);

    expect(itemsInCart($cart))->toEqual(2);

    $cart->store($identifier);
    $cart->destroy();
    expect(itemsInCart($cart))->toEqual(0);

    $cart->restore($identifier);
    expect(itemsInCart($cart))->toEqual(2);
});

it('can create items from models using the canbebought trait', function () {
    $cart = getCartWithDiscount(50); // 50%
    $cart->add(new BuyableProductTrait(['name' => 'First item']), 2); // 2 x 10.00
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    $cart->setTax('027c91341fd5cf4d2579b49c4b6a90da', 19);
    expect($cartItem->price(2))->toEqual(10.00);
    expect($cartItem->discount(2))->toEqual(5.00);
    expect($cartItem->discountTotal(2))->toEqual(10.00);
    expect($cartItem->priceTarget(2))->toEqual(5.00);
    expect($cartItem->subtotal(2))->toEqual(10.00);
    expect($cartItem->tax(2))->toEqual(0.95);
    expect($cartItem->taxTotal(2))->toEqual(1.90);
    expect($cartItem->priceTax(2))->toEqual(5.95);
    expect($cartItem->total(2))->toEqual(11.90);
})->skip('to be fixed');

it('does calculate correct results with rational qtys', function () {
    // https://github.com/Crinsane/LaravelShoppingcart/issues/544
    $cart = getCart();
    $cart->add(new BuyableProductTrait(['name' => 'First item']), 0.5);
    $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');
    $cart->setGlobalTax(50);
    expect($cartItem->price(2))->toEqual(10.00);
    expect($cart->subtotal(2))->toEqual(5.00); // 0.5 qty
    expect($cart->total(2))->toEqual(7.50);     // plus tax
    expect($cart->tax(2))->toEqual(2.50);         // tax of 5 Bucks
})->skip('to be fixed');

it('it does allow adding cart items with weight and options', function () {
    // https://github.com/bumbummen99/LaravelShoppingcart/pull/5
    $cart = getCart();
    $cartItem = $cart->add('293ad', 'Product 1', 1, 9.99, 550, ['size' => 'large']);
    expect($cartItem->weight)->toEqual(550);
    expect($cartItem->options->has('size'))->toBeTrue();
    expect($cartItem->options->size)->toEqual('large');
});

it('can merge without dispatching add events', function () {
    $this->artisan('migrate', ['--database' => 'testing']);
    $cart = getCartWithDiscount(50);
    $cart->add(new BuyableProduct(['name' => 'Item']), 1);
    $cart->add(new BuyableProduct(['id' => 2, 'name' => 'Item 2']), 1);
    $cart->store('test');
    Event::fakeFor(function () {
        $cart2 = getCart();
        $cart2->instance('test2');
        $cart2->setGlobalTax(0);
        $cart2->setGlobalDiscount(0);

        expect($cart2->countItems())->toEqual('0');
        $cart2->merge('test', null, null, false);
        Event::assertNotDispatched('cart.added');
        Event::assertDispatched('cart.merged');
        expect($cart2->countItems())->toEqual('2');
        expect($cart2->totalFloat())->toEqual(20);
    });
})->skip('to be fixed');

it('can merge dispatching add events', function () {
    $this->artisan('migrate', ['--database' => 'testing']);
    $cart = getCartWithDiscount(50);
    $cart->add(new BuyableProduct(['name' => 'Item']), 1);
    $cart->add(new BuyableProduct(['id' => 2, 'name' => 'Item 2']), 1);
    $cart->store('test');
    Event::fakeFor(function () {
        $cart2 = getCart();
        $cart2->instance('test2');
        $cart2->setGlobalTax(0);
        $cart2->setGlobalDiscount(0);

        expect($cart2->countItems())->toEqual('0');
        $cart2->merge('test');
        Event::assertDispatched('cart.added', 2);
        Event::assertDispatched('cart.merged');
        expect($cart2->countItems())->toEqual('2');
        expect($cart2->totalFloat())->toEqual(20);
    });
})->skip('to be fixed');

it('use correctly rounded values for totals and cart summary', function () {
    $decimals = 2;
    setConfigFormat($decimals, ',', '');
    $cart = getCartWithDiscount(6);
    $cartItem = $cart->add(new BuyableProduct([
        'name' => 'First item',
        'price' => 0.18929,
    ]), 1000);
    $cart->add(new BuyableProduct([
        'id' => 2,
        'name' => 'Second item',
        'price' => 4.41632,
    ]), 5);
    $cart->add(new BuyableProduct([
        'id' => 3,
        'name' => 'Third item',
        'price' => 0.37995,
    ]), 25);
    $cart->setGlobalTax(22);
    // check total
    expect($cart->total())->toEqual('253,29');
    // check that the sum of cart subvalues matches the total
    expect(round($cart->totalFloat(), $decimals))
        ->toEqual(round($cart->subtotalFloat() + $cart->taxFloat(), $decimals));
})->skip('to be fixed');

it('it use gross price as base price', function () {
    $cart = getCartWithDiscount(0);
    config(['shopping-cart.calculator' => GrossPrice::class]);
    $cartItem = $cart->add(new BuyableProduct([
        'name' => 'First item',
        'price' => 100,
    ]), 2);
    $cart->setGlobalTax(22);
    // check net price
    expect(round($cartItem->priceNet, 2))->toEqual(81.97);
});

it('use gross price and it use correctly rounded values for totals and cart summary', function () {
    setConfigFormat(2, ',', '');
    config(['shopping-cart.calculator' => GrossPrice::class]);
    $cart = getCartWithDiscount(6);
    $cartItem = $cart->add(new BuyableProduct([
        'name' => 'First item',
        'price' => 0.23093,
    ]), 1000);
    $cart->add(new BuyableProduct([
        'id' => 2,
        'name' => 'Second item',
        'price' => 5.38791,
    ]), 5);
    $cart->add(new BuyableProduct([
        'id' => 3,
        'name' => 'Third item',
        'price' => 0.46354,
    ]), 25);
    $cart->setGlobalTax(22);
    // check total
    expect($cart->total())->toEqual('254,12');
    // check item price total
    expect($cartItem->priceTotal)->toEqual(190);
    // check that cart totals match the sum of its parts
    expect($cart->totalFloat())->toEqual($cart->subtotalFloat() + $cart->taxFloat());
})->skip('to be fixed');
