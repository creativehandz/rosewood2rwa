<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - RWA Management System</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-color: #667eea;
            --primary-hover: #5a67d8;
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }

        .login-left {
            background: var(--primary-gradient);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            min-height: 500px;
        }

        .login-right {
            padding: 3rem;
        }

        .logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .welcome-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .welcome-subtitle {
            opacity: 0.9;
            font-size: 1rem;
            line-height: 1.6;
        }

        .form-floating .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form-floating .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-login {
            background: var(--primary-gradient);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .divider {
            position: relative;
            text-align: center;
            margin: 1.5rem 0;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .demo-credentials {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            font-size: 0.9rem;
            color: #495057;
        }

        .feature-list {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
        }

        .feature-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .feature-list li i {
            color: rgba(255, 255, 255, 0.8);
        }

        @media (max-width: 768px) {
            .login-left {
                display: none;
            }
            
            .login-right {
                padding: 2rem 1.5rem;
            }
        }

        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card row g-0">
            <!-- Left Side - Welcome -->
            <div class="col-md-6 login-left">
                <div class="logo">🏠</div>
                <h1 class="welcome-title">RWA Management System</h1>
                <p class="welcome-subtitle">
                    Streamline your residential society management with our comprehensive solution for payments, residents, and reporting.
                </p>
                
                <ul class="feature-list">
                    <li><i class="bi bi-check-circle-fill"></i> Payment Management</li>
                    <li><i class="bi bi-check-circle-fill"></i> Resident Directory</li>
                    <li><i class="bi bi-check-circle-fill"></i> Google Sheets Integration</li>
                    <li><i class="bi bi-check-circle-fill"></i> Analytics & Reports</li>
                    <li><i class="bi bi-check-circle-fill"></i> Real-time Dashboard</li>
                </ul>
            </div>

            <!-- Right Side - Login Form -->
            <div class="col-md-6 login-right">
                <div class="text-center mb-4">
                    <h2 class="h4 mb-2">Welcome Back</h2>
                    <p class="text-muted">Please sign in to your account</p>
                </div>

                <!-- Display Flash Messages -->
                @if(session('success'))
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                    </div>
                @endif

                @if(session('info'))
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login.post') }}">
                    @csrf
                    
                    <div class="form-floating mb-3">
                        <input 
                            type="email" 
                            class="form-control @error('email') is-invalid @enderror" 
                            id="email" 
                            name="email" 
                            placeholder="name@example.com"
                            value="{{ old('email') }}"
                            required
                        >
                        <label for="email">Email address</label>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-floating mb-3">
                        <input 
                            type="password" 
                            class="form-control @error('password') is-invalid @enderror" 
                            id="password" 
                            name="password" 
                            placeholder="Password"
                            required
                        >
                        <label for="password">Password</label>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-login w-100 text-white">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Sign In
                    </button>
                </form>

                <div class="divider">
                    <span>Demo Credentials</span>
                </div>

                <!-- Demo Credentials -->
                <div class="demo-credentials">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Demo Admin Account:</strong>
                        <button class="btn btn-sm btn-outline-primary" onclick="fillDemoCredentials()">
                            <i class="bi bi-clipboard"></i> Use Demo
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Email:</small><br>
                            <code>admin@rwa.com</code>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Password:</small><br>
                            <code>admin123</code>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <p class="text-muted small">
                        Don't have an admin account? 
                        <a href="{{ route('register') }}" class="text-decoration-none">Create one here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function fillDemoCredentials() {
            document.getElementById('email').value = 'admin@rwa.com';
            document.getElementById('password').value = 'admin123';
        }
    </script>
</body>
</html>