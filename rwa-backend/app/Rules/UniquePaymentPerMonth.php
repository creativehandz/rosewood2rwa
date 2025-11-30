<?php

namespace App\Rules;

use App\Models\Payment;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniquePaymentPerMonth implements ValidationRule
{
    protected int $residentId;
    protected ?int $excludePaymentId;

    public function __construct(int $residentId, ?int $excludePaymentId = null)
    {
        $this->residentId = $residentId;
        $this->excludePaymentId = $excludePaymentId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = Payment::where('resident_id', $this->residentId)
                       ->where('payment_month', $value);

        if ($this->excludePaymentId) {
            $query->where('id', '!=', $this->excludePaymentId);
        }

        if ($query->exists()) {
            $fail('A payment already exists for this resident and month.');
        }
    }
}