@extends('layouts.app')

@section('title', 'All Residents')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="page-title h3 mb-0">
                <i class="fas fa-users me-2"></i>All Residents
            </h1>
            <div class="page-actions">
                <!-- Google Sheets Integration -->
                <div class="btn-group me-2" role="group" aria-label="Google Sheets">
                    <button type="button" class="btn btn-outline-success" onclick="testConnection()" title="Test Google API Connection">
                        <i class="fas fa-wifi me-2"></i>Test Connection
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="syncFromSheet()" title="Sync data from Google Sheet to Database">
                        <i class="fas fa-download me-2"></i>Sync from Sheet
                    </button>
                    <button type="button" class="btn btn-outline-warning" onclick="pushToSheet()" title="Push data from Database to Google Sheet">
                        <i class="fas fa-upload me-2"></i>Push to Sheet
                    </button>
                </div>

                <button class="btn btn-outline-primary" onclick="exportResidents()">
                    <i class="fas fa-file-csv me-2"></i>Export CSV
                </button>
                <a href="/residents/create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Resident
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="ms-3">
                            <div class="stats-number">{{ $stats['total_residents'] }}</div>
                            <div class="stats-label">Total Residents</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stats-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="ms-3">
                            <div class="stats-number">{{ $stats['active_residents'] }}</div>
                            <div class="stats-label">Active</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stats-card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <div class="ms-3">
                            <div class="stats-number">{{ $stats['inactive_residents'] }}</div>
                            <div class="stats-label">Inactive</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="ms-3">
                            <div class="stats-number">{{ $stats['paid_this_month'] }}</div>
                            <div class="stats-label">Paid This Month</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="ms-3">
                            <div class="stats-number">{{ $stats['unpaid_this_month'] }}</div>
                            <div class="stats-label">Unpaid This Month</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="/residents" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search Residents</label>
                    <input type="text" class="form-control" name="search" value="{{ $search }}" 
                           placeholder="Search by name, flat, house, phone, email...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Status</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per Page</label>
                    <select class="form-select" name="per_page">
                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <a href="/residents" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-2"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Residents Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Residents List 
                <span class="badge bg-primary">{{ $residents->total() }} Total</span>
            </h5>
        </div>
        <div class="card-body p-0">
            @if($residents->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>House Number</th>
                            <th>Floor</th>
                            <th>Property Type</th>
                            <th>Owner Name</th>
                            <th>Contact Number</th>
                            <th>Current State</th>
                            <th>Monthly Maintenance (₹)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($residents as $resident)
                        <tr>
                            <td>
                                <div class="fw-bold">
                                    @if($resident->house_number)
                                        {{ $resident->house_number }}
                                    @elseif($resident->flat_number)
                                        {{ $resident->flat_number }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($resident->floor)
                                    {{ $resident->floor }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($resident->property_type)
                                    {{ ucfirst($resident->property_type) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold">{{ $resident->owner_name }}</div>
                                @if($resident->email)
                                    <small class="text-muted">{{ $resident->email }}</small>
                                @endif
                            </td>
                            <td>
                                @if($resident->contact_number)
                                    <i class="fas fa-phone me-1"></i>{{ $resident->contact_number }}
                                @else
                                    <span class="text-muted">No contact</span>
                                @endif
                            </td>
                            <td>
                                @if($resident->current_state)
                                    <span class="badge bg-info">{{ ucfirst($resident->current_state) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($resident->monthly_maintenance)
                                    <span class="fw-bold text-success">₹{{ number_format($resident->monthly_maintenance, 0) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/residents/{{ $resident->id }}" 
                                       class="btn btn-outline-primary" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/residents/{{ $resident->id }}/edit" 
                                       class="btn btn-outline-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('payment-management.index', ['resident_id' => $resident->id]) }}" 
                                       class="btn btn-outline-info" title="View Payments">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($residents->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Showing {{ $residents->firstItem() }} to {{ $residents->lastItem() }} 
                        of {{ $residents->total() }} residents
                    </div>
                    
                    <nav aria-label="Residents pagination">
                        <ul class="pagination mb-0">
                            {{-- Previous Page --}}
                            @if ($residents->onFirstPage())
                                <li class="page-item disabled">
                                    <span class="page-link">Previous</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="/residents?{{ http_build_query(array_merge(request()->except('page'), ['page' => $residents->currentPage() - 1])) }}">Previous</a>
                                </li>
                            @endif

                            {{-- Page Numbers --}}
                            @for ($i = max(1, $residents->currentPage() - 2); $i <= min($residents->lastPage(), $residents->currentPage() + 2); $i++)
                                @if ($i == $residents->currentPage())
                                    <li class="page-item active">
                                        <span class="page-link">{{ $i }}</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="/residents?{{ http_build_query(array_merge(request()->except('page'), ['page' => $i])) }}">{{ $i }}</a>
                                    </li>
                                @endif
                            @endfor

                            {{-- Next Page --}}
                            @if ($residents->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link" href="/residents?{{ http_build_query(array_merge(request()->except('page'), ['page' => $residents->currentPage() + 1])) }}">Next</a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <span class="page-link">Next</span>
                                </li>
                            @endif
                        </ul>
                    </nav>
                </div>
            </div>
            @endif

            @else
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No residents found</h5>
                @if($search || $status !== 'all')
                <p class="text-muted">Try adjusting your search criteria or 
                    <a href="/residents">clear filters</a>
                </p>
                @else
                <p class="text-muted">Start by adding your first resident</p>
                <a href="/residents/create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add First Resident
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function exportResidents() {
    // Get current filter parameters
    const urlParams = new URLSearchParams(window.location.search);
    const exportUrl = new URL('/residents/export/csv', window.location.origin);
    
    // Add current filters to export URL
    urlParams.forEach((value, key) => {
        exportUrl.searchParams.append(key, value);
    });
    
    // Trigger download
    window.location.href = exportUrl.toString();
}

// Google Sheets Integration Functions
function testConnection() {
    const button = event.target.closest('button');
    const originalHtml = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';
    
    // Check if CSRF token exists
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        showAlert('danger', 'Configuration Error', 'CSRF token not found. Please refresh the page.');
        button.disabled = false;
        button.innerHTML = originalHtml;
        return;
    }
    
    fetch('/api/v1/public/google-sheets/test-connection', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Connection test response:', data);
        
        if (data.success) {
            let message = data.message;
            if (data.data && data.data.spreadsheet_title) {
                message += ` (Connected to: "${data.data.spreadsheet_title}")`;
            }
            showAlert('success', 'Connection Test Successful', message);
        } else {
            showAlert('danger', 'Connection Test Failed', data.message || 'Unknown error occurred');
        }
    })
    .catch(error => {
        console.error('Connection test error:', error);
        showAlert('danger', 'Connection Error', `Failed to test connection: ${error.message}`);
    })
    .finally(() => {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalHtml;
    });
}

function syncFromSheet() {
    if (!confirm('This will sync data from Google Sheet to the database. Any existing resident data may be updated. Continue?')) {
        return;
    }
    
    const button = event.target.closest('button');
    const originalHtml = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Syncing...';
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        showAlert('danger', 'Configuration Error', 'CSRF token not found. Please refresh the page.');
        button.disabled = false;
        button.innerHTML = originalHtml;
        return;
    }
    
    fetch('/api/v1/public/google-sheets/sync-from-sheet', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Sync from sheet response:', data);
        
        if (data.success) {
            let message = data.message;
            if (data.data && (data.data.synced_count || data.data.created_count || data.data.updated_count)) {
                const details = [];
                if (data.data.created_count) details.push(`${data.data.created_count} new`);
                if (data.data.updated_count) details.push(`${data.data.updated_count} updated`);
                if (data.data.deleted_count) details.push(`${data.data.deleted_count} deleted`);
                if (details.length > 0) {
                    message += ` (${details.join(', ')})`;
                }
            }
            showAlert('success', 'Sync Completed', message);
            // Reload page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showAlert('danger', 'Sync Failed', data.message || 'Unknown error occurred');
        }
    })
    .catch(error => {
        console.error('Sync from sheet error:', error);
        showAlert('danger', 'Sync Error', `Failed to sync from Google Sheet: ${error.message}`);
    })
    .finally(() => {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalHtml;
    });
}

function pushToSheet() {
    if (!confirm('This will push all resident data from the database to Google Sheet. Continue?')) {
        return;
    }
    
    const button = event.target.closest('button');
    const originalHtml = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Pushing...';
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        showAlert('danger', 'Configuration Error', 'CSRF token not found. Please refresh the page.');
        button.disabled = false;
        button.innerHTML = originalHtml;
        return;
    }
    
    fetch('/api/v1/public/google-sheets/push-to-sheet', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Push to sheet response:', data);
        
        if (data.success) {
            let message = data.message;
            if (data.data && data.data.count) {
                message += ` (${data.data.count} residents pushed)`;
            }
            if (data.data && data.data.spreadsheet_url) {
                message += ` <a href="${data.data.spreadsheet_url}" target="_blank">View Sheet</a>`;
            }
            showAlert('success', 'Push Completed', message);
        } else {
            showAlert('danger', 'Push Failed', data.message || 'Unknown error occurred');
        }
    })
    .catch(error => {
        console.error('Push to sheet error:', error);
        showAlert('danger', 'Push Error', `Failed to push to Google Sheet: ${error.message}`);
    })
    .finally(() => {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalHtml;
    });
}

function showAlert(type, title, message) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 350px; max-width: 500px;';
    
    // Create content safely
    const titleSpan = document.createElement('strong');
    titleSpan.textContent = title + ': ';
    
    const messageSpan = document.createElement('span');
    // Check if message contains HTML (for links)
    if (message.includes('<a href=')) {
        messageSpan.innerHTML = message;
    } else {
        messageSpan.textContent = message;
    }
    
    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'btn-close';
    closeButton.setAttribute('data-bs-dismiss', 'alert');
    closeButton.setAttribute('aria-label', 'Close');
    
    // Assemble the alert
    alertDiv.appendChild(titleSpan);
    alertDiv.appendChild(messageSpan);
    alertDiv.appendChild(closeButton);
    
    // Add to page
    document.body.appendChild(alertDiv);
    
    // Auto remove after 8 seconds (longer for messages with more content)
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 8000);
}
</script>
@endsection