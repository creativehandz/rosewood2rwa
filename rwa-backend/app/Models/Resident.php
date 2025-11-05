<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resident extends Model
{
    protected $fillable = [
        'house_number',
        'flat_number',
        'property_type',
        'floor',
        'owner_name',
        'contact_number',
        'email',
        'address',
        'status',
        'current_state',
        'monthly_maintenance',
        'move_in_date',
        'emergency_contact',
        'emergency_phone',
        'remarks',
        'google_sheet_data'
    ];

    protected $casts = [
        'google_sheet_data' => 'array',
        'remarks' => 'array',
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

    /**
     * Add a new remark
     */
    public function addRemark(string $remark, string $addedBy = 'Admin'): void
    {
        $remarks = $this->remarks ?? [];
        $remarks[] = [
            'text' => $remark,
            'added_by' => $addedBy,
            'added_at' => now()->toISOString()
        ];
        $this->update(['remarks' => $remarks]);
    }

    /**
     * Get all remarks ordered by date (newest first)
     */
    public function getOrderedRemarks(): array
    {
        $remarks = $this->remarks ?? [];
        return array_reverse($remarks); // Show newest first
    }

    /**
     * Get remarks count
     */
    public function getRemarksCount(): int
    {
        return count($this->remarks ?? []);
    }
}
