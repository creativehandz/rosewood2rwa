@extends('layouts.app')

@section('title', 'Resident Details - ' . $resident->owner_name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="/residents">All Residents</a>
                        </li>
                        <li class="breadcrumb-item active">{{ $resident->owner_name }}</li>
                    </ol>
                </nav>
                <h1 class="page-title h3 mb-0">
                    <i class="fas fa-user me-2"></i>{{ $resident->owner_name }}
                </h1>
            </div>
            <div class="page-actions">
                <a href="/residents/{{ $resident->id }}/edit" class="btn btn-outline-primary">
                    <i class="fas fa-edit me-2"></i>Edit Resident
                </a>
                <a href="{{ route('payment-management.index', ['resident_id' => $resident->id]) }}" class="btn btn-primary">
                    <i class="fas fa-money-bill-wave me-2"></i>View Payments
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Resident Information -->
        <div class="col-md-8">
            <!-- Basic Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Basic Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="form-label text-muted">Owner Name</label>
                                <div class="fw-bold">{{ $resident->owner_name }}</div>
                            </div>
                            
                            @if($resident->flat_number)
                            <div class="info-item mb-3">
                                <label class="form-label text-muted">Flat Number</label>
                                <div class="fw-bold">{{ $resident->flat_number }}</div>
                            </div>
                            @endif

                            @if($resident->house_number)
                            <div class="info-item mb-3">
                                <label class="form-label text-muted">House Number</label>
                                <div class="fw-bold">{{ $resident->house_number }}</div>
                            </div>
                            @endif

                            <div class="info-item mb-3">
                                <label class="form-label text-muted">Contact Number</label>
                                <div class="fw-bold">
                                    @if($resident->contact_number)
                                        <i class="fas fa-phone me-1"></i>{{ $resident->contact_number }}
                                    @else
                                        <span class="text-muted">Not provided</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="form-label text-muted">Email Address</label>
                                <div class="fw-bold">
                                    @if($resident->email)
                                        <i class="fas fa-envelope me-1"></i>{{ $resident->email }}
                                    @else
                                        <span class="text-muted">Not provided</span>
                                    @endif
                                </div>
                            </div>

                            <div class="info-item mb-3">
                                <label class="form-label text-muted">Status</label>
                                <div>
                                    @if($resident->status === 'active')
                                        <span class="badge bg-success fs-6">Active</span>
                                    @else
                                        <span class="badge bg-secondary fs-6">Inactive</span>
                                    @endif
                                </div>
                            </div>

                            <div class="info-item mb-3">
                                <label class="form-label text-muted">Family Members</label>
                                <div class="fw-bold">
                                    <i class="fas fa-users me-1"></i>{{ $resident->family_members ?: '0' }}
                                </div>
                            </div>

                            <div class="info-item mb-3">
                                <label class="form-label text-muted">Parking Slots</label>
                                <div class="fw-bold">
                                    <i class="fas fa-car me-1"></i>{{ $resident->parking_slots ?: '0' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="form-label text-muted">Member Since</label>
                                <div class="fw-bold">
                                    <i class="fas fa-calendar me-1"></i>{{ $resident->created_at->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <label class="form-label text-muted">Last Updated</label>
                                <div class="fw-bold">
                                    <i class="fas fa-clock me-1"></i>{{ $resident->updated_at->format('M d, Y g:i A') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Recent Payments
                        </h5>
                        <a href="{{ route('payment-management.index', ['resident_id' => $resident->id]) }}" 
                           class="btn btn-sm btn-outline-primary">
                            View All Payments
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($recentPayments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Month</th>
                                    <th>Amount Due</th>
                                    <th>Amount Paid</th>
                                    <th>Status</th>
                                    <th>Payment Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentPayments as $payment)
                                <tr>
                                    <td>
                                        <div class="fw-bold">
                                            {{ \Carbon\Carbon::parse($payment->payment_month . '-01')->format('M Y') }}
                                        </div>
                                    </td>
                                    <td>₹{{ number_format($payment->amount_due, 2) }}</td>
                                    <td>₹{{ number_format($payment->amount_paid, 2) }}</td>
                                    <td>
                                        @if($payment->status === 'paid')
                                            <span class="badge bg-success">Paid</span>
                                        @elseif($payment->status === 'partial')
                                            <span class="badge bg-warning">Partial</span>
                                        @else
                                            <span class="badge bg-danger">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->payment_date)
                                            {{ \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-money-bill-wave fa-2x text-muted mb-3"></i>
                        <h6 class="text-muted">No payments recorded yet</h6>
                        <p class="text-muted">Payment history will appear here once payments are recorded</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Payment Statistics -->
        <div class="col-md-4">
            <!-- Payment Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Payment Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="display-6 fw-bold text-primary">
                            {{ $paymentStats['payment_completion_rate'] }}%
                        </div>
                        <div class="text-muted">Payment Completion Rate</div>
                    </div>

                    <div class="payment-stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Total Payments</span>
                            <span class="fw-bold">{{ $paymentStats['total_payments'] }}</span>
                        </div>
                    </div>

                    <div class="payment-stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-success">
                                <i class="fas fa-check-circle me-1"></i>Paid
                            </span>
                            <span class="fw-bold text-success">{{ $paymentStats['paid_payments'] }}</span>
                        </div>
                    </div>

                    <div class="payment-stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-warning">
                                <i class="fas fa-clock me-1"></i>Pending
                            </span>
                            <span class="fw-bold text-warning">{{ $paymentStats['pending_payments'] }}</span>
                        </div>
                    </div>

                    <hr>

                    <div class="payment-stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Total Amount Due</span>
                            <span class="fw-bold">₹{{ number_format($paymentStats['total_amount_due'], 2) }}</span>
                        </div>
                    </div>

                    <div class="payment-stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-success">Amount Paid</span>
                            <span class="fw-bold text-success">₹{{ number_format($paymentStats['total_amount_paid'], 2) }}</span>
                        </div>
                    </div>

                    <div class="payment-stat-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-{{ $paymentStats['outstanding_balance'] > 0 ? 'danger' : 'success' }}">
                                Outstanding Balance
                            </span>
                            <span class="fw-bold text-{{ $paymentStats['outstanding_balance'] > 0 ? 'danger' : 'success' }}">
                                ₹{{ number_format($paymentStats['outstanding_balance'], 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Payment -->
            @if($paymentStats['latest_payment'])
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>Latest Payment
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="h4 mb-1">
                            {{ \Carbon\Carbon::parse($paymentStats['latest_payment']->payment_month . '-01')->format('M Y') }}
                        </div>
                        <div class="text-muted">Payment Month</div>
                    </div>

                    <div class="latest-payment-item mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Amount Due:</span>
                            <span class="fw-bold">₹{{ number_format($paymentStats['latest_payment']->amount_due, 2) }}</span>
                        </div>
                    </div>

                    <div class="latest-payment-item mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Amount Paid:</span>
                            <span class="fw-bold">₹{{ number_format($paymentStats['latest_payment']->amount_paid, 2) }}</span>
                        </div>
                    </div>

                    <div class="latest-payment-item mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Status:</span>
                            <span>
                                @if($paymentStats['latest_payment']->status === 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif($paymentStats['latest_payment']->status === 'partial')
                                    <span class="badge bg-warning">Partial</span>
                                @else
                                    <span class="badge bg-danger">Pending</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    @if($paymentStats['latest_payment']->payment_date)
                    <div class="text-center">
                        <small class="text-muted">
                            Paid on {{ \Carbon\Carbon::parse($paymentStats['latest_payment']->payment_date)->format('M d, Y') }}
                        </small>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.info-item label {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.payment-stat-item, .latest-payment-item {
    padding: 0.25rem 0;
}

.page-header .breadcrumb {
    margin-bottom: 0.5rem;
}

.stats-card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>
@endsection