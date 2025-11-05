@echo off
echo Testing API endpoints...
echo.

echo Testing dashboard stats:
curl -s http://127.0.0.1:8000/api/v1/public/dashboard/stats
echo.
echo.

echo Testing login endpoint:
curl -s -X POST http://127.0.0.1:8000/api/v1/auth/login -H "Content-Type: application/json" -d "{\"email\":\"admin@example.com\",\"password\":\"password123\"}"
echo.
echo.

pause