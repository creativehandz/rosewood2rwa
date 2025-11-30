<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPaymentAmount implements ValidationRule
{
    protected float $amountDue;

    public function __construct(float $amountDue)
    {
        $this->amountDue = $amountDue;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value < 0) {
            $fail('The payment amount cannot be negative.');
            return;
        }

        if ($value > $this->amountDue) {
            $fail('The payment amount cannot exceed the amount due (₹' . number_format($this->amountDue, 2) . ').');
            return;
        }
    }
}