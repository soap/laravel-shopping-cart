<?php

namespace Soap\ShoppingCart\Adaptors;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Soap\ShoppingCart\Contracts\CouponInterface;

class NullCouponAdapter implements CouponInterface
{
    public function __construct(protected ?CouponDTO $dto = null) {}

    public function getCode(): string
    {
        return $this->dto->code ?? 'INVALID';
    }

    public function getDiscountType(): string
    {
        return 'none';
    }

    public function getDiscountValue(): float
    {
        return 0.0;
    }

    public function getAppliesTarget(): ?string
    {
        return null;
    }

    public function getExpiresAt(): ?Carbon
    {
        return null;
    }

    public function isExpired(): bool
    {
        return true;
    }

    public function isDisabled(): bool
    {
        return true;
    }

    public function isOverQuantity(): bool
    {
        return true;
    }

    public function isAllowedToRedeemBy(Model $user): bool
    {
        return false;
    }

    public function isOverLimitFor(Model $user): bool
    {
        return true;
    }

    public function getMinOrderValue(): ?float
    {
        return null;
    }

    public function getDiscountLimit(): ?float
    {
        return null;
    }
}
