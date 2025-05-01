<?php

it('has shopping_carts table', function () {
    expect(\Schema::hasTable(config('shopping-cart.database.table')))->toBeTrue();
});

it('has coupons table', function () {
    expect(\Schema::hasTable('coupons'))->toBeTrue();
});
