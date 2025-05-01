<?php

namespace Soap\ShoppingCart\Exceptions;

use Exception;

class CouponNotFoundException extends Exception
{
    protected string $errorCode;

    public function __construct(string $message, ?string $errorCode = null)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode ?? 'COUPON_NOT_FOUND';
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
