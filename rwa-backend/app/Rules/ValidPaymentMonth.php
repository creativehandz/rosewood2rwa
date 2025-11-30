<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPaymentMonth implements ValidationRule
{
    protected bool $allowFuture;

    public function __construct(bool $allowFuture = false)
    {
        $this->allowFuture = $allowFuture;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check format
        if (!preg_match('/^\d{4}-\d{2}$/', $value)) {
            $fail('The payment month must be in YYYY-MM format.');
            return;
        }

        // Validate the month is a real date
        $parts = explode('-', $value);
        $year = (int) $parts[0];
        $month = (int) $parts[1];

        if ($month < 1 || $month > 12) {
            $fail('The payment month must be a valid month (01-12).');
            return;
        }

        if ($year < 2020 || $year > 2050) {
            $fail('The payment year must be between 2020 and 2050.');
            return;
        }

        // Check if future months are allowed
        if (!$this->allowFuture) {
            $currentMonth = now()->format('Y-m');
            if ($value > $currentMonth) {
                $fail('Future payment months are not allowed.');
                return;
            }
        }

        // Check if the month is too old (more than 2 years back)
        $twoYearsAgo = now()->subYears(2)->format('Y-m');
        if ($value < $twoYearsAgo) {
            $fail('Payment months older than 2 years are not allowed.');
            return;
        }
    }
}