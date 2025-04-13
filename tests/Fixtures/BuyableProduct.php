<?php

namespace Soap\ShoppingCart\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Soap\ShoppingCart\Contracts\BuyableInterface;

class BuyableProduct extends Model implements BuyableInterface
{
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

    /**
     * Get the identifier of the Buyable item.
     */
    public function getBuyableIdentifier($options = null): int|string
    {
        return $this->id;
    }

    /**
     * Get the description or title of the Buyable item.
     */
    public function getBuyableDescription($options = null): string
    {
        return $this->name;
    }

    /**
     * Get the price of the Buyable item.
     */
    public function getBuyablePrice($options = null): float
    {
        return $this->price;
    }

    /**
     * Get the price of the Buyable item.
     */
    public function getBuyableWeight($options = null): float
    {
        return $this->weight;
    }
}
