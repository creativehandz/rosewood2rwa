@extends('layouts.app')

@section('title', 'Dashboard - RWA Management')

@section('content')
<!-- Page Header -->
<div class="page-header">
    <h1 class="h3 mb-2">
        <i class="fas fa-tachometer-alt me-2"></i>
        Dashboard Overview
    </h1>
    <p class="mb-0 opacity-75">Welcome to the RWA Management System dashboard</p>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-primary me-3">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <div class="h4 mb-0">{{ number_format($stats['total_residents']) }}</div>
                    <div class="text-muted">Total Residents</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-success me-3">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="h4 mb-0">{{ number_format($stats['paying_residents']) }}</div>
                    <div class="text-muted">Paying Residents</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-info me-3">
                    <i class="fas fa-percentage"></i>
                </div>
                <div>
                    <div class="h4 mb-0">{{ $stats['collection_rate'] }}%</div>
                    <div class="text-muted">Collection Rate</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon bg-warning me-3">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div>
                    <div class="h4 mb-0">₹{{ number_format($stats['monthly_collection'], 2) }}</div>
                    <div class="text-muted">This Month Collection</div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Quick Actions -->
<div class="main-content mb-4">
    <h5 class="mb-3">
        <i class="fas fa-bolt me-2"></i>
        Quick Actions
    </h5>
    <div class="row g-3">
        <div class="col-lg-3 col-md-6">
            <a href="#" class="btn btn-primary w-100 py-3">
                <i class="fas fa-users fa-lg me-2"></i><br>
                <span>Manage Residents</span>
            </a>
        </div>
        <div class="col-lg-3 col-md-6">
            <a href="{{ route('payment-management.index') }}" class="btn btn-success w-100 py-3">
                <i class="fas fa-credit-card fa-lg me-2"></i><br>
                <span>Payment Management</span>
            </a>
        </div>
        <div class="col-lg-3 col-md-6">
            <a href="{{ route('payment-management.analytics') }}" class="btn btn-info w-100 py-3">
                <i class="fas fa-chart-line fa-lg me-2"></i><br>
                <span>Analytics & Reports</span>
            </a>
        </div>
        <div class="col-lg-3 col-md-6">
            <button class="btn btn-warning w-100 py-3" onclick="pushToGoogleSheets()">
                <i class="fas fa-cloud-upload-alt fa-lg me-2"></i><br>
                <span>Push to Sheets</span>
            </button>
        </div>
    </div>
</div>

<!-- Google Sheets Integration -->
<div class="main-content">
    <h5 class="mb-3">
        <i class="fas fa-cloud me-2"></i>
        Google Sheets Integration
    </h5>
    <div class="row g-3">
        <div class="col-md-4">
            <button class="btn btn-outline-primary w-100" onclick="testGoogleSheetsConnection()">
                <i class="fas fa-wifi me-2"></i>Test Connection
            </button>
        </div>
        <div class="col-md-4">
            <button class="btn btn-outline-success w-100" onclick="pushToGoogleSheets()">
                <i class="fas fa-upload me-2"></i>Push All Residents
            </button>
        </div>
        <div class="col-md-4">
            <button class="btn btn-outline-info w-100" onclick="createNewSheet()">
                <i class="fas fa-plus me-2"></i>Create New Sheet
            </button>
        </div>
    </div>
    <div class="mt-3">
        <div id="sheetsStatus" class="alert alert-info d-none" role="alert">
            <i class="fas fa-info-circle me-2"></i><span id="sheetsStatusText">Ready to sync with Google Sheets</span>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const API_BASE = '{{ url("/api/v1") }}';
    let authToken = localStorage.getItem('rwa_token') || '{{ csrf_token() }}';

    // Google Sheets Integration Functions
    function showSheetsStatus(message, type = 'info') {
        const statusDiv = document.getElementById('sheetsStatus');
        const statusText = document.getElementById('sheetsStatusText');
        
        statusDiv.className = `alert alert-${type}`;
        statusText.textContent = message;
        statusDiv.classList.remove('d-none');
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            statusDiv.classList.add('d-none');
        }, 5000);
    }

    async function makeAuthenticatedRequest(endpoint, options = {}) {
        const headers = {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...options.headers
        };
        
        if (authToken && authToken !== '{{ csrf_token() }}') {
            headers['Authorization'] = 'Bearer ' + authToken;
        }
        
        const response = await fetch(API_BASE + endpoint, { ...options, headers });
        
        if (response.status === 401) {
            localStorage.removeItem('rwa_token');
            localStorage.removeItem('rwa_user');
            window.location.href = '/login';
            return;
        }
        
        return response;
    }

    async function testGoogleSheetsConnection() {
        showSheetsStatus('Testing Google Sheets connection...', 'info');
        
        try {
            const response = await makeAuthenticatedRequest('/google-sheets/test-connection');
            if (response && response.ok) {
                const data = await response.json();
                if (data.status === 'success') {
                    showSheetsStatus('✅ Google Sheets connection successful!', 'success');
                } else {
                    showSheetsStatus('❌ Connection failed: ' + (data.message || 'Unknown error'), 'danger');
                }
            } else {
                const errorData = await response.json();
                showSheetsStatus('❌ Connection failed: ' + (errorData.message || 'Server error'), 'danger');
            }
        } catch (error) {
            console.error('Google Sheets connection test failed:', error);
            showSheetsStatus('❌ Connection test failed: ' + error.message, 'danger');
        }
    }

    async function pushToGoogleSheets() {
        if (!confirm('Are you sure you want to push all resident data to Google Sheets?')) {
            return;
        }

        showSheetsStatus('Pushing resident data to Google Sheets...', 'info');
        
        try {
            const response = await makeAuthenticatedRequest('/google-sheets/push-residents', {
                method: 'POST'
            });
            
            if (response && response.ok) {
                const data = await response.json();
                if (data.status === 'success') {
                    showSheetsStatus(`✅ Successfully pushed ${data.data.total_residents} residents to Google Sheets!`, 'success');
                } else {
                    showSheetsStatus('❌ Push failed: ' + (data.message || 'Unknown error'), 'danger');
                }
            } else {
                const errorData = await response.json();
                showSheetsStatus('❌ Push failed: ' + (errorData.message || 'Server error'), 'danger');
            }
        } catch (error) {
            console.error('Google Sheets push failed:', error);
            showSheetsStatus('❌ Push failed: ' + error.message, 'danger');
        }
    }

    async function createNewSheet() {
        const sheetName = prompt('Enter a name for the new Google Sheet:', 'RWA Residents ' + new Date().toLocaleDateString());
        if (!sheetName) {
            return;
        }

        showSheetsStatus('Creating new Google Sheet...', 'info');
        
        try {
            const response = await makeAuthenticatedRequest('/google-sheets/create-sheet', {
                method: 'POST',
                body: JSON.stringify({ name: sheetName })
            });
            
            if (response && response.ok) {
                const data = await response.json();
                if (data.status === 'success') {
                    showSheetsStatus(`✅ New sheet "${sheetName}" created successfully!`, 'success');
                    if (data.data.spreadsheet_url) {
                        setTimeout(() => {
                            if (confirm('Would you like to open the new sheet in a new tab?')) {
                                window.open(data.data.spreadsheet_url, '_blank');
                            }
                        }, 1000);
                    }
                } else {
                    showSheetsStatus('❌ Sheet creation failed: ' + (data.message || 'Unknown error'), 'danger');
                }
            } else {
                const errorData = await response.json();
                showSheetsStatus('❌ Sheet creation failed: ' + (errorData.message || 'Server error'), 'danger');
            }
        } catch (error) {
            console.error('Google Sheets creation failed:', error);
            showSheetsStatus('❌ Sheet creation failed: ' + error.message, 'danger');
        }
    }
</script>
@endpush