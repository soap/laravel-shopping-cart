<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('coupon_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('coupon_code');
            $table->morphs('reserver'); // reserver_type, reserver_id
            $table->timestamp('reserved_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['coupon_code', 'reserver_type', 'reserver_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('coupon_reservations');
    }
};
