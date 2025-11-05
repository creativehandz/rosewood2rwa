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
            // Add new fields
            $table->string('house_number')->nullable()->after('id');
            $table->enum('property_type', [
                'house',
                '3bhk_flat',
                'villa',
                '2bhk_flat',
                '1bhk_flat',
                'estonia_1',
                'estonia_2',
                'plot'
            ])->default('house')->after('house_number');
            $table->enum('current_state', ['vacant', 'filled'])->default('filled')->after('status');
            
            // Make contact_number required (remove nullable)
            $table->string('contact_number')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn(['house_number', 'property_type', 'current_state']);
            $table->string('contact_number')->nullable()->change();
        });
    }
};