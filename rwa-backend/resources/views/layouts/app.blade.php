<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RWA Payment Management')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        /* Custom styles to match dashboard.html design */
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-color: #667eea;
            --primary-hover: #5a67d8;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', sans-serif;
        }

        .navbar {
            background: var(--primary-gradient);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: bold;
            font-size: 1.4rem;
        }

        .sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .sidebar .nav-link {
            color: var(--primary-color);
            padding: 0.75rem 1rem;
            margin-bottom: 0.25rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            position: relative;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #e3f2fd;
            border-left: 4px solid var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .sidebar .nav-link .badge {
            margin-left: auto;
            font-size: 0.7rem;
        }

        .sidebar h6 {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar hr {
            margin: 1rem 0;
            opacity: 0.3;
        }

        .main-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .stats-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .table th {
            background: var(--primary-gradient);
            color: white;
            border: none;
            font-weight: 600;
        }

        .table td {
            border-color: var(--border-color);
            vertical-align: middle;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
        }

        .btn {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .btn-primary { 
            background: var(--primary-color); 
            border-color: var(--primary-color); 
        }
        
        .btn-primary:hover { 
            background: var(--primary-hover); 
            border-color: var(--primary-hover); 
        }

        .filter-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .page-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .status-paid { background-color: var(--success-color) !important; }
        .status-pending { background-color: var(--warning-color) !important; }
        .status-overdue { background-color: var(--danger-color) !important; }
        .status-partial { background-color: var(--info-color) !important; }

        /* Pagination Styles */
        .pagination {
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .pagination .page-link {
            border-radius: 6px;
            margin: 0 2px;
            border: 1px solid var(--border-color);
            color: var(--primary-color);
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .pagination .page-link:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            text-decoration: none;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            z-index: 3;
        }

        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            background-color: white;
            border-color: var(--border-color);
        }

        /* Pagination info text */
        .pagination-info {
            font-size: 0.9rem;
            color: #6c757d;
        }

        /* Large table handling */
        .table-container-large {
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }

        .table-container-large .table {
            margin-bottom: 0;
        }

        .table-container-large .table thead th {
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 10;
            border-bottom: 2px solid var(--border-color);
        }

        /* Performance warning */
        .performance-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard.index') }}">
                <i class="fas fa-building me-2"></i>
                RWA Management System
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        {{ auth()->user()->name ?? 'Admin' }}
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item" style="border: none; background: none;">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 col-md-4">
                <div class="sidebar">
                    <h5 class="mb-3">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Navigation
                    </h5>
                    <nav class="nav flex-column">
                        <!-- Main Dashboard -->
                        <a class="nav-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}" 
                           href="{{ route('dashboard.index') }}">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                        
                        <hr class="my-3">
                        
                        <!-- Payment Management Section -->
                        <h6 class="text-muted mb-2 ms-2">
                            <i class="fas fa-credit-card me-1"></i>
                            Payment Management
                        </h6>
                        
                        <a class="nav-link {{ request()->routeIs('payment-management.index') ? 'active' : '' }}" 
                           href="{{ route('payment-management.index') }}">
                            <i class="fas fa-list me-2"></i>
                            All Payments
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('payment-management.unpaid') ? 'active' : '' }}" 
                           href="{{ route('payment-management.unpaid') }}">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Unpaid Residents
                            @php
                                // Get unpaid count for current month - we'll add this logic later
                                $unpaidCount = 0; // Placeholder
                            @endphp
                            @if($unpaidCount > 0)
                                <span class="badge bg-danger ms-auto">{{ $unpaidCount }}</span>
                            @endif
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('payment-management.defaulters') ? 'active' : '' }}" 
                           href="{{ route('payment-management.defaulters') }}">
                            <i class="fas fa-user-times me-2"></i>
                            Defaulters
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('payment-management.analytics') ? 'active' : '' }}" 
                           href="{{ route('payment-management.analytics') }}">
                            <i class="fas fa-chart-line me-2"></i>
                            Analytics & Reports
                        </a>
                        
                        <hr class="my-3">
                        
                        <!-- Resident Management Section -->
                        <h6 class="text-muted mb-2 ms-2">
                        <h6 class="sidebar-heading text-muted mb-2">
                            Resident Management
                        </h6>
                        
                        <a class="nav-link {{ request()->is('residents') || request()->is('residents/*') ? 'active' : '' }}" href="/residents">
                            <i class="fas fa-users me-2"></i>
                            All Residents
                        </a>
                        
                        <a class="nav-link {{ request()->is('residents/create') ? 'active' : '' }}" href="/residents/create">
                            <i class="fas fa-user-plus me-2"></i>
                            Add Resident
                        </a>
                        
                        <hr class="my-3">
                        
                        <!-- Quick Actions -->
                        <h6 class="text-muted mb-2 ms-2">
                            <i class="fas fa-bolt me-1"></i>
                            Quick Actions
                        </h6>
                        
                        <a class="nav-link" href="{{ route('payment-management.export') }}?{{ http_build_query(request()->query()) }}">
                            <i class="fas fa-download me-2"></i>
                            Export Data
                        </a>
                        
                        <a class="nav-link" href="#">
                            <i class="fas fa-plus me-2"></i>
                            Create Payment
                        </a>
                        
                        <a class="nav-link" href="#">
                            <i class="fas fa-bell me-2"></i>
                            Send Reminders
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9 col-md-8">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    @stack('scripts')
</body>
</html>