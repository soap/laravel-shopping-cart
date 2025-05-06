<?php

namespace Soap\ShoppingCart\Contracts;

interface ConditionManagerInterface
{
    /**
     * Register one or many condition definitions.
     *
     * @param  ConditionInterface[]  $conditions
     */
    public function register(array $conditions): void;

    /**
     * Get all registered conditions.
     *
     * @return ConditionInterface[]
     */
    public function conditions(): array;

    /**
     * Get all conditions that pass for the current context.
     *
     * @return ConditionInterface[]
     */
    public function applicable(array $context): array;

    /**
     * Reset conditions.
     */
    public function flush(): void;
}
