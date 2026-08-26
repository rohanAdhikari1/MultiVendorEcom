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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // NOTE: order_items is created later in the migration sequence (after
            // orders/vendor_payouts), so this column is intentionally left without
            // a foreign key constraint to avoid a circular migration dependency.
            // See the model docblock on Review::order_item_id for the same note.
            $table->unsignedBigInteger('order_item_id')->nullable();

            $table->unsignedTinyInteger('rating');
            $table->text('body')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
