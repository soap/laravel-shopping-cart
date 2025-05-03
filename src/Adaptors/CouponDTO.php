<?php

namespace Soap\ShoppingCart\Adaptors;

use JsonSerializable;
use Soap\ShoppingCart\Contracts\CouponInterface;

class CouponDTO implements JsonSerializable
{
    public function __construct(
        public string $code,
        public string $discountType,
        public float $discountValue,
        public ?string $appliesTarget,
        public ?string $expiresAt,
        public ?float $minOrderValue,
        public ?float $discountLimit,
    ) {}

    public static function fromAdapter(CouponInterface $adapter): self
    {
        return new self(
            $adapter->getCode(),
            $adapter->getDiscountType(),
            $adapter->getDiscountValue(),
            $adapter->getAppliesTarget(),
            optional($adapter->getExpiresAt())?->toISOString(),
            $adapter->getMinOrderValue(),
            $adapter->getDiscountLimit(),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
