@extends('layouts.app')

@section('title', 'Payment Management Dashboard')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">
                <i class="fas fa-credit-card me-2"></i>
                Payment Management
            </h1>
            <p class="mb-0 opacity-75">Manage maintenance payments for {{ $stats['month'] }} 
                <small class="text-muted">({{ $payments->total() }} records, {{ $payments->perPage() }} per page)</small>
            </p>
        </div>
        <div>
            <a href="{{ route('payment-management.export') }}?{{ http_build_query(request()->query()) }}" 
               class="btn btn-light">
                <i class="fas fa-download me-1"></i>
                Export
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mt-3">{{ session('success') }}</div>
@endif

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Total Residents</h6>
                    <h3 class="mb-0">{{ number_format($stats['total_residents']) }}</h3>
                </div>
                <div class="stats-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Total Due</h6>
                    <h3 class="mb-0 text-warning">₹{{ number_format($stats['total_due'], 2) }}</h3>
                </div>
                <div class="stats-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <i class="fas fa-rupee-sign"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Total Collected</h6>
                    <h3 class="mb-0 text-success">₹{{ number_format($stats['total_paid'], 2) }}</h3>
                </div>
                <div class="stats-icon" style="background: linear-gradient(135deg, #27ae60, #229954);">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Collection Rate</h6>
                    <h3 class="mb-0 text-info">{{ $stats['collection_rate'] }}%</h3>
                </div>
                <div class="stats-icon" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                    <i class="fas fa-chart-pie"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Summary -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card text-center border-success">
            <div class="card-body">
                <h5 class="card-title text-success">{{ $stats['paid_count'] }}</h5>
                <p class="card-text">Paid</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card text-center border-warning">
            <div class="card-body">
                <h5 class="card-title text-warning">{{ $stats['pending_count'] }}</h5>
                <p class="card-text">Pending</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card text-center border-danger">
            <div class="card-body">
                <h5 class="card-title text-danger">{{ $stats['overdue_count'] }}</h5>
                <p class="card-text">Overdue</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card text-center border-info">
            <div class="card-body">
                <h5 class="card-title text-info">{{ $stats['unpaid_residents'] }}</h5>
                <p class="card-text">No Payment</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="filter-card">
    <form method="GET" action="{{ route('payment-management.index') }}">
        <div class="row g-3">
            <div class="col-md-3">
                <label for="month" class="form-label">Month</label>
                <select name="month" id="month" class="form-select">
                    @foreach($availableMonths as $availableMonth)
                        <option value="{{ $availableMonth }}" {{ $month == $availableMonth ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($availableMonth . '-01')->format('F Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="paid" {{ $status == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="overdue" {{ $status == 'overdue' ? 'selected' : '' }}>Overdue</option>
                    <option value="partial" {{ $status == 'partial' ? 'selected' : '' }}>Partial</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="per_page" class="form-label">Show Results</label>
                <select name="per_page" id="per_page" class="form-select">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    <option value="200" {{ $perPage == 200 ? 'selected' : '' }}>200</option>
                    <option value="500" {{ $perPage == 500 ? 'selected' : '' }}>500 ⚠️</option>
                    <option value="1000" {{ $perPage == 1000 ? 'selected' : '' }}>1000 ⚠️</option>
                    <option value="9999" {{ $perPage == 9999 ? 'selected' : '' }}>All ⚠️</option>
                </select>
                @if($perPage >= 500)
                    <small class="text-warning mt-1 d-block">
                        <i class="fas fa-exclamation-triangle"></i> Large data set - may load slowly
                    </small>
                @endif
            </div>
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" name="search" id="search" class="form-control" 
                       placeholder="Resident name, flat number..." value="{{ $search }}">
            </div>
            <div class="col-md-1">
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

<!-- Payments Table -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Payment Records</h5>
        <div class="d-flex align-items-center">
            <span class="text-muted me-3">
                Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} 
                of {{ $payments->total() }} results
            </span>
        </div>
    </div>

    @if($payments->count() > 0)
        {{-- Performance warning for large data sets --}}
        @if($perPage >= 500)
            <div class="performance-warning">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Large Data Set:</strong> Displaying {{ $perPage == 9999 ? 'all' : $perPage }} records. 
                The page may load slowly and consume more memory.
            </div>
        @endif

        <div class="table-responsive {{ $perPage >= 200 ? 'table-container-large' : '' }}">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>House & Floor</th>
                        <th>Resident Details</th>
                        <th>Payment Month</th>
                        <th>Amount Due</th>
                        <th>Amount Paid</th>
                        <th>Payment Date</th>
                        <th>Payment Method & Transaction</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td><strong>PAY{{ str_pad($payment->id, 3, '0', STR_PAD_LEFT) }}</strong></td>
                            <td>
                                <div>
                                    <strong>{{ $payment->resident->house_number ?? $payment->resident->flat_number ?? '-' }}</strong>
                                    @if($payment->resident->floor)
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-building fa-xs"></i> {{ ucwords(str_replace('_', ' ', $payment->resident->floor)) }}
                                        </small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $payment->resident->owner_name }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-phone fa-xs"></i> {{ $payment->resident->contact_number ?? 'No phone' }}
                                    </small>
                                </div>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($payment->payment_month . '-01')->format('F Y') }}</td>
                            <td>
                                <strong>₹{{ number_format($payment->amount_due, 2) }}</strong>
                                @php
                                    // Check if this payment has carry-forward by looking for previous unpaid amounts
                                    $resident = $payment->resident;
                                    $baseMaintenance = $resident->monthly_maintenance ?? 0;
                                    $hasCarryForward = $payment->amount_due > $baseMaintenance;
                                @endphp
                                @if($hasCarryForward)
                                    <br>
                                    <small class="text-warning">
                                        <i class="fas fa-history fa-xs"></i> Includes carry-forward
                                    </small>
                                @endif
                            </td>
                            <td><strong>₹{{ number_format($payment->amount_paid, 2) }}</strong></td>
                            <td>
                                @if($payment->payment_date)
                                    {{ $payment->payment_date->format('d M Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($payment->payment_method)
                                    <div>
                                        @if(strtoupper($payment->payment_method) === 'UPI')
                                            <span class="badge bg-primary">
                                                <i class="fas fa-qrcode me-1"></i>UPI Scanner
                                            </span>
                                        @elseif(strtoupper($payment->payment_method) === 'CASH')
                                            <span class="badge bg-success">
                                                <i class="fas fa-money-bill me-1"></i>Cash
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">{{ $payment->payment_method }}</span>
                                        @endif
                                    </div>
                                    @if($payment->transaction_id)
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-hashtag fa-xs"></i> {{ $payment->transaction_id }}
                                        </small>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'paid' => 'bg-success',
                                        'pending' => 'bg-secondary', 
                                        'overdue' => 'bg-danger',
                                        'partial' => 'bg-warning'
                                    ];
                                    $color = $statusColors[$payment->status] ?? 'bg-secondary';
                                @endphp
                                <span class="badge {{ $color }}">{{ ucfirst($payment->status) }}</span>
                            </td>
                            <td>
                                @if($payment->remarks)
                                    {{ $payment->remarks }}
                                @elseif(strtolower($payment->status) == 'overdue')
                                    Payment overdue since {{ $payment->due_date->diffForHumans() }}
                                @elseif(strtolower($payment->status) == 'partial')
                                    Balance: ₹{{ number_format($payment->amount_due - $payment->amount_paid, 2) }}
                                @elseif(strtolower($payment->status) == 'paid')
                                    Payment completed
                                @else
                                    Due: {{ $payment->due_date->format('d M Y') }}
                                @endif
                            </td>

                            <td>
                                <button 
                                    class="btn btn-sm btn-outline-primary edit-payment-btn"
                                    data-payment-id="{{ $payment->id }}"
                                    data-resident-name="{{ $payment->resident->owner_name ?? '-' }}"
                                    data-resident-phone="{{ $payment->resident->contact_number ?? '-' }}"
                                    data-resident-house="{{ $payment->resident->house_number ?? $payment->resident->flat_number ?? '-' }}"
                                    data-payment-month="{{ \Carbon\Carbon::parse($payment->payment_month . '-01')->format('F Y') }}"
                                    data-amount-due="{{ $payment->amount_due }}"
                                    data-amount-paid="{{ $payment->amount_paid }}"
                                    data-status="{{ $payment->status }}"
                                    data-payment-method="{{ $payment->payment_method ?? 'UPI' }}"
                                    data-transaction-id="{{ $payment->transaction_id ?? '' }}"
                                    data-remarks="{{ $payment->remarks ?? '' }}"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editPaymentModal">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination Info and Controls -->
        <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
            <div class="pagination-info">
                @if($perPage == 9999)
                    <span>Showing all {{ $payments->total() }} results</span>
                @else
                    <span>Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} results</span>
                @endif
            </div>
            <div>
                @if($payments->hasPages() && $perPage != 9999)
                    {{ $payments->appends(request()->query())->links() }}
                @endif
            </div>
        </div>
    @else
        <div class="text-center py-5">
            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No payments found</h5>
            <p class="text-muted">No payment records found for the selected criteria.</p>
        </div>
    @endif
</div>

<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPaymentModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPaymentForm">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="edit_payment_id" name="payment_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Resident</label>
                        <div id="resident_info" class="border rounded p-2 bg-light">
                            <!-- Populated by JS -->
                        </div>
                    </div>

                    <!-- Carry-forward Breakdown -->
                    <div class="mb-3" id="carryforward_section" style="display: none;">
                        <label class="form-label">
                            <i class="fas fa-history text-warning me-1"></i>Carry-forward Breakdown
                        </label>
                        <div class="card border-warning">
                            <div class="card-body py-2">
                                <div id="carryforward_details">
                                    <!-- Populated by JS -->
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between">
                                    <span><strong>Base Maintenance:</strong></span>
                                    <span id="base_maintenance_amount">₹0.00</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span><strong>Total Carry-forward:</strong></span>
                                    <span id="total_carryforward_amount" class="text-warning fw-bold">₹0.00</span>
                                </div>
                                <div class="d-flex justify-content-between border-top pt-2 mt-2">
                                    <span><strong>Total Amount Due:</strong></span>
                                    <span id="calculated_total_due" class="text-primary fw-bold">₹0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_amount_due" class="form-label">Amount Due (₹)</label>
                            <input type="number" step="0.01" name="amount_due" id="edit_amount_due" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_amount_paid" class="form-label">Amount Paid (₹)</label>
                            <input type="number" step="0.01" name="amount_paid" id="edit_amount_paid" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="Paid">Paid</option>
                                <option value="Pending">Pending</option>
                                <option value="Overdue">Overdue</option>
                                <option value="Partial">Partial</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_payment_method" class="form-label">Payment Method</label>
                            <select name="payment_method" id="edit_payment_method" class="form-select">
                                <option value="UPI">UPI</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_transaction_id" class="form-label">Transaction ID</label>
                            <input type="text" name="transaction_id" id="edit_transaction_id" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_remarks" class="form-label">Remarks</label>
                        <textarea name="remarks" id="edit_remarks" rows="3" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-submit form when filters change
    document.getElementById('month').addEventListener('change', function() {
        this.closest('form').submit();
    });
    
    document.getElementById('status').addEventListener('change', function() {
        this.closest('form').submit();
    });
    
    document.getElementById('per_page').addEventListener('change', function() {
        const value = parseInt(this.value);
        
        // Show confirmation for large data sets
        if (value >= 1000) {
            const confirmed = confirm('Loading ' + (value === 9999 ? 'all' : value) + ' records may take some time and could slow down your browser. Continue?');
            if (!confirmed) {
                // Reset to previous value if user cancels
                this.value = "{{ $perPage }}";
                return;
            }
        }
        
        this.closest('form').submit();
    });

    // Modal edit functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Handle edit button clicks
        document.querySelectorAll('.edit-payment-btn').forEach(button => {
            button.addEventListener('click', function() {
                const paymentId = this.getAttribute('data-payment-id');
                const residentName = this.getAttribute('data-resident-name');
                const residentPhone = this.getAttribute('data-resident-phone');
                const residentHouse = this.getAttribute('data-resident-house');
                const paymentMonth = this.getAttribute('data-payment-month');
                const amountDue = this.getAttribute('data-amount-due');
                const amountPaid = this.getAttribute('data-amount-paid');
                const status = this.getAttribute('data-status');
                const paymentMethod = this.getAttribute('data-payment-method');
                const transactionId = this.getAttribute('data-transaction-id');
                const remarks = this.getAttribute('data-remarks');

                // Populate modal form
                document.getElementById('edit_payment_id').value = paymentId;
                document.getElementById('resident_info').innerHTML = `
                    <strong>${residentName}</strong><br>
                    <small class="text-muted"><i class="fas fa-phone fa-xs"></i> ${residentPhone}</small><br>
                    <small class="text-muted"><i class="fas fa-home fa-xs"></i> ${residentHouse} • ${paymentMonth}</small>
                `;
                document.getElementById('edit_amount_due').value = amountDue;
                document.getElementById('edit_amount_paid').value = amountPaid;
                document.getElementById('edit_status').value = status;
                document.getElementById('edit_payment_method').value = paymentMethod || 'UPI';
                document.getElementById('edit_transaction_id').value = transactionId;
                document.getElementById('edit_remarks').value = remarks;

                // Fetch carry-forward breakdown
                fetchCarryForwardBreakdown(paymentId);
            });
        });

        // Fetch carry-forward breakdown for a payment
        async function fetchCarryForwardBreakdown(paymentId) {
            try {
                const response = await fetch(`/payment-management/${paymentId}/carryforward-breakdown`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch carry-forward breakdown');
                }

                const data = await response.json();
                displayCarryForwardBreakdown(data);
            } catch (error) {
                console.error('Error fetching carry-forward breakdown:', error);
                // Hide carry-forward section if error
                document.getElementById('carryforward_section').style.display = 'none';
            }
        }

        // Display carry-forward breakdown in the modal
        function displayCarryForwardBreakdown(data) {
            const section = document.getElementById('carryforward_section');
            const detailsDiv = document.getElementById('carryforward_details');
            
            if (data.total_carryforward > 0) {
                // Show carry-forward section
                section.style.display = 'block';
                
                // Build breakdown HTML
                let breakdownHtml = '<small class="text-muted mb-2 d-block">Outstanding balances from previous months:</small>';
                
                data.breakdown.forEach(item => {
                    const balance = item.amount_due - item.amount_paid;
                    const monthName = new Date(item.month + '-01').toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short' 
                    });
                    
                    breakdownHtml += `
                        <div class="d-flex justify-content-between small">
                            <span>${monthName} (${item.status})</span>
                            <span>₹${balance.toLocaleString('en-IN', {minimumFractionDigits: 2})}</span>
                        </div>
                    `;
                });
                
                detailsDiv.innerHTML = breakdownHtml;
                
                // Update amounts
                document.getElementById('base_maintenance_amount').textContent = 
                    '₹' + data.base_maintenance.toLocaleString('en-IN', {minimumFractionDigits: 2});
                document.getElementById('total_carryforward_amount').textContent = 
                    '₹' + data.total_carryforward.toLocaleString('en-IN', {minimumFractionDigits: 2});
                document.getElementById('calculated_total_due').textContent = 
                    '₹' + data.calculated_total_due.toLocaleString('en-IN', {minimumFractionDigits: 2});
            } else {
                // Hide carry-forward section if no carry-forward
                section.style.display = 'none';
            }
        }

        // Handle form submission
        document.getElementById('editPaymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const paymentId = document.getElementById('edit_payment_id').value;
            const formData = new FormData(this);
            
            // Add CSRF token and method
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            formData.append('_method', 'PATCH');

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
            submitBtn.disabled = true;

            fetch(`/payment-management/${paymentId}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editPaymentModal'));
                    modal.hide();
                    
                    // Show success message
                    showAlert('success', data.message);
                    
                    // Refresh the page to update table and stats
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show validation errors
                    if (data.errors) {
                        let errorMsg = 'Please fix the following errors:<br>';
                        for (let field in data.errors) {
                            errorMsg += `• ${data.errors[field].join('<br>• ')}<br>`;
                        }
                        showAlert('danger', errorMsg);
                    } else {
                        showAlert('danger', data.message || 'An error occurred while saving.');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while saving the payment.');
            })
            .finally(() => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Utility function to show alerts
        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Find or create alert container
            let alertContainer = document.querySelector('.alert-container');
            if (!alertContainer) {
                alertContainer = document.createElement('div');
                alertContainer.className = 'alert-container';
                document.querySelector('.page-header').after(alertContainer);
            }
            
            alertContainer.innerHTML = alertHtml;
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
    });
</script>
@endpush