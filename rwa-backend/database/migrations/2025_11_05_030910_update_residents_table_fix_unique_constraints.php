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
        Schema::table('residents', function (Blueprint $table) {
            // First, drop the existing unique constraint on flat_number
            $table->dropUnique(['flat_number']);
            
            // Add a composite unique constraint on house_number and floor
            // This allows same house number on different floors
            $table->unique(['house_number', 'floor'], 'residents_house_floor_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('residents_house_floor_unique');
            
            // Restore the original unique constraint on flat_number
            $table->unique('flat_number');
        });
    }
};
