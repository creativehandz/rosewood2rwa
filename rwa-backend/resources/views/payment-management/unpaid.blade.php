@extends('layouts.app')

@section('title', 'Unpaid Residents')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Unpaid Residents
            </h1>
            <p class="mb-0 opacity-75">Residents with outstanding maintenance payments for {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</p>
        </div>
        <div>
            <a href="{{ route('payment-management.index') }}" class="btn btn-light">
                <i class="fas fa-arrow-left me-1"></i>
                Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="filter-card">
    <form method="GET" action="{{ route('payment-management.unpaid') }}">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="month" class="form-label">Month</label>
                <select name="month" id="month" class="form-select">
                    @foreach($availableMonths as $availableMonth)
                        <option value="{{ $availableMonth }}" {{ $month == $availableMonth ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($availableMonth . '-01')->format('F Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="search" class="form-label">Search</label>
                <input type="text" name="search" id="search" class="form-control" 
                       placeholder="Resident name, flat number..." value="{{ $search }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>
                        Filter
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Summary Alert -->
<div class="main-content">
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="fas fa-info-circle me-3 fa-2x"></i>
        <div>
            <h5 class="alert-heading mb-2">Payment Summary for {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</h5>
            <p class="mb-1">
                <strong>{{ $unpaidResidents->total() }} residents</strong> have outstanding maintenance payments.
            </p>
            <p class="mb-0">
                Total unpaid amount: <strong class="text-danger">₹{{ number_format($totalUnpaidAmount, 2) }}</strong>
            </p>
        </div>
    </div>
</div>

<!-- Unpaid Residents Table -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="fas fa-users me-2"></i>
            Unpaid Residents
        </h5>
        <div class="d-flex align-items-center">
            <span class="text-muted me-3">
                Showing {{ $unpaidResidents->firstItem() ?? 0 }} to {{ $unpaidResidents->lastItem() ?? 0 }} 
                of {{ $unpaidResidents->total() }} residents
            </span>
        </div>
    </div>

    @if($unpaidResidents->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>House & Floor</th>
                        <th>Resident Name</th>
                        <th>Contact Number</th>
                        <th>Amount Due</th>
                        <th>Amount Paid</th>
                        <th>Unpaid Amount</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($unpaidResidents as $resident)
                        @php
                            $unpaidAmount = $resident->amount_due - $resident->amount_paid;
                        @endphp
                        <tr>
                            <td>
                                <div>
                                    <strong>{{ $resident->house_number }}</strong>
                                    <br>
                                    <span class="badge bg-secondary">
                                        {{ ucfirst(str_replace('_', ' ', $resident->floor)) }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $resident->owner_name }}</strong>
                                    @if($resident->email)
                                        <br>
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
                                <strong class="text-primary">₹{{ number_format($resident->amount_due, 2) }}</strong>
                            </td>
                            <td>
                                @if($resident->amount_paid > 0)
                                    <span class="text-success">₹{{ number_format($resident->amount_paid, 2) }}</span>
                                @else
                                    <span class="text-muted">₹0.00</span>
                                @endif
                            </td>
                            <td>
                                <strong class="text-danger fs-5">₹{{ number_format($unpaidAmount, 2) }}</strong>
                            </td>
                            <td>
                                @if($resident->payment_status == 'Pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($resident->payment_status == 'Partial')
                                    <span class="badge bg-info">Partial</span>
                                @elseif($resident->payment_status == 'Overdue')
                                    <span class="badge bg-danger">Overdue</span>
                                @else
                                    <span class="badge bg-secondary">{{ $resident->payment_status }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="tel:{{ $resident->contact_number }}" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Call">
                                        <i class="fas fa-phone"></i>
                                    </a>
                                    @if($resident->email)
                                        <a href="mailto:{{ $resident->email }}?subject=Maintenance Payment Reminder - {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}&body=Dear {{ $resident->owner_name }},%0D%0A%0D%0AThis is a reminder that your maintenance payment of ₹{{ number_format($unpaidAmount, 2) }} for {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }} is still pending.%0D%0A%0D%0APlease make the payment at your earliest convenience.%0D%0A%0D%0AThank you!" 
                                           class="btn btn-sm btn-outline-info" 
                                           title="Email">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    @endif
                                    <button class="btn btn-sm btn-outline-success" 
                                            onclick="markAsPaid({{ $resident->id }}, {{ $unpaidAmount }})" 
                                            title="Record Payment">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        {{ $unpaidResidents->appends(request()->query())->links() }}

        <!-- Bulk Actions -->
        <div class="mt-4 pt-3 border-top">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <strong class="me-3">Bulk Actions:</strong>
                        <button class="btn btn-outline-primary me-2" onclick="sendBulkReminders()">
                            <i class="fas fa-bell me-1"></i>
                            Send All Reminders
                        </button>
                        <button class="btn btn-outline-success" onclick="exportUnpaidList()">
                            <i class="fas fa-download me-1"></i>
                            Export List
                        </button>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="text-muted">
                        <strong>Total Outstanding:</strong> 
                        <span class="text-danger fs-5">₹{{ number_format($totalUnpaidAmount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-5">
            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
            <h5 class="text-success">All Payments Received!</h5>
            <p class="text-muted">All residents have fully paid their maintenance for {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}.</p>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Auto-submit form when month changes
    document.getElementById('month').addEventListener('change', function() {
        this.closest('form').submit();
    });

    function sendReminder(residentId) {
        if (confirm('Send payment reminder to this resident?')) {
            // Implementation for sending individual reminder
            alert('Reminder sent! (Feature to be implemented)');
        }
    }

    function sendBulkReminders() {
        const count = {{ $unpaidResidents->count() }};
        if (confirm(`Send payment reminders to all ${count} unpaid residents?`)) {
            // Implementation for bulk reminders
            alert('Bulk reminders sent! (Feature to be implemented)');
        }
    }

    function markAsPaid(residentId, unpaidAmount) {
        if (confirm(`Mark this payment of ₹${unpaidAmount.toLocaleString()} as paid?`)) {
            // Redirect to payment management page for this resident
            window.location.href = '{{ route("payment-management.index") }}?search=' + residentId + '&month={{ $month }}';
        }
    }

    function exportUnpaidList() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'unpaid');
        window.location.href = '{{ route("payment-management.export") }}?' + params.toString();
    }
</script>
@endpush