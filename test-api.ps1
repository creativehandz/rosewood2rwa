# Test API endpoints
Write-Host "Testing Laravel API..."

try {
    $response = Invoke-RestMethod -Uri "http://127.0.0.1:8000" -Method GET
    Write-Host "Server is running"
} catch {
    Write-Host "Server connection failed: $($_.Exception.Message)"
    exit 1
}

try {
    $stats = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/public/dashboard/stats" -Method GET
    Write-Host "Dashboard API working"
    Write-Host "Response: $($stats | ConvertTo-Json)"
} catch {
    Write-Host "Dashboard API failed: $($_.Exception.Message)"
}

try {
    $login = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/login" -Method POST -ContentType "application/json" -Body '{"email":"admin@example.com","password":"password123"}'
    Write-Host "Login API working"
    Write-Host "Response: $($login | ConvertTo-Json)"
} catch {
    Write-Host "Login API failed: $($_.Exception.Message)"
}