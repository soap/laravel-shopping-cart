<?php

namespace Soap\ShoppingCart;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use Soap\ShoppingCart\Calculation\CartCalculator;
use Soap\ShoppingCart\Contracts\BuyableInterface;
use Soap\ShoppingCart\Contracts\InstanceIdentifierInterface;
use Soap\ShoppingCart\Exceptions\CartAlreadyStoredException;
use Soap\ShoppingCart\Exceptions\InvalidRowIDException;
use Soap\ShoppingCart\Exceptions\UnknownModelException;

/**
 * @property-read float $finalSubtotal
 */
class ShoppingCart
{
    use Macroable;

    const DEFAULT_INSTANCE = 'default';

    /**
     * Instance of the session manager.
     *
     * @var \Illuminate\Session\SessionManager
     */
    private $session;

    /**
     * Instance of the event dispatcher.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    private $events;

    /**
     * Holds the current cart instance.
     *
     * @var string
     */
    private $instance;

    /**
     * Holds the creation date of the cart.
     *
     * @var mixed
     */
    private $createdAt;

    /**
     * Holds the update date of the cart.
     *
     * @var mixed
     */
    private $updatedAt;

    /**
     * Defines the discount percentage for all items.
     *
     * @var float
     */
    private $discountRate = 0;

    private $shipping = 0;

    /**
     * Defines the tax rate.
     *
     * @var float
     */
    private $taxRate = 0;

    protected $discountManager;

    public $discounts;

    /**
     * Guard name for the cart used when applying coupon.
     */
    protected ?string $guard = null;

    protected $dirty = true;

    /**
     * ShoppingCart constructor.
     */
    public function __construct(SessionManager $session, Dispatcher $events)
    {
        $this->session = $session;
        $this->events = $events;
        $this->taxRate = config('shopping-cart.tax');

        $this->discountManager = new DiscountManager(
            $this,
            app(CouponManager::class),
            app(ConditionManager::class)
        );

        $this->instance(self::DEFAULT_INSTANCE);
    }

    public function setGuard(string $guard): self
    {
        $this->guard = $guard;

        return $this;
    }

    /**
     * Fluent chain support.
     */
    public function withGuard(string $guard): self
    {
        return $this->setGuard($guard);
    }

    /**
     * Set the current ShoppingCart instance.
     *
     * @param  string|null  $instance
     * @return \Soap\ShoppingCart\ShoppingCart
     */
    public function instance($instance = null)
    {
        $instance = $instance ?: self::DEFAULT_INSTANCE;

        if ($instance instanceof InstanceIdentifierInterface) {
            $this->discountRate = $instance->getInstanceGlobalDiscount();
            $instance = $instance->getInstanceIdentifier();
        }

        $this->instance = 'cart.'.$instance;
        $this->discountManager->instance($instance);

        return $this;
    }

    /**
     * Get the current cart instance.
     *
     * @return string
     */
    public function currentInstance()
    {
        return str_replace('cart.', '', $this->instance);
    }

    /**
     * Add an item to the cart.
     *
     * @param  mixed  $id
     * @param  mixed  $name
     * @param  int|float  $qty
     * @param  float  $price
     * @param  float  $weight
     * @return \Soap\ShoppingCart\CartItem | array
     */
    public function add($id, $name = null, $qty = null, $price = null, $weight = 0, array $options = [])
    {
        if ($this->isMulti($id)) {
            return array_map(function ($item) {
                return $this->add($item);
            }, $id);
        }

        $cartItem = $this->createCartItem($id, $name, $qty, $price, $weight, $options);

        return $this->addCartItem($cartItem);
    }

    /**
     * Add an item to the cart.
     *
     * @param  \Soap\ShoppingCart\CartItem  $item  Item to add to the Cart
     * @param  bool  $keepDiscount  Keep the discount rate of the Item
     * @param  bool  $keepTax  Keep the Tax rate of the Item
     * @param  bool  $dispatchEvent
     * @return \Soap\ShoppingCart\CartItem
     */
    public function addCartItem($item, $keepDiscount = false, $keepTax = false, $dispatchEvent = true)
    {
        if (! $keepDiscount) {
            $item->setDiscountRate($this->discountRate);
        }

        if (! $keepTax) {
            $item->setTaxRate($this->taxRate);
        }

        $content = $this->getContent();

        if ($content->has($item->rowId)) {
            $item->qty += $content->get($item->rowId)->qty;
        }

        $content->put($item->rowId, $item);

        if ($dispatchEvent) {
            $this->events->dispatch('cart.adding', $item);
        }

        $this->session->put($this->instance, $content);

        if ($dispatchEvent) {
            $this->events->dispatch('cart.added', $item);
        }

        $this->handleCartChanged();

        return $item;
    }

    /**
     * Update the cart item with the given rowId.
     *
     * @param  string  $rowId
     * @param  mixed  $qty
     * @return \Soap\ShoppingCart\CartItem | null
     */
    public function update($rowId, $qty)
    {
        $cartItem = $this->get($rowId);

        if ($qty instanceof BuyableInterface) {
            $cartItem->updateFromBuyable($qty);
        } elseif (is_array($qty)) {
            $cartItem->updateFromArray($qty);
        } else {
            $cartItem->qty = $qty;
        }

        $content = $this->getContent();

        if ($rowId !== $cartItem->rowId) {
            $itemOldIndex = $content->keys()->search($rowId);

            $content->pull($rowId);

            if ($content->has($cartItem->rowId)) {
                $existingCartItem = $this->get($cartItem->rowId);
                $cartItem->setQuantity($existingCartItem->qty + $cartItem->qty);
            }
        }

        if ($cartItem->qty <= 0) {
            $this->remove($cartItem->rowId);

            return null;
        } else {
            if (isset($itemOldIndex)) {
                $content = $content->slice(0, $itemOldIndex)
                    ->merge([$cartItem->rowId => $cartItem])
                    ->merge($content->slice($itemOldIndex));
            } else {
                $content->put($cartItem->rowId, $cartItem);
            }
        }

        $this->events->dispatch('cart.updating', $cartItem);

        $this->session->put($this->instance, $content);

        $this->events->dispatch('cart.updated', $cartItem);

        $this->handleCartChanged();

        return $cartItem;
    }

    /**
     * Remove the cart item with the given rowId from the cart.
     *
     * @param  string  $rowId
     * @return void
     */
    public function remove($rowId)
    {
        $cartItem = $this->get($rowId);

        $content = $this->getContent();

        $content->pull($cartItem->rowId);

        $this->events->dispatch('cart.removing', $cartItem);

        $this->session->put($this->instance, $content);

        $this->events->dispatch('cart.removed', $cartItem);

        $this->handleCartChanged();
    }

    /**
     * Get a cart item from the cart by its rowId.
     *
     * @param  string  $rowId
     * @return \Soap\ShoppingCart\CartItem
     */
    public function get($rowId)
    {
        $content = $this->getContent();

        if (! $content->has($rowId)) {
            throw new InvalidRowIDException("The cart does not contain rowId {$rowId}.");
        }

        return $content->get($rowId);
    }

    /**
     * Destroy the current cart instance.
     *
     * @return void
     */
    public function destroy()
    {
        $this->session->remove($this->instance);
    }

    /**
     * Get the content of the cart.
     *
     * @return \Illuminate\Support\Collection
     */
    public function content()
    {
        if (is_null($this->session->get($this->instance))) {
            return new Collection([]);
        }

        return $this->session->get($this->instance);
    }

    /**
     * Get the total quantity of all CartItems in the cart.
     *
     * @return int|float
     */
    public function count()
    {
        return $this->getContent()->sum('qty');
    }

    /**
     * Get the amount of CartItems in the Cart.
     * Keep in mind that this does NOT count quantity.
     *
     * @return int|float
     */
    public function countItems()
    {
        return $this->getContent()->count();
    }

    public function shippingFloat()
    {
        return $this->shipping ?? 0.0;
    }

    public function shipping($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->shippingFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the total tax of the items in the cart.
     *
     * @since 2.0.0
     *
     * @return float
     */
    public function taxFloat()
    {
        return $this->getContent()->reduce(function ($tax, CartItem $item) {
            return $tax + $item->taxTotal; // taxTotal = tax * qty
        }, 0.0);
    }

    /**
     * Get the total tax of the items in the cart as formatted string.
     *
     * @param  int  $decimals
     * @param  string  $decimalPoint
     * @param  string  $thousandSeperator
     * @return string
     */
    public function tax($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->taxFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the price of the items in the cart before any discount.
     *
     * @since 2.0.0
     *
     * @return float
     */
    public function initialSubtotalFloat()
    {
        return $this->getContent()->reduce(function ($initial, CartItem $cartItem) {
            return $initial + ($cartItem->qty * $cartItem->price); // priceTotal = price * qty
        }, 0);
    }

    /**
     * Get the price of the items in the cart as formatted string.
     *
     * @param  int  $decimals
     * @param  string  $decimalPoint
     * @param  string  $thousandSeperator
     * @return string
     *
     * @since 2.0.0
     */
    public function initialSubtotal($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->initialSubtotalFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the subtotal after item discounts. Not including subtotal-level discounts (distributed to items)
     *
     * @since 2.0.0
     */
    public function subtotalAfterItemDiscountsFloat(): float
    {
        return $this->getContent()->reduce(function ($sum, CartItem $item) {
            return $sum + (
                $item->price * $item->qty - ($item->appliedItemDiscount ?? 0)
            );
        }, 0);
    }

    /**
     * Get the subtotal after item discounts. Not including subtotal-level discounts (distributed to items)
     *
     * @since 2.0.0
     */
    public function subtotalAfterItemDiscounts($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->subtotalAfterItemDiscountsFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the subtotal after item discounts and subtotal-level discounts.
     *
     * @since 2.0.0
     */
    public function finalSubtotalFloat(): float
    {
        return $this->getContent()->reduce(function ($subtotal, CartItem $item) {
            return $subtotal + $item->finalSubtotal; // finalSubtotal = subtotalAfterItemDiscount - subtotalLevelDiscount
        }, 0);
    }

    /**
     * @since 2.0.0
     */
    public function finalSubtotal($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->finalSubtotalFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    public function subtotal($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->finalSubtotalFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * @since 2.0.0
     */
    public function grossTotalBeforeTotalDiscountFloat(): float
    {
        return
            $this->finalSubtotalFloat()
            + $this->shippingFloat()
            + $this->taxFloat();
    }

    /**
     * @since 2.0.0
     */
    public function grossTotalBeforeTotalDiscount($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->grossTotalBeforeTotalDiscountFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the final payable amount of the cart.
     */
    public function finalPayableFloat(): float
    {
        return max(
            $this->finalSubtotalFloat()
                + $this->shippingFloat()
                + $this->taxFloat()
                - $this->totalLevelDiscountFloat(),
            0.0
        );
    }

    /**
     * @since 2.0.0
     */
    public function finalPayable($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->finalPayableFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    public function total($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->finalPayableFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    public function subtotalLevelDiscountFloat(): float
    {
        return $this->discounts->subtotalLevelDiscount ?? 0.0;
    }

    public function subtotalLevelDiscount($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->subtotalLevelDiscountFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the total level discount of the cart.
     *
     * @since 2.0.0
     */
    public function totalLevelDiscountFloat(): float
    {
        return $this->discounts->totalLevelDiscount ?? 0.0;
    }

    public function totalLevelDiscount($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->totalLevelDiscountFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    public function discountFloat()
    {
        return $this->subtotalLevelDiscountFloat() + $this->totalLevelDiscountFloat();
    }

    public function discount($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->discountFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the total weight of the items in the cart.
     *
     * @return float
     */
    public function weightFloat()
    {
        return $this->getContent()->reduce(function ($total, CartItem $cartItem) {
            return $total + ($cartItem->qty * $cartItem->weight);
        }, 0);
    }

    /**
     * Get the total weight of the items in the cart.
     *
     * @param  int  $decimals
     * @param  string  $decimalPoint
     * @param  string  $thousandSeperator
     * @return string
     */
    public function weight($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        return $this->numberFormat($this->weightFloat(), $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Search the cart content for a cart item matching the given search closure.
     *
     * @return \Illuminate\Support\Collection
     */
    public function search(Closure $search)
    {
        return $this->getContent()->filter($search);
    }

    /**
     * Associate the cart item with the given rowId with the given model.
     *
     * @param  string  $rowId
     * @param  mixed  $model
     * @return void
     */
    public function associate($rowId, $model)
    {
        if (is_string($model) && ! class_exists($model)) {
            throw new UnknownModelException("The supplied model {$model} does not exist.");
        }

        $cartItem = $this->get($rowId);

        $cartItem->associate($model);

        $content = $this->getContent();

        $content->put($cartItem->rowId, $cartItem);

        $this->session->put($this->instance, $content);
    }

    /**
     * Set the tax rate for the cart item with the given rowId.
     *
     * @param  string  $rowId
     * @param  int|float  $taxRate
     * @return void
     */
    public function setTax($rowId, $taxRate)
    {
        $cartItem = $this->get($rowId);

        $cartItem->setTaxRate($taxRate);

        $content = $this->getContent();

        $content->put($cartItem->rowId, $cartItem);

        $this->handleCartChanged();

        $this->session->put($this->instance, $content);
    }

    public function setTaxRate($rowId, $taxRate)
    {
        $this->setTax($rowId, $taxRate);
    }

    /**
     * Set the global tax rate for the cart.
     * This will set the tax rate for all items.
     *
     * @param  float  $taxRate
     * @return void
     */
    public function setGlobalTax($taxRate)
    {
        $this->taxRate = $taxRate;

        $content = $this->getContent();
        if ($content->count()) {
            $content->each(function ($item, $key) {
                $item->setTaxRate($this->taxRate);
            });

        }
        $this->handleCartChanged();

    }

    public function setGlobalTaxRate($taxRate)
    {
        $this->setGlobalTax($taxRate);
    }

    /**
     * Set the discount rate for the cart item with the given rowId.
     *
     * @param  string  $rowId
     * @param  int|float  $discount
     * @return void
     */
    public function setDiscount($rowId, $discount)
    {
        $cartItem = $this->get($rowId);

        $cartItem->setDiscountRate($discount);

        $content = $this->getContent();

        $content->put($cartItem->rowId, $cartItem);

        $this->handleCartChanged();

        $this->session->put($this->instance, $content);
    }

    /**
     * Set the global discount percentage for the cart.
     * This will set the discount for all cart items.
     *
     * @param  float  $discountRate
     * @return void
     */
    public function setGlobalDiscount($discountRate)
    {
        $this->discountRate = $discountRate;

        $content = $this->getContent();

        if ($content->count()) {
            $content->each(function ($item, $key) {
                $item->setDiscountRate($this->discountRate);
            });
        }

        $this->handleCartChanged();
    }

    /**
     * Store an the current instance of the cart.
     *
     * @param  mixed  $identifier
     * @return void
     */
    public function store($identifier)
    {
        if ($identifier instanceof InstanceIdentifierInterface) {
            $identifier = $identifier->getInstanceIdentifier();
        }

        $instance = $this->currentInstance();

        if ($this->storedCartInstanceWithIdentifierExists($instance, $identifier)) {
            throw new CartAlreadyStoredException("A cart with identifier {$identifier} was already stored.");
        }

        $payload = [
            'items' => $this->getContent()->all(), // CartItem[]
            'coupons' => $this->discountManager->coupons()->resolvedCoupons(true),
            'conditions' => [], // $this->discountManager->conditions()->resolveConditions(true),
        ];

        $serializedContent = $this->getConnection()->getDriverName() === 'pgsql'
            ? base64_encode(serialize($payload))
            : serialize($payload);

        $this->getConnection()->table($this->getTableName())->insert([
            'identifier' => $identifier,
            'instance' => $instance,
            'content' => $serializedContent,
            'created_at' => $this->createdAt ?: Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->events->dispatch('cart.stored');
    }

    /**
     * Restore the cart with the given identifier.
     *
     * @param  mixed  $identifier
     * @return void
     */
    public function restore($identifier)
    {
        if ($identifier instanceof InstanceIdentifierInterface) {
            $identifier = $identifier->getInstanceIdentifier();
        }

        $currentInstance = $this->currentInstance();

        if (! $this->storedCartInstanceWithIdentifierExists($currentInstance, $identifier)) {
            return;
        }

        $stored = $this->getConnection()->table($this->getTableName())
            ->where(['identifier' => $identifier, 'instance' => $currentInstance])->first();

        $storedContent = $this->getConnection()->getDriverName() === 'pgsql'
            ? unserialize(base64_decode(data_get($stored, 'content')))
            : unserialize(data_get($stored, 'content'));

        $this->instance(data_get($stored, 'instance'));

        // 🔄 Compatibility: support old format (just an array of items)
        $items = is_array($storedContent) && array_is_list($storedContent)
            ? $storedContent
            : data_get($storedContent, 'items', []);

        $coupons = data_get($storedContent, 'coupons', []);
        $conditions = data_get($storedContent, 'conditions', []);

        // Restore cart items
        $content = collect();

        foreach ($items as $cartItem) {
            $content->put($cartItem->rowId, $cartItem);
        }
        $this->session->put($this->instance, $content);

        // Restore coupons
        $this->discountManager->coupons()->restoreFromSnapshot($coupons);

        // Restore conditions
        /* */
        // $this->discountManager->conditions()->restoreFromSnapshot($conditions);

        $this->createdAt = Carbon::parse(data_get($stored, 'created_at'));
        $this->updatedAt = Carbon::parse(data_get($stored, 'updated_at'));

        $this->getConnection()->table($this->getTableName())
            ->where(['identifier' => $identifier, 'instance' => $currentInstance])->delete();

        $this->instance($currentInstance);

        $this->events->dispatch('cart.restored');

        $this->handleCartChanged();
    }

    /**
     * Erase the cart with the given identifier.
     *
     * @param  mixed  $identifier
     * @return void
     */
    public function erase($identifier)
    {
        if ($identifier instanceof InstanceIdentifierInterface) {
            $identifier = $identifier->getInstanceIdentifier();
        }

        $instance = $this->currentInstance();

        if (! $this->storedCartInstanceWithIdentifierExists($instance, $identifier)) {
            return;
        }

        $this->getConnection()->table($this->getTableName())->where(['identifier' => $identifier, 'instance' => $instance])->delete();

        $this->events->dispatch('cart.erased');
    }

    /**
     * Merges the contents of another cart into this cart.
     *
     * @param  mixed  $identifier  Identifier of the Cart to merge with.
     * @param  bool  $keepDiscount  Keep the discount of the CartItems.
     * @param  bool  $keepTax  Keep the tax of the CartItems.
     * @param  bool  $dispatchAdd  Flag to dispatch the add events.
     * @return bool
     */
    public function merge($identifier, $keepDiscount = false, $keepTax = false, $dispatchAdd = true, $instance = self::DEFAULT_INSTANCE)
    {
        if (! $this->storedCartInstanceWithIdentifierExists($instance, $identifier)) {
            return false;
        }

        $stored = $this->getConnection()->table($this->getTableName())
            ->where(['identifier' => $identifier, 'instance' => $instance])->first();

        $storedContent = $this->getConnection()->getDriverName() === 'pgsql'
            ? unserialize(base64_decode($stored->content))
            : unserialize($stored->content);

        // ✅ รองรับ format เก่า & ใหม่
        $items = is_array($storedContent) && array_key_exists('items', $storedContent)
            ? $storedContent['items']
            : $storedContent;

        foreach ($items as $cartItem) {
            if (is_array($cartItem)) {
                $cartItem = CartItem::fromArray($cartItem);
            }

            $this->addCartItem($cartItem, $keepDiscount, $keepTax, $dispatchAdd);
        }

        // ✅ Restore coupons
        if (
            is_array($storedContent)
            && array_key_exists('coupons', $storedContent)
            && method_exists($this->discountManager->coupons(), 'restoreFromSnapshot')
        ) {
            $this->discountManager->coupons()->restoreFromSnapshot($storedContent['coupons']);
        }

        // ✅ Restore conditions
        /*
        if (
            is_array($storedContent)
            && array_key_exists('conditions', $storedContent)
            && method_exists($this->discountManager->conditions(), 'restoreFromSnapshot')
        ) {
            $this->discountManager->conditions()->restoreFromSnapshot($storedContent['conditions']);
        }
        */
        $this->events->dispatch('cart.merged');

        $this->handleCartChanged(); // ✅ Recalculate totals, discounts, etc.

        return true;
    }

    /**
     * Magic method to make accessing the total, tax and subtotal properties possible.
     *
     * @internal provide backwards compatibility with version 1.x
     *    *
     * @param  string  $attribute
     * @return float|null|string
     */
    public function __get($attribute)
    {
        switch ($attribute) {
            case 'total':
                return $this->finalPayable();
            case 'discount':
                return $this->discount();
            case 'tax':
                return $this->tax();
            case 'subtotal':
                return $this->finalSubtotal();
            default:
                return null;
        }
    }

    public function getCoupons(): array
    {
        return $this->discountManager->getCoupons();
    }

    public function getAppliedCoupons(): array
    {
        return $this->discountManager->getAppliedCoupons();
    }

    public function getResolvedCoupons(bool $appliedOnly = true): array
    {
        return $this->discountManager->getResolvedCoupons($appliedOnly);
    }

    public function appliedCoupons(): array
    {
        return $this->discountManager->getAppliedCoupons();
    }

    public function verifyCoupon(string $couponCode, int|string|null $userId = null, ?string $guard = null): bool
    {
        return $this->discountManager->verifyCoupon($couponCode, $userId, $guard);
    }

    public function applyCoupon(string $couponCode, int|string|null $userId = null, ?string $guard = null): bool
    {
        if (! $guard) {
            $guard = $this->guard;
        }
        $this->discountManager->applyCoupon($couponCode, $userId, $guard);
        $this->handleCartChanged();

        return true;
    }

    public function removeCoupon(string $couponCode): bool
    {
        $this->discountManager->removeCoupon($couponCode);
        $this->handleCartChanged();

        return true;
    }

    public function checkout(): bool
    {
        $this->discountManager->checkout();
        $this->handleCartChanged();

        return true;
    }

    public function getCouponBreakdown(): array
    {
        return $this->discounts->couponBreakdown ?? [];
    }

    /**
     * Get the carts content, if there is no cart content set yet, return a new empty Collection.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getContent()
    {
        if ($this->session->has($this->instance)) {
            return $this->session->get($this->instance);
        }

        return new Collection;
    }

    public function handleCartChanged()
    {
        $this->discountManager->calculateDiscounts();
        $calculator = new CartCalculator;
        $calculator->calculate($this);
    }

    /**
     * Create a new CartItem from the supplied attributes.
     *
     * @param  mixed  $id
     * @param  mixed  $name
     * @param  int|float  $qty
     * @param  float  $price
     * @param  float  $weight
     * @return \Soap\ShoppingCart\CartItem
     */
    private function createCartItem($id, $name, $qty, $price, $weight, array $options)
    {
        if ($id instanceof BuyableInterface) {
            $cartItem = CartItem::fromBuyable($id, $qty ?: []);
            $cartItem->setQuantity($name ?: 1);
            $cartItem->associate($id);
        } elseif (is_array($id)) {
            $cartItem = CartItem::fromArray($id);
            $cartItem->setQuantity($id['qty']);
        } else {
            $cartItem = CartItem::fromAttributes($id, $name, $price, $weight, $options);
            $cartItem->setQuantity($qty);
        }

        $cartItem->setInstance($this->currentInstance());

        return $cartItem;
    }

    /**
     * Check if the item is a multidimensional array or an array of Buyables.
     *
     * @param  mixed  $item
     * @return bool
     */
    private function isMulti($item)
    {
        if (! is_array($item)) {
            return false;
        }

        return is_array(head($item)) || head($item) instanceof BuyableInterface;
    }

    /**
     * @return bool
     */
    private function storedCartInstanceWithIdentifierExists($instance, $identifier)
    {
        return $this->getConnection()->table($this->getTableName())->where(['identifier' => $identifier, 'instance' => $instance])->exists();
    }

    /**
     * Get the database connection.
     *
     * @return \Illuminate\Database\Connection
     */
    private function getConnection()
    {
        return app(DatabaseManager::class)->connection($this->getConnectionName());
    }

    /**
     * Get the database table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return config('shopping-cart.database.table', 'shopping_carts');
    }

    /**
     * Get the database connection name.
     *
     * @return string
     */
    private function getConnectionName()
    {
        $connection = config('shopping-cart.database.connection');

        return is_null($connection) ? config('database.default') : $connection;
    }

    /**
     * Get the Formatted number.
     *
     *
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

    /**
     * Get the creation date of the cart (db context).
     *
     * @return \Carbon\Carbon|null
     */
    public function createdAt()
    {
        return $this->createdAt;
    }

    /**
     * Get the lats update date of the cart (db context).
     *
     * @return \Carbon\Carbon|null
     */
    public function updatedAt()
    {
        return $this->updatedAt;
    }
}
