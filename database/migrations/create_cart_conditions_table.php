<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->text('expression');
            $table->float('value');
            $table->enum('type', ['percentage', 'subtraction', 'fixed'])->default('percentage');
            $table->enum('target', ['item', 'subtotal', 'total'])->default('subtotal');
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_up')->nullable();
            $table->timestamp('published_down')->nullable();

            // 🆕 Tracking fields:
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('limit')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->timestamps();
        });

        Schema::create('condition_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_condition_id')->constrained()->cascadeOnDelete();
            $table->morphs('user'); // user_type, user_id
            $table->timestamp('used_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_conditions');
    }
};
