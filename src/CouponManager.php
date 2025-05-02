<?php

namespace Soap\ShoppingCart;

use Soap\ShoppingCart\Contracts\CouponServiceInterface;
use Soap\ShoppingCart\Exceptions\CouponExpiredException;
use Soap\ShoppingCart\Exceptions\CouponMinimumOrderValueException;
use Soap\ShoppingCart\Exceptions\CouponNotFoundException;

class CouponManager
{
    /**
     * Array that will hold each coupon data
     * indexed by coupon code.
     */
    protected array $coupons = [];

    /**
     * The service that retrieves coupon data.
     */
    protected CouponServiceInterface $couponService;

    /**
     * Construct the Coupon Manager.
     * The CouponServiceInterface is resolved from the Laravel container.
     */
    public function __construct()
    {
        $this->couponService = app(CouponServiceInterface::class);
        $this->populateCoupons();
    }

    public function add(string $couponCode)
    {
        $coupon = $this->couponService->getCouponByCode($couponCode);

        if (! $coupon) {
            throw new \Exception("Coupon not found: {$couponCode}");
        }

        // Prevent duplicate coupon addition.
        if (isset($this->coupons[$couponCode])) {
            throw new \Exception("Coupon already added: {$couponCode}");
        }

        // Store coupon data:
        $this->coupons[$couponCode] = [
            'coupon' => $coupon, // the coupon object from the service
            'applies_target' => $coupon->getAppliesTarget() ?? 'subtotal', // default to subtotal if not specified
            'applied' => false, // not applied yet
            'discount' => 0,    // discount to be calculated later
        ];

        return $this;
    }

    public function remove(string $couponCode)
    {
        if (! isset($this->coupons[$couponCode])) {
            throw new \Exception("Coupon not found: {$couponCode}");
        }

        unset($this->coupons[$couponCode]);

        return $this;
    }

    public function get(string $couponCode): ?array
    {
        return $this->coupons[$couponCode] ?? null;
    }

    public function all(): array
    {
        return $this->coupons;
    }

    public function apply(string $couponCode, ?ShoppingCart $cart = null, int|string|null $userId = null, ?string $guard = null): self
    {
        $coupon = $this->get($couponCode);

        if (! $coupon) {
            throw new \Exception("Coupon not found: {$couponCode}");
        }

        // Apply the coupon to the cart.
        $appliedCoupon = $this->couponService->applyCoupon($couponCode, $cart->finalSubtotal(), $userId, $guard);

        if (! $appliedCoupon) {
            throw new \Exception("Failed to apply coupon: {$couponCode}");
        }

        // Mark the coupon as applied.
        $this->coupons[$appliedCoupon->getCode()]['applied'] = true;

        return $this;
    }

    public function verify(string $couponCode, ?ShoppingCart $cart = null, int|string|null $userId = null, ?string $guard = null): bool
    {
        $coupon = $this->couponService->getCouponByCode($couponCode);

        if (! $coupon) {
            throw new CouponNotFoundException("Coupon not found: {$couponCode}");
        }

        if ($coupon->isExpired()) {
            throw new CouponExpiredException("Coupon expired: {$couponCode}");
        }

        if ($coupon->getMinOrderValue() !== null && $cart->finalSubtotalFloat() < $coupon->getMinOrderValue()) {
            throw new CouponMinimumOrderValueException("Coupon minimum order value not met: {$couponCode}");
        }

        return true;
    }

    /**
     * Retrieve a list of all applied coupons.
     */
    public function appliedCoupons(): array
    {
        return collect($this->coupons)
            ->filter(function (array $coupon) {
                return $coupon['applied'] ?? false;
            })->toArray();
    }

    /**
     * Remove applied coupons from the list of applied coupons.
     *
     * @return $this
     */
    public function removeAppliedCoupons(): self
    {
        $this->coupons = collect($this->coupons)
            ->reject(function (array $coupon) {
                return $coupon['applied'] ?? false;
            })->toArray();

        return $this;
    }

    /**
     * Clear all applied coupons.
     *
     * @return $this
     */
    public function clearAppliedCoupons(): self
    {
        $this->coupons = collect($this->coupons)
            ->reject(function (array $coupon) {
                return $coupon['applied'] ?? false;
            })->toArray();

        return $this;
    }

    public function clear(): self
    {
        $this->coupons = [];

        return $this;
    }

    protected function populateCoupons(): void
    {
        foreach ($this->couponService->getCoupons() as $coupon) {
            $this->add($coupon->getCode());
        }
    }
}
