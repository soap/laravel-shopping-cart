<?php

namespace Soap\ShoppingCart\Conditions;

use Soap\ShoppingCart\Contracts\ConditionInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionCondition implements ConditionInterface
{
    public function __construct(
        protected string $slug,
        protected string $expression,
        protected float|int $value,
        protected string $type = 'percentage',  // 'percentage' | 'subtraction' | 'fixed'
        protected string $target = 'subtotal'    // 'item' | 'subtotal' | 'total'
    ) {}

    public function name(): string
    {
        return $this->slug;
    }

    public function expression(): string
    {
        return $this->expression;
    }

    public function value(): float|int
    {
        return $this->value;
    }

    public function passes(array $context, mixed $value = null): bool
    {
        $engine = new ExpressionLanguage;

        return (bool) $engine->evaluate($this->expression, $context);
    }

    public function payload(): array
    {
        return [
            'value' => $this->value,
            'type' => $this->type,
            'target' => $this->target,
        ];
    }
}
