<?php

namespace Soap\ShoppingCart\Exceptions;

use Exception;

class CouponDisabledException extends Exception
{
    protected string $errorCode;

    public function __construct(string $message, ?string $errorCode = null)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode ?? 'COUPON_DISABLED';
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
