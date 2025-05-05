<?php

namespace Soap\ShoppingCart;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Session\SessionManager;
use Soap\ShoppingCart\Adaptors\CouponDTO;
use Soap\ShoppingCart\Adaptors\CouponFactory;
use Soap\ShoppingCart\Contracts\CouponInterface;
use Soap\ShoppingCart\Contracts\CouponServiceInterface;
use Soap\ShoppingCart\Contracts\InstanceIdentifierInterface;
use Soap\ShoppingCart\Contracts\UserResolverInterface;
use Soap\ShoppingCart\Exceptions\CouponExpiredException;
use Soap\ShoppingCart\Exceptions\CouponMinimumOrderValueException;
use Soap\ShoppingCart\Exceptions\CouponNotAllowedToRedeemException;
use Soap\ShoppingCart\Exceptions\CouponNotFoundException;
use Soap\ShoppingCart\Exceptions\CouponOverLimitException;
use Soap\ShoppingCart\Exceptions\CouponOverQuantityException;

class CouponManager
{
    const DEFAULT_INSTANCE = 'default';

    /**
     * The user object.
     * This is used to apply coupons to the user.
     */
    protected ?Authenticatable $user = null;

    /**
     * Array that will hold each coupon data
     * indexed by coupon code.
     */
    protected array $coupons = [];

    private $instance;

    /**
     * Construct the Coupon Manager.
     * The CouponServiceInterface is resolved from the Laravel container.
     */
    public function __construct(
        protected SessionManager $session,
        protected Dispatcher $events,
        protected UserResolverInterface $userResolver,
        protected CouponServiceInterface $couponService)
    {
        $this->instance(self::DEFAULT_INSTANCE);
    }

    public function instance($instance = null): self
    {
        $instance = $instance ?: self::DEFAULT_INSTANCE;

        if ($instance instanceof InstanceIdentifierInterface) {
            $instance = $instance->getInstanceIdentifier();
        }

        $this->instance = 'coupons.'.$instance;

        return $this;
    }

    /**
     * Get the current coupon instance without the prefix.
     *
     * @return string
     */
    public function currentInstance()
    {
        return str_replace('coupons.', '', $this->instance);
    }

    public function resolveUser(int|string|null $userId = null, ?string $guard = null): self
    {
        $this->user = $this->userResolver->resolve($userId, $guard);

        return $this;
    }

    public function getUser(): ?Authenticatable
    {
        return $this->user;
    }

    public function add(string $couponCode): static
    {
        if ($this->has($couponCode)) {
            return $this;
        }

        $coupon = $this->couponService->getCouponByCode($couponCode);

        if (! $coupon) {
            throw new \Exception("Coupon not found: {$couponCode}");
        }

        return $this->addFromAdapter($coupon);
    }

    public function has(string $couponCode): bool
    {
        return isset($this->coupons[$couponCode]);
    }

    public function addFromAdapter(CouponInterface $couponAdater): static
    {
        $code = $couponAdater->getCode();

        // Prevent duplicate coupon addition.
        if ($this->has($code)) {
            throw new \Exception("Coupon already added: {$code}");
        }

        $dto = CouponDTO::fromAdapter($couponAdater);

        // Store coupon data:
        $this->coupons[$code] = [
            'coupon' => $dto->toArray(),
            'applies_target' => $dto->applies_target ?? 'subtotal', // default to subtotal if not specified
            'applied' => false, // not applied yet
            'discount' => 0,    // discount to be calculated later
        ];

        // Store the coupons in the session.
        $this->session->put($this->instance, $this->coupons);

        return $this;
    }

    public function markAsApplied(string $couponCode, float $discount = 0): static
    {
        if (! isset($this->coupons[$couponCode])) {
            throw new \Exception("Coupon not found: {$couponCode}");
        }

        $this->coupons[$couponCode]['applied'] = true;
        $this->coupons[$couponCode]['discount'] = $discount;

        $this->session->put($this->instance, $this->coupons);

        return $this;
    }

    public function markAsUnapplied(string $couponCode): static
    {
        if (! isset($this->coupons[$couponCode])) {
            throw new \Exception("Coupon not found: {$couponCode}");
        }

        $this->coupons[$couponCode]['applied'] = false;
        $this->coupons[$couponCode]['discount'] = 0;

        $this->session->put($this->instance, $this->coupons);

        return $this;
    }

    public function remove(string $couponCode)
    {
        if (! isset($this->coupons[$couponCode])) {
            throw new \Exception("Coupon not found: {$couponCode}");
        }

        unset($this->coupons[$couponCode]);

        $this->session->put($this->instance, $this->coupons);

        return $this;
    }

    public function get(string $couponCode): ?array
    {
        if (! isset($this->coupons[$couponCode])) {
            return null;
        }

        $data = $this->coupons[$couponCode];

        return [
            'coupon' => CouponFactory::fromDTO(new CouponDTO(...$data['coupon'])),
            'applies_to' => $data['applies_target'],
            'discount' => $data['discount'],
            'applied' => $data['applied'],
        ];
    }

    public function all(): array
    {
        return $this->coupons;
    }

    public function allWithAdapters(): array
    {
        return collect($this->coupons)
            ->map(fn ($data) => [
                'coupon' => CouponFactory::fromDTO(new CouponDTO(...$data['coupon'])),
                'applies_to' => $data['applies_target'],
                'discount' => $data['discount'],
                'applied' => $data['applied'],
            ])
            ->all();
    }

    public function resolvedCoupons(bool $appliedOnly = false): array
    {
        return collect($this->coupons)
            ->filter(function ($data) use ($appliedOnly) {
                return ! $appliedOnly || ($data['applied'] ?? false);
            })
            ->map(function ($data, $code) {
                return [
                    'code' => $code,
                    'coupon' => CouponFactory::fromDTO(new CouponDTO(...$data['coupon'])),
                    'applies_to' => $data['applies_target'],
                    'discount' => $data['discount'],
                    'applied' => $data['applied'],
                ];
            })
            ->values()
            ->all();
    }

    public function apply(string $couponCode, ?ShoppingCart $cart = null, int|string|null $userId = null, ?string $guard = null): self
    {
        $this->verify($couponCode, $cart, $userId, $guard);

        $this->user = $this->userResolver->resolve($userId, $guard);

        if (! $this->user) {
            throw new \Exception('No authenticated user found to apply coupon.');
        }

        // Apply the coupon to the cart.
        $appliedCoupon = $this->couponService->applyCoupon($couponCode, $cart->finalSubtotal(), $this->user);

        if (! $appliedCoupon) {
            throw new \Exception("Failed to apply coupon: {$couponCode}");
        }

        // Mark the coupon as applied.
        $this->coupons[$appliedCoupon->getCode()]['applied'] = true;
        $this->session->put($this->instance, $this->coupons);

        return $this;
    }

    public function verify(string $couponCode, ?ShoppingCart $cart = null, int|string|null $userId = null, ?string $guard = null): bool
    {
        $this->user = $this->userResolver->resolve($userId, $guard);

        if (! $this->user) {
            throw new \Exception('No authenticated user found to apply coupon.');
        }

        if (! $this->has($couponCode)) {
            $coupon = $this->couponService->getCouponByCode($couponCode);
            if (! $coupon) {
                throw new \Exception("Coupon not found: {$couponCode}");
            }
            $this->addFromAdapter($coupon);
        }

        $coupon = $this->get($couponCode)['coupon']; // Get the coupon adapter

        if (! $coupon) {
            throw new CouponNotFoundException("Coupon not found: {$couponCode}");
        }

        if ($coupon->isDisabled()) {
            throw new \Exception("Coupon is disabled: {$couponCode}");
        }

        if ($coupon->isExpired()) {
            throw new CouponExpiredException("Coupon expired: {$couponCode}");
        }

        if ($coupon->isOverQuantity()) {
            throw new CouponOverQuantityException("Coupon quantity exceeded: {$couponCode}");
        }

        if ($coupon->getMinOrderValue() !== null && $cart->initialSubtotalFloat() < $coupon->getMinOrderValue()) {
            throw new CouponMinimumOrderValueException("Coupon minimum order value not met: {$couponCode}");
        }

        if ($this->user instanceof Model) {
            if (! $coupon->isAllowedToRedeemBy($this->user)) {
                throw new CouponNotAllowedToRedeemException("Coupon not allowed to redeem: {$couponCode}");
            }

            if ($coupon->isOverLimitFor($this->user)) {
                throw new CouponOverLimitException("Coupon over limit for user: {$couponCode}");
            }
        }

        return true;
    }

    /**
     * Retrieve a list of all applied coupons.
     */
    public function appliedCoupons(): array
    {
        return collect($this->coupons)
            ->filter(fn ($c) => $c['applied'] === true)
            ->map(fn ($c) => CouponFactory::fromDTO(new CouponDTO(...$c['coupon'])))
            ->values()
            ->all();
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
            $this->addFromAdapter($coupon);
        }
    }
}
