<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceCharge extends Model
{
    protected $fillable = [
        'month',
        'basic_maintenance',
        'additional_charges',
        'discount',
        'penalty',
        'due_date',
        'description',
        'status'
    ];

    protected $casts = [
        'due_date' => 'date',
        'basic_maintenance' => 'decimal:2',
        'additional_charges' => 'decimal:2',
        'discount' => 'decimal:2',
        'penalty' => 'decimal:2'
    ];

    /**
     * Calculate total maintenance charge
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->basic_maintenance + $this->additional_charges - $this->discount + $this->penalty;
    }

    /**
     * Scope for active charges
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get maintenance charge for a specific month
     */
    public static function getForMonth($month)
    {
        return self::where('month', $month)->first();
    }
}
