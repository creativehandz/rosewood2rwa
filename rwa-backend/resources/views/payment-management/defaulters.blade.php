@extends('layouts.app')

@section('title', 'Defaulters List')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">
                <i class="fas fa-user-times me-2"></i>
                Defaulters List
            </h1>
            <p class="mb-0 opacity-75">Residents with overdue payments (3+ months)</p>
        </div>
        <div>
            <a href="{{ route('payment-management.index') }}" class="btn btn-light">
                <i class="fas fa-arrow-left me-1"></i>
                Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Summary Alert -->
<div class="main-content">
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
        <div>
            <h5 class="alert-heading mb-2">Critical Payment Defaulters</h5>
            <p class="mb-1">
                <strong>{{ $defaulters->count() }} residents</strong> have overdue payments from 3+ months ago.
            </p>
            <p class="mb-0">
                Total outstanding: <strong class="text-danger">₹{{ number_format($defaulters->sum('total_balance'), 2) }}</strong>
            </p>
        </div>
    </div>
</div>

<!-- Defaulters Table -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="fas fa-exclamation-circle me-2"></i>
            Critical Defaulters
        </h5>
        <div class="d-flex align-items-center">
            <span class="text-muted me-3">
                {{ $defaulters->count() }} defaulters found
            </span>
            <div class="btn-group">
                <button class="btn btn-outline-warning" onclick="sendBulkNotices()">
                    <i class="fas fa-paper-plane me-1"></i>
                    Send Legal Notices
                </button>
                <button class="btn btn-outline-primary" onclick="exportDefaulters()">
                    <i class="fas fa-download me-1"></i>
                    Export List
                </button>
            </div>
        </div>
    </div>

    @if($defaulters->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-danger">
                    <tr>
                        <th>House & Floor</th>
                        <th>Resident Details</th>
                        <th>Contact</th>
                        <th>Total Outstanding</th>
                        <th>Overdue Months</th>
                        <th>Oldest Due</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($defaulters as $defaulter)
                        @php
                            $resident = $defaulter['resident'];
                            $monthsOverdue = $defaulter['overdue_months'];
                            $urgencyClass = $monthsOverdue >= 6 ? 'table-danger' : ($monthsOverdue >= 4 ? 'table-warning' : '');
                        @endphp
                        <tr class="{{ $urgencyClass }}">
                            <td>
                                <div>
                                    <strong class="fs-5">{{ $resident->house_number }}</strong>
                                    <br>
                                    <span class="badge bg-secondary">
                                        {{ ucfirst(str_replace('_', ' ', $resident->floor)) }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $resident->owner_name }}</strong>
                                    <br>
                                    @if($resident->email)
                                        <small class="text-muted">{{ $resident->email }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <a href="tel:{{ $resident->contact_number }}" class="text-decoration-none">
                                    <i class="fas fa-phone me-1"></i>
                                    {{ $resident->contact_number }}
                                </a>
                            </td>
                            <td>
                                <div class="text-end">
                                    <div class="text-muted small">Total Due: ₹{{ number_format($defaulter['total_due'], 2) }}</div>
                                    <div class="text-success small">Paid: ₹{{ number_format($defaulter['total_paid'], 2) }}</div>
                                    <hr class="my-1">
                                    <strong class="text-danger fs-5">₹{{ number_format($defaulter['total_balance'], 2) }}</strong>
                                </div>
                            </td>
                            <td>
                                <div class="text-center">
                                    @if($monthsOverdue >= 6)
                                        <span class="badge bg-danger fs-6">{{ $monthsOverdue }} months</span>
                                        <br><small class="text-danger"><strong>CRITICAL</strong></small>
                                    @elseif($monthsOverdue >= 4)
                                        <span class="badge bg-warning fs-6">{{ $monthsOverdue }} months</span>
                                        <br><small class="text-warning"><strong>HIGH RISK</strong></small>
                                    @else
                                        <span class="badge bg-info fs-6">{{ $monthsOverdue }} months</span>
                                        <br><small class="text-info">MODERATE</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="text-center">
                                    <strong>{{ \Carbon\Carbon::parse($defaulter['oldest_due_month'] . '-01')->format('M Y') }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        {{ \Carbon\Carbon::parse($defaulter['latest_due_month'] . '-01')->format('M Y') }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="text-center">
                                    @if($monthsOverdue >= 6)
                                        <i class="fas fa-exclamation-triangle text-danger fa-2x" title="Critical - Legal Action Required"></i>
                                    @elseif($monthsOverdue >= 4)
                                        <i class="fas fa-exclamation-circle text-warning fa-2x" title="High Risk - Immediate Action Needed"></i>
                                    @else
                                        <i class="fas fa-clock text-info fa-2x" title="Moderate Risk - Follow Up Required"></i>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="btn-group-vertical" role="group">
                                    <a href="tel:{{ $resident->contact_number }}" 
                                       class="btn btn-sm btn-outline-primary mb-1" 
                                       title="Call">
                                        <i class="fas fa-phone me-1"></i>Call
                                    </a>
                                    @if($resident->email)
                                        <a href="mailto:{{ $resident->email }}?subject=URGENT: Outstanding Maintenance Payment&body=Dear {{ $resident->owner_name }},%0D%0A%0D%0AThis is an urgent notice regarding your outstanding maintenance payment of ₹{{ number_format($defaulter['total_balance'], 2) }} spanning {{ $monthsOverdue }} months.%0D%0A%0D%0AImmediate action is required to avoid further consequences.%0D%0A%0D%0APlease contact us immediately." 
                                           class="btn btn-sm btn-outline-warning mb-1" 
                                           title="Send Notice">
                                            <i class="fas fa-envelope me-1"></i>Notice
                                        </a>
                                    @endif
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="escalateCase({{ $resident->id }}, '{{ $resident->owner_name }}', {{ $defaulter['total_balance'] }})" 
                                            title="Legal Action">
                                        <i class="fas fa-gavel me-1"></i>Legal
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Expandable Payment Details -->
                        <tr class="collapse" id="details-{{ $resident->id }}">
                            <td colspan="8" class="bg-light">
                                <div class="p-3">
                                    <h6><i class="fas fa-list me-2"></i>Payment History</h6>
                                    <div class="row">
                                        @foreach($defaulter['payments'] as $payment)
                                            <div class="col-md-3 mb-2">
                                                <div class="card card-sm">
                                                    <div class="card-body p-2">
                                                        <strong>{{ \Carbon\Carbon::parse($payment->payment_month . '-01')->format('M Y') }}</strong>
                                                        <br>
                                                        <small>Due: ₹{{ number_format($payment->amount_due, 2) }}</small>
                                                        <br>
                                                        <small>Paid: ₹{{ number_format($payment->amount_paid, 2) }}</small>
                                                        <br>
                                                        <span class="badge badge-sm {{ $payment->status == 'Pending' ? 'bg-warning' : 'bg-danger' }}">
                                                            {{ $payment->status }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Summary Statistics -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle text-danger fa-2x mb-2"></i>
                        <h5 class="card-title text-danger">{{ $defaulters->where('overdue_months', '>=', 6)->count() }}</h5>
                        <p class="card-text">Critical Cases (6+ months)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-circle text-warning fa-2x mb-2"></i>
                        <h5 class="card-title text-warning">{{ $defaulters->where('overdue_months', '>=', 4)->where('overdue_months', '<', 6)->count() }}</h5>
                        <p class="card-text">High Risk (4-5 months)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <i class="fas fa-clock text-info fa-2x mb-2"></i>
                        <h5 class="card-title text-info">{{ $defaulters->where('overdue_months', '<', 4)->count() }}</h5>
                        <p class="card-text">Moderate Risk (3 months)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <i class="fas fa-rupee-sign text-primary fa-2x mb-2"></i>
                        <h5 class="card-title text-primary">₹{{ number_format($defaulters->sum('total_balance'), 0) }}</h5>
                        <p class="card-text">Total Outstanding</p>
                    </div>
                </div>
            </div>
        </div>

    @else
        <div class="text-center py-5">
            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
            <h5 class="text-success">No Defaulters Found!</h5>
            <p class="text-muted">All residents are up to date with their payments.</p>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function sendBulkNotices() {
        const count = {{ $defaulters->count() }};
        if (confirm(`Send legal notices to all ${count} defaulters?`)) {
            // Implementation for bulk legal notices
            alert('Legal notices sent! (Feature to be implemented)');
        }
    }

    function exportDefaulters() {
        // Implementation for exporting defaulters list
        alert('Export feature to be implemented');
    }

    function escalateCase(residentId, residentName, outstandingAmount) {
        if (confirm(`Escalate case for ${residentName} with outstanding amount of ₹${outstandingAmount.toLocaleString()} to legal department?`)) {
            // Implementation for legal escalation
            alert('Case escalated to legal department! (Feature to be implemented)');
        }
    }

    // Toggle payment details
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('tbody tr:not(.collapse)');
        rows.forEach(function(row) {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function(e) {
                if (e.target.closest('a') || e.target.closest('button')) {
                    return; // Don't toggle if clicking on action buttons
                }
                
                const nextRow = row.nextElementSibling;
                if (nextRow && nextRow.classList.contains('collapse')) {
                    nextRow.classList.toggle('show');
                }
            });
        });
    });
</script>
@endpush

@push('styles')
<style>
    .card-sm .card-body {
        font-size: 0.875rem;
    }
    
    .table-responsive {
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
        border-radius: 8px;
        overflow: hidden;
    }
    
    .table-danger {
        --bs-table-bg: #f8d7da;
    }
    
    .table-warning {
        --bs-table-bg: #fff3cd;
    }
    
    tbody tr:hover {
        background-color: rgba(0,0,0,0.075) !important;
    }
    
    .badge.fs-6 {
        font-size: 0.875rem !important;
    }
</style>
@endpush