<?php

namespace Soap\ShoppingCart\Exceptions;

use Exception;

class CouponNotAllowedToRedeemException extends Exception
{
    protected string $errorCode;

    public function __construct(string $message, ?string $errorCode = null)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode ?? 'COUPON_NOT_ALLOWED_TO_REDEEM';
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
