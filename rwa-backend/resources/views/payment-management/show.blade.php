@extends('layouts.app')

@section('title', 'Payment Details')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">
                <i class="fas fa-receipt me-2"></i>
                Payment Details
            </h1>
            <p class="mb-0 opacity-75">{{ $payment->resident->owner_name }} - {{ $payment->resident->flat_number }}</p>
        </div>
        <div>
            <a href="{{ route('payment-management.index') }}" class="btn btn-light">
                <i class="fas fa-arrow-left me-1"></i>
                Back to List
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Payment Information -->
    <div class="col-lg-8">
        <div class="main-content">
            <h5 class="mb-4">
                <i class="fas fa-info-circle me-2"></i>
                Payment Information
            </h5>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-muted">Payment Status</label>
                        <div>
                            <span class="badge status-{{ $payment->status }} fs-6">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-muted">Payment Month</label>
                        <div>
                            <strong>{{ \Carbon\Carbon::parse($payment->payment_month . '-01')->format('F Y') }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-muted">Amount Due</label>
                        <div>
                            <h4 class="text-warning mb-0">₹{{ number_format($payment->amount_due, 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-muted">Amount Paid</label>
                        <div>
                            <h4 class="text-success mb-0">₹{{ number_format($payment->amount_paid, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            @if($payment->status == 'partial')
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Remaining Amount</label>
                            <div>
                                <h5 class="text-danger mb-0">₹{{ number_format($payment->amount_due - $payment->amount_paid, 2) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-muted">Due Date</label>
                        <div>
                            <strong>{{ $payment->due_date->format('d M Y') }}</strong>
                            @if($payment->due_date < now() && $payment->status != 'paid')
                                <span class="text-danger ms-2">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Overdue ({{ $payment->due_date->diffForHumans() }})
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label text-muted">Payment Date</label>
                        <div>
                            @if($payment->payment_date)
                                <strong>{{ $payment->payment_date->format('d M Y H:i') }}</strong>
                            @else
                                <span class="text-muted">Not paid yet</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($payment->late_fee > 0)
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Late Fee</label>
                            <div>
                                <strong class="text-danger">₹{{ number_format($payment->late_fee, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($payment->payment_method)
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Payment Method</label>
                            <div>
                                <span class="badge bg-info">{{ ucfirst($payment->payment_method) }}</span>
                            </div>
                        </div>
                    </div>
                    @if($payment->transaction_id)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Transaction ID</label>
                                <div>
                                    <code>{{ $payment->transaction_id }}</code>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            @if($payment->notes)
                <div class="mb-3">
                    <label class="form-label text-muted">Notes</label>
                    <div class="border rounded p-3 bg-light">
                        {{ $payment->notes }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Resident Information -->
    <div class="col-lg-4">
        <div class="main-content">
            <h5 class="mb-4">
                <i class="fas fa-user me-2"></i>
                Resident Information
            </h5>

            <div class="mb-3">
                <label class="form-label text-muted">Name</label>
                <div><strong>{{ $payment->resident->owner_name }}</strong></div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted">Flat Number</label>
                <div><strong>{{ $payment->resident->flat_number }}</strong></div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted">House Number</label>
                <div>{{ $payment->resident->house_number }}</div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted">Floor</label>
                <div>{{ ucfirst(str_replace('_', ' ', $payment->resident->floor)) }}</div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted">Contact Number</label>
                <div>
                    <a href="tel:{{ $payment->resident->contact_number }}" class="text-decoration-none">
                        {{ $payment->resident->contact_number }}
                    </a>
                </div>
            </div>

            @if($payment->resident->email)
                <div class="mb-3">
                    <label class="form-label text-muted">Email</label>
                    <div>
                        <a href="mailto:{{ $payment->resident->email }}" class="text-decoration-none">
                            {{ $payment->resident->email }}
                        </a>
                    </div>
                </div>
            @endif

            <div class="mb-3">
                <label class="form-label text-muted">Monthly Maintenance</label>
                <div><strong>₹{{ number_format($payment->resident->monthly_maintenance, 2) }}</strong></div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted">Status</label>
                <div>
                    <span class="badge {{ $payment->resident->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                        {{ ucfirst($payment->resident->status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="main-content mt-3">
            <h5 class="mb-4">
                <i class="fas fa-history me-2"></i>
                Recent Payment History
            </h5>

            @if($paymentHistory->count() > 0)
                <div class="timeline">
                    @foreach($paymentHistory as $historyPayment)
                        <div class="d-flex mb-3 {{ $historyPayment->id == $payment->id ? 'bg-light rounded p-2' : '' }}">
                            <div class="me-3">
                                <span class="badge status-{{ $historyPayment->status }}">
                                    {{ ucfirst($historyPayment->status) }}
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">
                                    {{ \Carbon\Carbon::parse($historyPayment->payment_month . '-01')->format('M Y') }}
                                </div>
                                <div class="text-muted small">
                                    ₹{{ number_format($historyPayment->amount_paid, 2) }} / ₹{{ number_format($historyPayment->amount_due, 2) }}
                                </div>
                                @if($historyPayment->payment_date)
                                    <div class="text-muted small">
                                        {{ $historyPayment->payment_date->format('d M Y') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-muted">
                    <i class="fas fa-history fa-2x mb-2"></i>
                    <p>No payment history found</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection