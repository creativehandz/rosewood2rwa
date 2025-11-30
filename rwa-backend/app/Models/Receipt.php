<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Receipt extends Model
{
    protected $fillable = [
        'payment_id',
        'receipt_number',
        'receipt_date',
        'amount',
        'tax_amount',
        'total_amount',
        'notes'
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2'
    ];

    /**
     * Get the payment that owns the receipt
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Generate a unique receipt number
     */
    public static function generateReceiptNumber(): string
    {
        $date = Carbon::now()->format('Ymd');
        $lastReceipt = self::whereDate('created_at', Carbon::today())
                          ->orderBy('id', 'desc')
                          ->first();
        
        $sequence = $lastReceipt ? (intval(substr($lastReceipt->receipt_number, -3)) + 1) : 1;
        
        return 'RWA' . $date . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
}
