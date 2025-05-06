<?php

namespace Soap\ShoppingCart\Models;

use Illuminate\Database\Eloquent\Model;

class ConditionUsage extends Model
{
    protected $fillable = ['cart_condition_id', 'user_type', 'user_id', 'used_at'];

    public function condition()
    {
        return $this->belongsTo(CartCondition::class);
    }

    public function user()
    {
        return $this->morphTo();
    }
}
