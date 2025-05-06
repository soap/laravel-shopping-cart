<?php

namespace Soap\ShoppingCart\Supports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ExpressionContextBuilder
{
    protected ?Carbon $overrideNow = null;

    public function __construct(
        protected mixed $cart,
        protected ?Model $user = null,
    ) {}

    /**
     * Use a fixed "now" for testing or reproducible conditions.
     */
    public function withNow(Carbon $now): static
    {
        $this->overrideNow = $now;

        return $this;
    }

    /**
     * Build context array for expression evaluation.
     */
    public function build(bool $objectAccess = true): array
    {
        return [
            'cart' => $objectAccess ? $this->cart : $this->normalize($this->cart),
            'user' => $objectAccess ? $this->user : $this->normalize($this->user),
            'now' => $this->overrideNow ?? now(),
        ];
    }

    protected function normalize(mixed $model): mixed
    {
        if ($model instanceof Model) {
            return collect($model->getAttributes())
                ->merge($model->getRelations())
                ->map(fn ($value) => $value instanceof \DateTimeInterface ? Carbon::parse($value) : $value)
                ->toArray();
        }

        return $model;
    }
}
