<?php

namespace Soap\ShoppingCart\Exceptions;

class CouponMinimumOrderValueException extends \Exception
{
    protected string $errorCode;

    public function __construct(string $message, ?string $errorCode = null)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode ?? 'COUPON_MINIMUM_ORDER_VALUE';
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
