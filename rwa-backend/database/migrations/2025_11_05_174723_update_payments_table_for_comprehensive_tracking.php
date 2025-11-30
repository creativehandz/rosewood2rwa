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
        Schema::table('payments', function (Blueprint $table) {
            // Update existing columns to match new schema
            $table->renameColumn('amount', 'amount_due');
            $table->decimal('amount_paid', 10, 2)->default(0)->after('amount_due');
            
            // Update status enum to match Google Sheets values
            $table->dropColumn('status');
        });
        
        // Add the new status column with correct enum values
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Paid', 'Partial', 'Overdue'])->default('Pending')->after('remarks');
        });
        
        // Update payment method to match Google Sheets dropdown
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_method', ['Cash', 'UPI', 'Bank Transfer'])->nullable()->after('payment_date');
        });
        
        // Rename and add new columns
        Schema::table('payments', function (Blueprint $table) {
            $table->renameColumn('transaction_reference', 'transaction_id');
            
            // Add new columns for Google Sheets sync
            $table->integer('sheet_row_id')->nullable()->after('google_sheet_data');
            $table->timestamp('last_synced_at')->nullable()->after('sheet_row_id');
            
            // Remove due_date as we're using payment_month for tracking
            $table->dropColumn('due_date');
            
            // Add unique constraint to prevent duplicate payments per resident per month
            $table->unique(['resident_id', 'payment_month'], 'payments_resident_month_unique');
            
            // Add additional indexes for better performance
            $table->index(['status']);
            $table->index(['payment_method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Reverse all changes
            $table->renameColumn('amount_due', 'amount');
            $table->dropColumn(['amount_paid', 'sheet_row_id', 'last_synced_at']);
            $table->renameColumn('transaction_id', 'transaction_reference');
            $table->date('due_date')->after('payment_date');
            
            // Drop indexes and constraints
            $table->dropUnique('payments_resident_month_unique');
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_method']);
        });
        
        // Restore original status enum
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['paid', 'pending', 'overdue'])->default('pending')->after('remarks');
        });
        
        // Restore original payment method
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('status');
        });
    }
};
