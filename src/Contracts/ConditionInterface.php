<?php

namespace Soap\ShoppingCart\Contracts;

interface ConditionInterface
{
    public function passes(array $context, mixed $value = null): bool;

    public function name(): string;

    public function expression(): string;

    public function value(): float|int;

    public function payload(): array;
}
