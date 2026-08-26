<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained();

            // Snapshotted shipping address (not a FK - addresses can change/be
            // deleted after the order is placed).
            $table->string('shipping_recipient_name');
            $table->string('shipping_phone');
            $table->string('shipping_province');
            $table->string('shipping_district');
            $table->string('shipping_municipality');
            $table->string('shipping_ward_no')->nullable();
            $table->string('shipping_street_address')->nullable();

            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->string('currency')->default('NPR');
            $table->string('payment_method');
            $table->string('payment_status')->default('pending');
            $table->string('status')->default('pending_payment');
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
