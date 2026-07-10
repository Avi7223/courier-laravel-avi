<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id')->index();
            $table->string('courier')->index(); // steadfast, pathao, redx, ...
            $table->string('consignment_id')->nullable()->index();
            $table->string('tracking_number')->nullable();
            $table->string('status')->default('pending')->index(); // pending|picked|in_transit|delivered|returned|cancelled
            $table->string('label_url')->nullable();
            $table->decimal('charge', 10, 2)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_orders');
    }
};
