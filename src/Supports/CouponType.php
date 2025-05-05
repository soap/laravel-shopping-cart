<?php

namespace Soap\ShoppingCart\Supports;

enum CouponType: string
{
    case Percentage = 'percentage';
    case Subtraction = 'subtraction';
    case Fixed = 'fixed';
}
