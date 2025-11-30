<?php

namespace App\Http\Requests;

use App\Rules\ActiveResidentExists;
use App\Rules\UniquePaymentPerMonth;
use App\Rules\ValidPaymentAmount;
use App\Rules\ValidPaymentMonth;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
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
        $payment = $this->route('payment');
        
        return [
            'resident_id' => [
                'sometimes',
                'integer',
                new ActiveResidentExists()
            ],
            'payment_month' => [
                'sometimes',
                'string',
                new ValidPaymentMonth(),
                new UniquePaymentPerMonth(
                    $this->input('resident_id', $payment->resident_id),
                    $payment->id
                )
            ],
            'amount_due' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:999999.99'
            ],
            'amount_paid' => [
                'sometimes',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $payment = $this->route('payment');
                    $amountDue = $this->input('amount_due', $payment->amount_due);
                    if ($value > $amountDue) {
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
                'unique:payments,transaction_id,' . $this->route('payment')->id
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
            'resident_id.integer' => 'Please select a valid resident.',
            'amount_due.min' => 'Amount due must be a positive number.',
            'amount_due.max' => 'Amount due cannot exceed ₹999,999.99.',
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
        $payment = $this->route('payment');

        // Auto-set payment date if amount paid is being increased and no date provided
        if ($this->has('amount_paid') && 
            $this->input('amount_paid') > $payment->amount_paid && 
            !$this->has('payment_date') && 
            !$payment->payment_date) {
            $this->merge([
                'payment_date' => now()->toDateString()
            ]);
        }
    }
}