<?php

namespace Soap\ShoppingCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Soap\ShoppingCart\Models\CouponReservation;

class CouponReservationFactory extends Factory
{
    protected $model = CouponReservation::class;

    public function definition()
    {
        return [
            'resever_type' => 'App\Models\User',
            'reserver_id' => 1,
        ];
    }
}
