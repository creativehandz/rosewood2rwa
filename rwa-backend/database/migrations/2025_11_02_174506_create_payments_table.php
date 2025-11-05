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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->date('payment_date')->nullable();
            $table->date('due_date');
            $table->string('payment_month'); // e.g., "2025-11" for November 2025
            $table->enum('status', ['paid', 'pending', 'overdue'])->default('pending');
            $table->string('payment_method')->nullable(); // cash, online, cheque, etc.
            $table->string('transaction_reference')->nullable();
            $table->text('remarks')->nullable();
            $table->json('google_sheet_data')->nullable(); // Store original Google Sheet data
            $table->timestamps();
            
            // Index for efficient queries
            $table->index(['resident_id', 'payment_month']);
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
