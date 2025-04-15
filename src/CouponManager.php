<?php

namespace Soap\ShoppingCart;

use Soap\ShoppingCart\Contracts\CouponServiceInterface;

class CouponManager
{
    /**
     * Array that will hold each coupon data
     * indexed by coupon code.
     */
    protected array $coupons;

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

    public function apply(string $couponCode, ?ShoppingCart $cart = null, int|string|null $userId = null): self {}

    public function verify(string $couponCode, ?ShoppingCart $cart = null, int|string|null $userId = null): bool
    {
        $coupon = $this->get($couponCode);

        if (! $coupon) {
            return false;
        }

        return $this->couponService->verifyCoupon($coupon);
    }

    /**
     * Retrieve a list of all applied coupons.
     */
    public function getAppliedCoupons(): array
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
}
