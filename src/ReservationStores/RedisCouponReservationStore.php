<?php

namespace Soap\ShoppingCart\ReservationStores;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Soap\ShoppingCart\Contracts\CouponReservationStoreInterface;

class RedisCouponReservationStore implements CouponReservationStoreInterface
{
    protected string $prefix = 'cart:condition:reservation:';

    public function reserve(string $code, Model $reserver, int $ttl = 300): void
    {
        $key = $this->getKey($code, $reserver);
        Redis::connection()->setex($key, $ttl, now()->timestamp);
    }

    public function release(string $code, Model $reserver): void
    {
        Redis::connection()->del($this->getKey($code, $reserver));
    }

    public function isLocked(string $code, Model $reserver): bool
    {
        $value = Redis::connection()->exists($this->getKey($code, $reserver));

        return (int) $value > 0;
    }

    public function isLockedByOthers(string $code, Model $reserver): bool
    {
        return $this->countActiveReservationsExcept($reserver, $code) > 0;
    }

    public function getReservedAt(string $code, Model $reserver): ?Carbon
    {
        $timestamp = Redis::connection()->get($this->getKey($code, $reserver));

        return $timestamp ? Carbon::createFromTimestamp((int) $timestamp) : null;
    }

    protected function getKey(string $code, Model|string $reserver): string
    {
        return $this->prefix.$code.':'.$this->reserverKey($reserver);
    }

    protected function reserverKey(Model|string $reserver): string
    {
        if (is_string($reserver)) {
            return $reserver;
        }

        return strtolower(class_basename($reserver)).':'.$reserver->getKey();
    }

    public function countActiveReservations(string $code): int
    {
        $pattern = $this->prefix.$code.':*';

        return count(Redis::connection()->keys($pattern));
    }

    public function countActiveReservationsExcept(Model $reserver, string $code): int
    {
        $pattern = $this->prefix.$code.':*';
        $allKeys = Redis::connection()->keys($pattern);
        $excludedKey = $this->getKey($code, $reserver);

        return count(array_filter($allKeys, fn ($key) => $key !== $excludedKey));
    }
}
