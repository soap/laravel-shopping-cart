<?php

namespace Soap\ShoppingCart\Exceptions;

use Exception;

class CouponOverQuantityException extends Exception
{
    protected string $errorCode;

    public function __construct(string $message, ?string $errorCode = null)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode ?? 'COUPON_OVER_QUANTITY';
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
