<?php

namespace Soap\ShoppingCart\Supports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Soap\ShoppingCart\Contracts\UserResolverInterface;

class ConditionContextFactory
{
    public function __construct(
        protected UserResolverInterface $userResolver
    ) {}

    protected mixed $cart = null;

    protected ?Model $user = null;

    protected ?Carbon $now = null;

    public function useCart(mixed $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    public function useUser(?Model $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function useNow(Carbon $now): static
    {
        $this->now = $now;

        return $this;
    }

    public function buildFromRuntime(bool $objectAccess = true, int|string|null $userId = null, ?string $guard = null): array
    {
        $cart = $this->cart ?? app('shopping-cart') ?? null;

        $user = $this->user ?? $this->userResolver->resolve($userId, $guard);

        $builder = new ExpressionContextBuilder($cart, $user);

        if ($this->now) {
            $builder->withNow($this->now);
        }

        return $builder->build($objectAccess);
    }
}
