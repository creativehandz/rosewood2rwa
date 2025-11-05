<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update the enum definition to include both 'filled' and 'occupied'
        DB::statement("ALTER TABLE residents MODIFY COLUMN current_state ENUM('vacant', 'filled', 'occupied') DEFAULT 'occupied'");
        
        // Update existing 'filled' values to 'occupied'
        DB::table('residents')
            ->where('current_state', 'filled')
            ->update(['current_state' => 'occupied']);
            
        // Finally, update the enum definition to only have 'vacant' and 'occupied'
        DB::statement("ALTER TABLE residents MODIFY COLUMN current_state ENUM('vacant', 'occupied') DEFAULT 'occupied'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to 'filled'
        DB::table('residents')
            ->where('current_state', 'occupied')
            ->update(['current_state' => 'filled']);
            
        // Revert the enum definition
        DB::statement("ALTER TABLE residents MODIFY COLUMN current_state ENUM('vacant', 'filled') DEFAULT 'filled'");
    }
};