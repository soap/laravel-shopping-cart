<?php

namespace Soap\ShoppingCart\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $coupon_code
 * @property string $reserver_type
 * @property int $reserver_id
 * @property \Illuminate\Database\Eloquent\Model $reserver
 * @property \Illuminate\Support\Carbon|null $reserved_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 */
class CouponReservation extends Model
{
    protected $fillable = ['coupon_code', 'reserver_type', 'reserver_id', 'reserved_at', 'expires_at'];

    public function reserver()
    {
        return $this->morphTo();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
