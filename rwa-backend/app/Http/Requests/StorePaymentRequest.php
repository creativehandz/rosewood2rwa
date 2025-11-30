<?php

namespace App\Http\Requests;

use App\Rules\ActiveResidentExists;
use App\Rules\UniquePaymentPerMonth;
use App\Rules\ValidPaymentAmount;
use App\Rules\ValidPaymentMonth;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'resident_id' => [
                'required',
                'integer',
                new ActiveResidentExists()
            ],
            'payment_month' => [
                'required',
                'string',
                new ValidPaymentMonth(),
                new UniquePaymentPerMonth($this->input('resident_id'))
            ],
            'amount_due' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99'
            ],
            'amount_paid' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $amountDue = $this->input('amount_due');
                    if ($amountDue && $value > $amountDue) {
                        $fail('The amount paid cannot exceed the amount due.');
                    }
                }
            ],
            'payment_date' => [
                'nullable',
                'date',
                'before_or_equal:today'
            ],
            'payment_method' => [
                'nullable',
                'in:Cash,UPI,Bank Transfer'
            ],
            'transaction_id' => [
                'nullable',
                'string',
                'max:255',
                'unique:payments,transaction_id'
            ],
            'status' => [
                'nullable',
                'in:Pending,Paid,Partial,Overdue'
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'sheet_row_id' => [
                'nullable',
                'integer',
                'min:1'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'resident_id.required' => 'Please select a resident.',
            'payment_month.required' => 'Payment month is required.',
            'amount_due.required' => 'Amount due is required.',
            'amount_due.min' => 'Amount due must be a positive number.',
            'amount_due.max' => 'Amount due cannot exceed ₹999,999.99.',
            'amount_paid.required' => 'Amount paid is required.',
            'amount_paid.min' => 'Amount paid must be a positive number or zero.',
            'payment_date.before_or_equal' => 'Payment date cannot be in the future.',
            'payment_method.in' => 'Payment method must be Cash, UPI, or Bank Transfer.',
            'transaction_id.unique' => 'This transaction ID already exists.',
            'transaction_id.max' => 'Transaction ID cannot exceed 255 characters.',
            'status.in' => 'Status must be Pending, Paid, Partial, or Overdue.',
            'remarks.max' => 'Remarks cannot exceed 1000 characters.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'resident_id' => 'resident',
            'payment_month' => 'payment month',
            'amount_due' => 'amount due',
            'amount_paid' => 'amount paid',
            'payment_date' => 'payment date',
            'payment_method' => 'payment method',
            'transaction_id' => 'transaction ID',
            'sheet_row_id' => 'sheet row ID'
        ];
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-set payment date if amount paid > 0 and no date provided
        if ($this->input('amount_paid') > 0 && !$this->has('payment_date')) {
            $this->merge([
                'payment_date' => now()->toDateString()
            ]);
        }

        // Auto-calculate status if not provided
        if (!$this->has('status')) {
            $amountDue = $this->input('amount_due', 0);
            $amountPaid = $this->input('amount_paid', 0);

            if ($amountPaid >= $amountDue) {
                $status = 'Paid';
            } elseif ($amountPaid > 0) {
                $status = 'Partial';
            } else {
                $status = 'Pending';
            }

            $this->merge(['status' => $status]);
        }
    }
}