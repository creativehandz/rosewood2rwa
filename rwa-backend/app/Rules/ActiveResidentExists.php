<?php

namespace App\Rules;

use App\Models\Resident;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveResidentExists implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $resident = Resident::find($value);

        if (!$resident) {
            $fail('The selected resident does not exist.');
            return;
        }

        if ($resident->current_state !== 'Occupied') {
            $fail('Payments can only be created for residents with occupied units.');
            return;
        }
    }
}