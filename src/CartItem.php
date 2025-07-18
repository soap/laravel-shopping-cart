<?php

namespace Soap\ShoppingCart;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use ReflectionClass;
use Soap\ShoppingCart\Calculation\CustomCartItemCalculator;
use Soap\ShoppingCart\Contracts\BuyableInterface;
use Soap\ShoppingCart\Contracts\CalculatorInterface;
use Soap\ShoppingCart\Exceptions\InvalidCalculatorException;

/**
 * @property-read float $discountTotal
 * @property-read float $finalSubtotal
 * @property-read float $subtotalAfterItemDiscount
 * @property-read float $priceTarget
 * @property-read float $priceNet
 * @property-read float $priceTotal
 * @property-read float $subtotal
 * @property-read float $taxTotal
 * @property-read float $discount
 * @property-read float $tax
 * @property-read float $total
 * @property-read float $priceTax
 */
class CartItem implements Arrayable, Jsonable
{
    /**
     * The rowID of the cart item.
     *
     * @var string
     */
    public $rowId;

    /**
     * The ID of the cart item.
     *
     * @var int|string
     */
    public $id;

    /**
     * The quantity for this cart item.
     *
     * @var int|float
     */
    public $qty;

    /**
     * The name of the cart item.
     *
     * @var string
     */
    public $name;

    /**
     * The price without TAX of the cart item.
     *
     * @var float
     */
    public $price;

    /**
     * Whether the cart item is discountable.
     *
     * @var bool
     */
    public $isDiscountable = true;

    /**
     * The weight of the product.
     *
     * @var float
     */
    public $weight;

    /**
     * The options for this cart item.
     *
     * @var CartItemOptions|array
     */
    public $options;

    /**
     * The tax rate for the cart item.
     *
     * @var int|float
     */
    public $taxRate = 0;

    /**
     * The FQN of the associated model.
     *
     * @var string|null
     */
    private $associatedModel = null;

    /**
     * The discount rate for the cart item.
     *
     * @var float
     */
    public $discountRate = 0;

    /**
     * The discount amount (fixed) for cart item
     *
     * @var float
     */
    public $discountAmount = 0;

    public $proportionalWeight = 0;

    /**
     * The discount from subtotal level allocated to this item.
     */
    public $appliedSubtotalDiscount = 0;

    public $subtotalLevelDiscountTotal = 0;

    public $appliedCouponCode = null;

    /**
     * The cart instance of the cart item.
     *
     * @var null|string
     */
    public $instance = null;

    /**
     * CartItem constructor.
     *
     * @param  int|string  $id
     * @param  string  $name
     * @param  float  $price
     * @param  float  $weight
     * @param  bool  $discountable  // must be after $options to avoid issues with existing methods
     */
    public function __construct($id, $name, $price, $weight = 0, array $options = [], $discountable = true)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('Please supply a valid identifier.');
        }
        if (empty($name)) {
            throw new \InvalidArgumentException('Please supply a valid name.');
        }
        if (! is_numeric($price) || $price < 0) {
            throw new \InvalidArgumentException('Please supply a valid price.');
        }
        if ($weight < 0 || ! is_numeric($weight)) {
            throw new \InvalidArgumentException('Please supply a valid weight.');
        }

        $this->id = $id;
        $this->name = $name;
        $this->price = floatval($price);
        $this->weight = floatval($weight);
        $this->isDiscountable = $discountable;
        $this->options = new CartItemOptions($options);
        $this->rowId = $this->generateRowId($id, $options);
    }

    /**
     * Returns the formatted weight.
     *
     * @param  int  $decimals
     * @param  string  $decimalPoint
     * @param  string  $thousandSeperator
     * @return string
     */
    public function weight($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->weight, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted price without TAX.
     *
     * @param  int  $decimals
     * @param  string  $decimalPoint
     * @param  string  $thousandSeperator
     * @return string
     */
    public function price($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->price, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted price with discount applied.
     *
     * @param  int  $decimals
     * @param  string  $decimalPoint
     * @param  string  $thousandSeperator
     * @return string
     */
    public function priceTarget($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->priceTarget, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted price with TAX.
     *
     * @param  int  $decimals
     * @param  string  $decimalPoint
     * @param  string  $thousandSeperator
     * @return string
     */
    public function priceTax($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->priceTax, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted subtotal.
     * Subtotal is price for whole CartItem without TAX.
     *
     * @param  int  $decimals
     * @param  string  $decimalPoint
     * @param  string  $thousandSeperator
     * @return string
     */
    public function subtotal($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        // changes from subtoal in as in Deafault to subtotalAfterItemDiscount
        return $this->numberFormat($this->subtotalAfterItemDiscount, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted total.
     * Total is price for whole CartItem with TAX and discount.
     *
     * @param  int  $decimals
     * @param  string  $decimalPoint
     * @param  string  $thousandSeperator
     * @return string
     */
    public function total($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->total, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted tax.
     *
     * @param  int  $decimals
     * @param  string  $decimalPoint
     * @param  string  $thousandSeperator
     * @return string
     */
    public function tax($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->tax, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted tax.
     *
     * @param  int  $decimals
     * @param  string  $decimalPoint
     * @param  string  $thousandSeperator
     * @return string
     */
    public function taxTotal($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->taxTotal, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted discount.
     *
     * @param  int  $decimals
     * @param  string  $decimalPoint
     * @param  string  $thousandSeperator
     * @return string
     */
    public function discount($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->discount, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted total discount for this cart item.
     *
     * @param  int  $decimals
     * @param  string  $decimalPoint
     * @param  string  $thousandSeperator
     * @return string
     */
    public function discountTotal($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->discountTotal, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Returns the formatted total price for this cart item.
     *
     * @param  int  $decimals
     * @param  string  $decimalPoint
     * @param  string  $thousandSeperator
     * @return string
     */
    public function priceTotal($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->priceTotal, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Set the quantity for this cart item.
     *
     * @param  int|float  $qty
     */
    public function setQuantity($qty)
    {
        if (empty($qty) || ! is_numeric($qty)) {
            throw new \InvalidArgumentException('Please supply a valid quantity.');
        }

        $this->qty = $qty;

        return $this;
    }

    /**
     * Update the cart item from a Buyable.
     *
     * @return void
     */
    public function updateFromBuyable(BuyableInterface $item)
    {
        $this->id = $item->getBuyableIdentifier($this->options);
        $this->name = $item->getBuyableDescription($this->options);
        $this->price = $item->getBuyablePrice($this->options);
        $this->isDiscountable = $item->getIsDiscountable($this->options);
    }

    /**
     * Update the cart item from an array.
     *
     *
     * @return void
     */
    public function updateFromArray(array $attributes)
    {
        $this->id = Arr::get($attributes, 'id', $this->id);
        $this->qty = Arr::get($attributes, 'qty', $this->qty);
        $this->name = Arr::get($attributes, 'name', $this->name);
        $this->price = Arr::get($attributes, 'price', $this->price);
        $this->weight = Arr::get($attributes, 'weight', $this->weight);
        $this->options = new CartItemOptions(Arr::get($attributes, 'options', $this->options));
        $this->isDiscountable = Arr::get($attributes, 'isDiscountable', $this->isDiscountable);
        $this->rowId = $this->generateRowId($this->id, $this->options->all());
    }

    /**
     * Associate the cart item with the given model.
     *
     * @param  mixed  $model
     * @return \Soap\ShoppingCart\CartItem
     */
    public function associate($model)
    {
        $this->associatedModel = is_string($model) ? $model : get_class($model);

        return $this;
    }

    /**
     * Set the tax rate.
     *
     * @param  int|float  $taxRate
     * @return \Soap\ShoppingCart\CartItem
     */
    public function setTaxRate($taxRate)
    {
        $this->taxRate = $taxRate;

        return $this;
    }

    public function setDiscountAmount($discountAmount)
    {
        $this->discountAmount = $discountAmount;

        return $this;
    }

    public function getDiscountAmount()
    {
        return (float) $this->discountAmount;
    }

    /**
     * Set the discount rate.
     *
     * @param  int|float  $discountRate
     * @return \Soap\ShoppingCart\CartItem
     */
    public function setDiscountRate($discountRate)
    {
        $this->discountRate = $discountRate;

        return $this;
    }

    /**
     * Getter for the raw internal discount rate.
     * Should be used in calculators.
     *
     * @return float
     */
    public function getDiscountRate()
    {
        return (float) $this->discountRate;
    }

    /**
     * Set cart instance.
     *
     * @param  null|string  $instance
     * @return \Soap\ShoppingCart\CartItem
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;

        return $this;
    }

    public function setDiscountable($discountable)
    {
        $this->isDiscountable = $discountable;

        return $this;
    }

    /**
     * Get an attribute from the cart item or get the associated model.
     *
     * @param  string  $attribute
     * @return mixed
     */
    public function __get($attribute)
    {
        if (property_exists($this, $attribute)) {
            return $this->{$attribute};
        }
        $decimals = config('shopping-cart.format.decimals', 2);

        switch ($attribute) {
            case 'model':
                if (isset($this->associatedModel)) {
                    return (new $this->associatedModel)->find($this->id);
                }
                // no break
            case 'modelFQCN':
                if (isset($this->associatedModel)) {
                    return $this->associatedModel;
                }
                // no break
            case 'weightTotal':
                return round($this->weight * $this->qty, $decimals);
        }

        $class = new ReflectionClass(config('shopping-cart.calculator', CustomCartItemCalculator::class));
        if (! $class->implementsInterface(CalculatorInterface::class)) {
            throw new InvalidCalculatorException('The configured Calculator seems to be invalid. Calculators have to implement the CalculatorInterface.');
        }

        return call_user_func($class->getName().'::getAttribute', $attribute, $this);
    }

    /**
     * Create a new instance from a Buyable.
     *
     * @return \Soap\ShoppingCart\CartItem
     */
    public static function fromBuyable(BuyableInterface $item, array $options = [])
    {
        return new self($item->getBuyableIdentifier($options), $item->getBuyableDescription($options), $item->getBuyablePrice($options), $item->getBuyableWeight($options), $options);
    }

    /**
     * Create a new instance from the given array.
     *
     *
     * @return \Soap\ShoppingCart\CartItem
     */
    public static function fromArray(array $attributes)
    {
        $options = Arr::get($attributes, 'options', []);

        $item = new self(
            $attributes['id'],
            $attributes['name'],
            $attributes['price'],
            $attributes['weight'] ?? 0,
            $options,
            $attributes['discountable'] ?? true,
        );

        // ✅ เติม field เสริมทั้งหมด
        $item->setQuantity($attributes['qty'] ?? 1);
        $item->setDiscountRate($attributes['discountRate'] ?? 0);
        $item->setDiscountAmount($attributes['discountAmount'] ?? 0);
        $item->setTaxRate($attributes['taxRate'] ?? 0);

        $item->appliedSubtotalDiscount = $attributes['appliedSubtotalDiscount'] ?? 0;
        $item->subtotalLevelDiscountTotal = $attributes['subtotalLevelDiscountTotal'] ?? 0;
        $item->appliedCouponCode = $attributes['appliedCouponCode'] ?? null;
        $item->proportionalWeight = $attributes['proportionalWeight'] ?? 0;
        $item->instance = $attributes['instance'] ?? null;

        return $item;
    }

    /**
     * Create a new instance from the given attributes.
     *
     * @param  int|string  $id
     * @param  string  $name
     * @param  float  $price
     * @return \Soap\ShoppingCart\CartItem
     */
    public static function fromAttributes($id, $name, $price, $weight, array $options = [], $discountable = true)
    {
        return new self($id, $name, $price, $weight, $options, $discountable);
    }

    /**
     * Generate a unique id for the cart item.
     *
     * @param  string  $id
     * @return string
     */
    protected function generateRowId($id, array $options)
    {
        ksort($options);

        return md5($id.serialize($options));
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'rowId' => $this->rowId,
            'id' => $this->id,
            'name' => $this->name,
            'qty' => $this->qty,
            'price' => $this->price,
            'weight' => $this->weight,
            'options' => is_object($this->options)
                ? $this->options->toArray()
                : $this->options,
            'discount' => $this->discount,
            'discountable' => $this->isDiscountable,
            'tax' => $this->tax,
            'subtotal' => $this->subtotal,
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get the formatted number.
     *
     * @param  float  $value
     * @param  int  $decimals
     * @param  string  $decimalPoint
     * @param  string  $thousandSeperator
     * @return string
     */
    private function numberFormat($value, $decimals, $decimalPoint, $thousandSeperator)
    {
        if (is_null($decimals)) {
            $decimals = config('shopping-cart.format.decimals', 2);
        }

        if (is_null($decimalPoint)) {
            $decimalPoint = config('shopping-cart.format.decimal_point', '.');
        }

        if (is_null($thousandSeperator)) {
            $thousandSeperator = config('shopping-cart.format.thousand_separator', ',');
        }

        return number_format($value, $decimals, $decimalPoint, $thousandSeperator);
    }
}
