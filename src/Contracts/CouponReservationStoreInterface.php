<?php

namespace Soap\ShoppingCart\Contracts;

use Illuminate\Database\Eloquent\Model;

interface CouponReservationStoreInterface
{
    /**
     * Reserve a coupon for a specific reserver model (User, Admin, etc.).
     */
    public function reserve(string $code, Model $reserver, int $ttlSeconds = 900): void;

    /**
     * Release a reserved coupon for the specified reserver.
     */
    public function release(string $code, Model $reserver): void;

    /**
     * Check if a coupon is currently locked by the same reserver.
     */
    public function isLocked(string $code, Model $reserver): bool;

    /**
     * Check if a coupon is locked by any reserver other than the given one.
     */
    public function isLockedByOthers(string $code, Model $reserver): bool;

    /**
     * Count all active (non-expired) reservations for the given coupon code.
     */
    public function countActiveReservations(string $code): int;

    /**
     * Count all active reservations for the coupon, excluding the given reserver.
     */
    public function countActiveReservationsExcept(Model $reserver, string $code): int;
}
