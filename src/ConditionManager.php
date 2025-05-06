<?php

namespace Soap\ShoppingCart;

use Closure;
use Soap\ShoppingCart\Contracts\ConditionInterface;
use Soap\ShoppingCart\Contracts\ConditionManagerInterface;
use Soap\ShoppingCart\Models\CartCondition;

class ConditionManager implements ConditionManagerInterface
{
    protected array $conditions = [];

    public function __construct(
        protected Closure $contextResolver // ← context provider
    ) {}

    public function register(array $conditions): void
    {
        $this->conditions = array_merge($this->conditions, $conditions);
    }

    public function registerIf(bool $condition, ConditionInterface $instance): void
    {
        if ($condition) {
            $this->register([$instance]);
        }
    }

    public function flush(): void
    {
        $this->conditions = [];
    }

    public function conditions(): array
    {
        return $this->conditions;
    }

    public function applicable(array $overrideContext = [], bool $objectAccess = true): array
    {
        $context = call_user_func($this->contextResolver, $objectAccess);

        return array_filter($this->conditions, fn ($c) => $c->passes($context));
    }

    protected function populate()
    {
        CartCondition::published()
            ->get()
            ->map(fn ($c) => $c->toExpressionCondition())
            ->pipe(fn ($conditions) => $this->register($conditions->all()));
    }
}
