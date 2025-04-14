<?php

namespace Soap\ShoppingCart\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Soap\ShoppingCart\Contracts\BuyableInterface;
use Soap\ShoppingCart\Traits\CanBeBought;

class BuyableProductTrait extends Model implements BuyableInterface
{
    use CanBeBought;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'title',
        'description',
        'price',
        'weight',
    ];

    protected $attributes = [
        'id' => 1,
        'name' => 'Item name',
        'price' => 10.00,
        'weight' => 0,
    ];
}
