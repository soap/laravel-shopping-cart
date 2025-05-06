<?php

namespace Soap\ShoppingCart\Models;

use Illuminate\Database\Eloquent\Model;
use Soap\ShoppingCart\Conditions\ExpressionCondition;

/**
 * @property string $slug
 * @property string $expression
 * @property float $value
 * @property string $type
 * @property string $target
 * @property int|null $quantity
 * @property int|null $limit
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $published_up
 * @property \Illuminate\Support\Carbon|null $published_down
 */
class CartCondition extends Model
{
    protected $fillable = [
        'slug', 'expression', 'value', 'type', 'target',
        'quantity', 'limit',
        'is_active', 'published_up', 'published_down',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'published_up' => 'datetime',
        'published_down' => 'datetime',
        'value' => 'float',
    ];

    public function usages()
    {
        return $this->hasMany(ConditionUsage::class);
    }

    public function isPublished(): bool
    {
        $now = now();

        return $this->is_active &&
            (! $this->published_up || $now->gte($this->published_up)) &&
            (! $this->published_down || $now->lte($this->published_down));
    }

    public function toExpressionCondition(): ExpressionCondition
    {
        return new ExpressionCondition(
            slug: $this->slug,
            expression: $this->expression,
            value: $this->value,
            type: $this->type,
            target: $this->target
        );
    }

    public function scopePublished($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('published_up')->orWhere('published_up', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('published_down')->orWhere('published_down', '>=', $now);
            });
    }

    public function remaining(): ?int
    {
        return $this->quantity;
    }

    public function isOutOfStock(): bool
    {
        return ! is_null($this->quantity) && $this->quantity <= 0;
    }

    public function timesUsedBy(Model $user): int
    {
        return $this->usages()
            ->where('user_type', $user::class)
            ->where('user_id', $user->getKey())
            ->count();
    }

    public function isPublishedFor(?Model $user = null): bool
    {
        $now = now();

        if (! $this->is_active) {
            return false;
        }
        if ($this->published_up && $now->lt($this->published_up)) {
            return false;
        }
        if ($this->published_down && $now->gt($this->published_down)) {
            return false;
        }
        if ($this->isOutOfStock()) {
            return false;
        }

        if ($user && $this->limit !== null) {
            if ($this->timesUsedBy($user) >= $this->limit) {
                return false;
            }
        }

        return true;
    }

    public function canBeUsedBy(?Model $user): bool
    {
        if (! $user || is_null($this->limit)) {
            return true;
        }

        return $this->timesUsedBy($user) < $this->limit;
    }
}
