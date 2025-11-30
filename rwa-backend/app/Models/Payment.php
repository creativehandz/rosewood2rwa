<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'resident_id',
        'payment_month',
        'amount_due',
        'amount_paid',
        'payment_date',
        'payment_method',
        'transaction_id',
        'status',
        'remarks',
        'sheet_row_id',
        'last_synced_at',
        'google_sheet_data'
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'payment_date' => 'date',
        'last_synced_at' => 'datetime',
        'google_sheet_data' => 'array'
    ];

    /**
     * Get the resident that owns the payment
     */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    /**
     * Get the receipt for this payment
     */
    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }

    /**
     * Calculate the balance due
     */
    public function getBalanceDueAttribute(): float
    {
        return (float) ($this->amount_due - $this->amount_paid);
    }

    /**
     * Check if payment is fully paid
     */
    public function isFullyPaid(): bool
    {
        return $this->amount_paid >= $this->amount_due;
    }

    /**
     * Check if payment is partial
     */
    public function isPartial(): bool
    {
        return $this->amount_paid > 0 && $this->amount_paid < $this->amount_due;
    }

    /**
     * Check if payment is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'Overdue';
    }

    /**
     * Scope: Filter by payment month
     */
    public function scopeForMonth($query, string $month)
    {
        return $query->where('payment_month', $month);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by resident
     */
    public function scopeForResident($query, int $residentId)
    {
        return $query->where('resident_id', $residentId);
    }

    /**
     * Scope: Pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    /**
     * Scope: Paid payments
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'Paid');
    }

    /**
     * Scope: Overdue payments
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'Overdue');
    }

    /**
     * Get payments with resident details
     */
    public function scopeWithResident($query)
    {
        return $query->with(['resident:id,house_number,floor,owner_name,contact_number']);
    }

    /**
     * Auto-update status based on amount paid
     */
    public function updateStatus(): void
    {
        if ($this->amount_paid >= $this->amount_due) {
            $this->status = 'Paid';
        } elseif ($this->amount_paid > 0) {
            $this->status = 'Partial';
        } else {
            $this->status = 'Pending';
        }
        $this->save();
    }

    /**
     * Get display name for the payment
     */
    public function getDisplayNameAttribute(): string
    {
        return "Payment for {$this->resident->house_number} ({$this->resident->owner_name}) - {$this->payment_month}";
    }

    /**
     * Calculate late fee based on days overdue
     */
    public function calculateLateFee(float $lateFeePercentage = 2.0, int $graceDays = 5): float
    {
        $dueDate = $this->getDueDateAttribute();
        $today = now();
        
        if ($this->status === 'Paid' || $today <= $dueDate->addDays($graceDays)) {
            return 0;
        }
        
        $daysOverdue = $today->diffInDays($dueDate) - $graceDays;
        return ($this->amount_due * $lateFeePercentage / 100) * ceil($daysOverdue / 30);
    }

    /**
     * Get the due date for the payment (last day of payment month)
     */
    public function getDueDateAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromFormat('Y-m', $this->payment_month)->endOfMonth();
    }

    /**
     * Get days until due date (negative if overdue)
     */
    public function getDaysUntilDueAttribute(): int
    {
        $dueDate = $this->getDueDateAttribute();
        return now()->diffInDays($dueDate, false);
    }

    /**
     * Check if payment is overdue based on date
     */
    public function isOverdueByDate(): bool
    {
        return $this->getDaysUntilDueAttribute() < 0 && !$this->isFullyPaid();
    }

    /**
     * Get payment efficiency percentage
     */
    public function getPaymentEfficiencyAttribute(): float
    {
        if ($this->amount_due == 0) return 100;
        return round(($this->amount_paid / $this->amount_due) * 100, 2);
    }

    /**
     * Format amount for display
     */
    public function getFormattedAmountDueAttribute(): string
    {
        return '₹' . number_format($this->amount_due, 2);
    }

    /**
     * Format amount paid for display
     */
    public function getFormattedAmountPaidAttribute(): string
    {
        return '₹' . number_format($this->amount_paid, 2);
    }

    /**
     * Format balance due for display
     */
    public function getFormattedBalanceDueAttribute(): string
    {
        return '₹' . number_format($this->getBalanceDueAttribute(), 2);
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'Paid' => 'success',
            'Partial' => 'warning',
            'Overdue' => 'danger',
            'Pending' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Get payment method icon for UI
     */
    public function getPaymentMethodIconAttribute(): string
    {
        return match($this->payment_method) {
            'Cash' => 'fas fa-money-bill-wave',
            'UPI' => 'fas fa-mobile-alt',
            'Bank Transfer' => 'fas fa-university',
            default => 'fas fa-credit-card'
        };
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by amount range
     */
    public function scopeAmountRange($query, $minAmount, $maxAmount)
    {
        return $query->whereBetween('amount_due', [$minAmount, $maxAmount]);
    }

    /**
     * Scope: Filter by payment method
     */
    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope: Get recent payments
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('payment_date', '>=', now()->subDays($days));
    }

    /**
     * Scope: Get payments with balance due
     */
    public function scopeWithBalance($query)
    {
        return $query->whereRaw('amount_paid < amount_due');
    }

    /**
     * Scope: Get fully paid payments
     */
    public function scopeFullyPaid($query)
    {
        return $query->whereRaw('amount_paid >= amount_due');
    }

    /**
     * Scope: Search payments by resident details
     */
    public function scopeSearchResident($query, string $search)
    {
        return $query->whereHas('resident', function($q) use ($search) {
            $q->where('house_number', 'LIKE', "%{$search}%")
              ->orWhere('owner_name', 'LIKE', "%{$search}%")
              ->orWhere('contact_number', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Auto-update payment status based on business rules
     */
    public function updateStatusByRules(): void
    {
        $previousStatus = $this->status;
        
        if ($this->amount_paid >= $this->amount_due) {
            $this->status = 'Paid';
        } elseif ($this->amount_paid > 0) {
            $this->status = 'Partial';
        } elseif ($this->isOverdueByDate()) {
            $this->status = 'Overdue';
        } else {
            $this->status = 'Pending';
        }

        if ($previousStatus !== $this->status) {
            $this->save();
            
            // Fire event for status change
            event(new \App\Events\PaymentStatusChanged($this, $previousStatus));
        }
    }

    /**
     * Record partial payment
     */
    public function recordPartialPayment(float $amount, array $details = []): bool
    {
        if ($amount <= 0 || ($this->amount_paid + $amount) > $this->amount_due) {
            return false;
        }

        $this->amount_paid += $amount;
        
        if (!empty($details)) {
            $this->payment_date = $details['payment_date'] ?? now();
            $this->payment_method = $details['payment_method'] ?? $this->payment_method;
            $this->transaction_id = $details['transaction_id'] ?? $this->transaction_id;
            $this->remarks = $details['remarks'] ?? $this->remarks;
        }

        $this->updateStatusByRules();
        return true;
    }

    /**
     * Get payment history summary
     */
    public function getPaymentHistoryAttribute(): array
    {
        return [
            'total_due' => $this->amount_due,
            'total_paid' => $this->amount_paid,
            'balance_due' => $this->getBalanceDueAttribute(),
            'payment_efficiency' => $this->getPaymentEfficiencyAttribute(),
            'days_until_due' => $this->getDaysUntilDueAttribute(),
            'is_overdue' => $this->isOverdueByDate(),
            'late_fee' => $this->calculateLateFee(),
            'status_color' => $this->getStatusColorAttribute()
        ];
    }

    /**
     * Export payment data for reports
     */
    public function toExportArray(): array
    {
        return [
            'house_number' => $this->resident->house_number,
            'floor' => $this->resident->floor,
            'owner_name' => $this->resident->owner_name,
            'payment_month' => $this->payment_month,
            'amount_due' => $this->amount_due,
            'amount_paid' => $this->amount_paid,
            'balance_due' => $this->getBalanceDueAttribute(),
            'payment_date' => $this->payment_date?->format('Y-m-d'),
            'payment_method' => $this->payment_method,
            'transaction_id' => $this->transaction_id,
            'status' => $this->status,
            'days_until_due' => $this->getDaysUntilDueAttribute(),
            'late_fee' => $this->calculateLateFee(),
            'remarks' => $this->remarks
        ];
    }

    /**
     * Boot method to register model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            // Auto-set status if not provided
            if (!$payment->status) {
                if ($payment->amount_paid >= $payment->amount_due) {
                    $payment->status = 'Paid';
                } elseif ($payment->amount_paid > 0) {
                    $payment->status = 'Partial';
                } else {
                    $payment->status = 'Pending';
                }
            }
        });

        static::updating(function ($payment) {
            // Auto-update status when amounts change
            if ($payment->isDirty(['amount_due', 'amount_paid'])) {
                $payment->updateStatusByRules();
                
                // Trigger automatic recalculation if amount_due or amount_paid changed
                if ($payment->isDirty('amount_due') || $payment->isDirty('amount_paid')) {
                    // Import controller for recalculation logic
                    $controller = app(\App\Http\Controllers\Web\PaymentManagementController::class);
                    $controller->handlePaymentUpdateRecalculation($payment);
                }
            }
        });

        static::created(function ($payment) {
            event(new \App\Events\PaymentCreated($payment));
        });

        static::updated(function ($payment) {
            event(new \App\Events\PaymentUpdated($payment));
        });

        static::deleted(function ($payment) {
            event(new \App\Events\PaymentDeleted($payment));
        });
    }
}
