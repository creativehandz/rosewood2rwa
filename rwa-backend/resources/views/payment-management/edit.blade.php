@extends('layouts.app')

@section('title', 'Edit Payment')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1"><i class="fas fa-edit me-2"></i>Edit Payment</h1>
            <p class="mb-0 opacity-75">Modify payment details for <strong>{{ $payment->resident->owner_name ?? 'Resident' }}</strong></p>
        </div>
        <div>
            <a href="{{ route('payment-management.index', ['month' => $payment->payment_month]) }}" class="btn btn-light">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('payment-management.update', $payment->id) }}">
            @csrf
            @method('PATCH')

            <div class="mb-3">
                <label class="form-label">Resident</label>
                <div>
                    <strong>{{ $payment->resident->owner_name ?? '-' }}</strong>
                    <div class="text-muted">{{ $payment->resident->contact_number ?? '-' }}</div>
                    <div class="small text-muted">{{ $payment->resident->house_number ?? $payment->resident->flat_number ?? '' }}</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="payment_month" class="form-label">Payment Month</label>
                    <input type="text" id="payment_month" class="form-control" value="{{ \Carbon\Carbon::parse($payment->payment_month.'-01')->format('F Y') }}" readonly>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="amount_due" class="form-label">Amount Due (₹)</label>
                    <input type="number" step="0.01" name="amount_due" id="amount_due" class="form-control" value="{{ old('amount_due', $payment->amount_due) }}" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="amount_paid" class="form-label">Amount Paid (₹)</label>
                    <input type="number" step="0.01" name="amount_paid" id="amount_paid" class="form-control" value="{{ old('amount_paid', $payment->amount_paid) }}" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select" required>
                        @foreach(['Paid','Pending','Overdue','Partial'] as $s)
                            <option value="{{ $s }}" {{ (old('status', $payment->status) == $s) ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <select name="payment_method" id="payment_method" class="form-select">
                        <option value="UPI" {{ (old('payment_method', $payment->payment_method) == 'UPI') ? 'selected' : '' }}>UPI</option>
                        <option value="Cash" {{ (old('payment_method', $payment->payment_method) == 'Cash') ? 'selected' : '' }}>Cash</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="transaction_id" class="form-label">Transaction ID</label>
                    <input type="text" name="transaction_id" id="transaction_id" class="form-control" value="{{ old('transaction_id', $payment->transaction_id) }}">
                </div>
            </div>

            <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea name="remarks" id="remarks" rows="3" class="form-control">{{ old('remarks', $payment->remarks) }}</textarea>
            </div>

            <div class="d-flex justify-content-end">
                <a href="{{ route('payment-management.index', ['month' => $payment->payment_month]) }}" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

@endsection
