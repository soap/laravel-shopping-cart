<?php

namespace Soap\ShoppingCart\ReservationStores;

use Illuminate\Database\Eloquent\Model;
use Soap\ShoppingCart\Contracts\CouponReservationStoreInterface;
use Soap\ShoppingCart\Models\CouponReservation;

class DatabaseCouponReservationStore implements CouponReservationStoreInterface
{
    public function reserve(string $code, Model $model, int $ttlSeconds = 900): void
    {
        CouponReservation::updateOrCreate([
            'coupon_code' => $code,
            'reserver_type' => $model::class,
            'reserver_id' => $model->getKey(),
        ], [
            'reserved_at' => now(),
            'expires_at' => now()->addSeconds($ttlSeconds),
        ]);
    }

    public function release(string $code, Model $model): void
    {
        CouponReservation::where([
            'coupon_code' => $code,
            'reserver_type' => $model::class,
            'reserver_id' => $model->getKey(),
        ])->delete();
    }

    public function isLocked(string $code, Model $model): bool
    {
        return CouponReservation::where([
            'coupon_code' => $code,
            'reserver_type' => $model::class,
            'reserver_id' => $model->getKey(),
        ])->where('expires_at', '>', now())->exists();
    }

    public function isLockedByOthers(string $code, Model $model): bool
    {
        return CouponReservation::where('coupon_code', $code)
            ->where('reserver_type', $model::class)
            ->where('reserver_id', '!=', $model->getKey())
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function countActiveReservations(string $code): int
    {
        return CouponReservation::where('coupon_code', $code)
            ->where('expires_at', '>', now())
            ->count();
    }

    public function countActiveReservationsExcept(Model $reserver, string $code): int
    {
        return CouponReservation::where('coupon_code', $code)
            ->where('reserver_type', '!=', $reserver::class)
            ->orWhere(function ($query) use ($reserver) {
                $query->where('reserver_type', $reserver::class)
                    ->where('reserver_id', '!=', $reserver->getKey());
            })
            ->where('expires_at', '>', now())
            ->count();
    }
}
