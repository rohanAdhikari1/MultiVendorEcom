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
        Schema::table('site_settings', function (Blueprint $table) {
            // Defaults match what the storefront already looks like today
            // (rounded-md/rounded-lg corners, shadow-sm/shadow-lg depth,
            // p-3/py-10 spacing, solid primary buttons), so migrating an
            // existing install never visibly changes anything until an
            // admin deliberately picks a different option.
            $table->string('border_radius_scale')->default('rounded')->after('font_family');
            $table->string('shadow_style')->default('soft')->after('border_radius_scale');
            $table->string('layout_density')->default('comfortable')->after('shadow_style');
            $table->string('button_style')->default('solid')->after('layout_density');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'border_radius_scale',
                'shadow_style',
                'layout_density',
                'button_style',
            ]);
        });
    }
};
