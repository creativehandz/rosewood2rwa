<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resident extends Model
{
    protected $fillable = [
        'flat_number',
        'owner_name',
        'contact_number',
        'email',
        'address',
        'status',
        'monthly_maintenance',
        'google_sheet_data'
    ];

    protected $casts = [
        'google_sheet_data' => 'array',
        'monthly_maintenance' => 'decimal:2'
    ];

    /**
     * Get all payments for this resident
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get paid payments for this resident
     */
    public function paidPayments(): HasMany
    {
        return $this->payments()->where('status', 'paid');
    }

    /**
     * Get pending payments for this resident
     */
    public function pendingPayments(): HasMany
    {
        return $this->payments()->where('status', 'pending');
    }

    /**
     * Check if resident is a payer (has recent payments)
     */
    public function isPayer(): bool
    {
        return $this->paidPayments()->exists();
    }

    /**
     * Get the latest payment status
     */
    public function getLatestPaymentStatus(): string
    {
        $latestPayment = $this->payments()->latest('payment_date')->first();
        return $latestPayment ? $latestPayment->status : 'no_payment';
    }
}
