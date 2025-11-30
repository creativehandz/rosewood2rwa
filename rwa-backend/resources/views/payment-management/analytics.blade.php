@extends('layouts.app')

@section('title', 'Payment Analytics')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">
                <i class="fas fa-chart-bar me-2"></i>
                Payment Analytics
            </h1>
            <p class="mb-0 opacity-75">Financial insights and payment trends analysis</p>
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
    <form method="GET" action="{{ route('payment-management.analytics') }}">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="start_month" class="form-label">Start Month</label>
                <input type="month" name="start_month" id="start_month" class="form-control" value="{{ $startMonth }}">
            </div>
            <div class="col-md-4">
                <label for="end_month" class="form-label">End Month</label>
                <input type="month" name="end_month" id="end_month" class="form-control" value="{{ $endMonth }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-chart-line me-1"></i>
                        Update Analytics
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Key Metrics Overview -->
@if(count($analytics) > 0)
    @php
        $latestMonth = array_key_last($analytics);
        $latestStats = $analytics[$latestMonth];
        $totalRevenue = collect($analytics)->sum('total_amount_paid');
        $totalOutstanding = collect($analytics)->sum('total_balance_due');
        $avgCollectionRate = collect($analytics)->avg('collection_percentage');
    @endphp

    <div class="main-content">
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <i class="fas fa-rupee-sign text-primary fa-3x mb-3"></i>
                        <h4 class="text-primary mb-2">₹{{ number_format($totalRevenue, 0) }}</h4>
                        <p class="card-text">Total Revenue Collected</p>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($startMonth . '-01')->format('M Y') }} - {{ \Carbon\Carbon::parse($endMonth . '-01')->format('M Y') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                        <h4 class="text-warning mb-2">₹{{ number_format($totalOutstanding, 0) }}</h4>
                        <p class="card-text">Total Outstanding</p>
                        <small class="text-muted">Across all selected months</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <i class="fas fa-percentage text-success fa-3x mb-3"></i>
                        <h4 class="text-success mb-2">{{ number_format($avgCollectionRate, 1) }}%</h4>
                        <p class="card-text">Avg Collection Rate</p>
                        <small class="text-muted">Overall performance</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <i class="fas fa-home text-info fa-3x mb-3"></i>
                        <h4 class="text-info mb-2">{{ $latestStats['total_units'] ?? 0 }}</h4>
                        <p class="card-text">Active Units</p>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($latestMonth . '-01')->format('M Y') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="main-content">
        <div class="row">
            <!-- Monthly Trends Chart -->
            <div class="col-lg-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line me-2"></i>Monthly Collection Trends</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyTrendsChart" style="height: 250px !important;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Methods Analysis -->
    <div class="main-content">
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-credit-card me-2"></i>Payment Methods Distribution</h5>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($latestMonth . '-01')->format('F Y') }}</small>
                    </div>
                    <div class="card-body">
                        <canvas id="paymentMethodsChart" style="height: 180px !important;"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-alt me-2"></i>Monthly Performance Table</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Due</th>
                                        <th>Collected</th>
                                        <th>Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monthlyTrends as $trend)
                                        <tr>
                                            <td>{{ $trend['month_name'] }}</td>
                                            <td>₹{{ number_format($trend['total_due'], 0) }}</td>
                                            <td>₹{{ number_format($trend['total_paid'], 0) }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 me-2" style="height: 15px;">
                                                        <div class="progress-bar 
                                                            @if($trend['collection_rate'] >= 90) bg-success
                                                            @elseif($trend['collection_rate'] >= 70) bg-info  
                                                            @elseif($trend['collection_rate'] >= 50) bg-warning
                                                            @else bg-danger @endif" 
                                                            style="width: {{ $trend['collection_rate'] }}%">
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">{{ number_format($trend['collection_rate'], 1) }}%</small>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Monthly Analytics -->
    <div class="main-content">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-table me-2"></i>Detailed Monthly Analytics</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Units</th>
                                <th>Amount Due</th>
                                <th>Amount Collected</th>
                                <th>Outstanding</th>
                                <th>Collection Rate</th>
                                <th>Payment Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($analytics as $month => $stats)
                                <tr>
                                    <td>
                                        <strong>{{ \Carbon\Carbon::parse($month . '-01')->format('M Y') }}</strong>
                                    </td>
                                    <td>{{ $stats['total_units'] }}</td>
                                    <td>₹{{ number_format($stats['total_amount_due'], 2) }}</td>
                                    <td class="text-success">₹{{ number_format($stats['total_amount_paid'], 2) }}</td>
                                    <td class="text-danger">₹{{ number_format($stats['total_balance_due'], 2) }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                <div class="progress-bar 
                                                    @if($stats['collection_percentage'] >= 90) bg-success
                                                    @elseif($stats['collection_percentage'] >= 70) bg-info  
                                                    @elseif($stats['collection_percentage'] >= 50) bg-warning
                                                    @else bg-danger @endif" 
                                                    style="width: {{ $stats['collection_percentage'] }}%">
                                                    {{ number_format($stats['collection_percentage'], 1) }}%
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <span class="badge bg-success" title="Paid">{{ $stats['payment_counts']['paid'] }}</span>
                                            <span class="badge bg-info" title="Partial">{{ $stats['payment_counts']['partial'] }}</span>
                                            <span class="badge bg-warning" title="Pending">{{ $stats['payment_counts']['pending'] }}</span>
                                            <span class="badge bg-danger" title="Overdue">{{ $stats['payment_counts']['overdue'] }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@else
    <div class="main-content">
        <div class="text-center py-5">
            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No Analytics Data Available</h5>
            <p class="text-muted">No payment data found for the selected date range.</p>
        </div>
    </div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(count($analytics) > 0)
        // Monthly Trends Chart
        const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
        new Chart(monthlyTrendsCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode(array_map(function($trend) { return $trend['month_name']; }, $monthlyTrends)) !!},
                datasets: [{
                    label: 'Amount Due',
                    data: {!! json_encode(array_map(function($trend) { return $trend['total_due']; }, $monthlyTrends)) !!},
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.1
                }, {
                    label: 'Amount Collected',
                    data: {!! json_encode(array_map(function($trend) { return $trend['total_paid']; }, $monthlyTrends)) !!},
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₹' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Payment Methods Chart
        const paymentMethodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
        new Chart(paymentMethodsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Cash', 'UPI', 'Bank Transfer'],
                datasets: [{
                    data: [
                        latestStats.payment_methods.cash || 0,
                        latestStats.payment_methods.upi || 0,
                        latestStats.payment_methods.bank_transfer || 0
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#17a2b8', 
                        '#6f42c1'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ₹' + context.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    @endif
});
</script>
@endpush

@push('styles')
<style>
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }
    
    .progress {
        background-color: #e9ecef;
    }
    
    .badge {
        font-size: 0.75rem;
    }
    
    .table th {
        border-top: none;
        font-weight: 600;
        color: #495057;
    }
    
    .filter-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .filter-card .form-label {
        color: white;
        font-weight: 500;
    }
</style>
@endpush