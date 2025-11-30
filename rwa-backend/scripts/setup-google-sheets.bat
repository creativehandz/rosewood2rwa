@echo off
REM Google Sheets Payment Sync Setup Script for Windows
REM This script helps configure the Google Sheets integration

echo 🔧 Google Sheets Payment Sync Setup
echo ======================================

REM Check if running in Laravel project
if not exist "artisan" (
    echo ❌ Error: Please run this script from Laravel project root directory
    exit /b 1
)

echo.
echo 📋 Setup Checklist:
echo.

REM 1. Check for credentials file
if exist "storage\app\google-credentials.json" (
    echo ✅ Google credentials file found
) else (
    echo ❌ Google credentials file missing
    echo    Please copy your Google service account credentials to:
    echo    storage\app\google-credentials.json
    echo.
    echo    You can use the example file as a template:
    echo    copy storage\app\google-credentials.json.example storage\app\google-credentials.json
    echo.
)

REM 2. Check environment variables
echo.
echo 🔍 Checking environment configuration...

findstr /C:"GOOGLE_SHEETS_SPREADSHEET_ID" .env >nul 2>&1
if %errorlevel% equ 0 (
    for /f "tokens=2 delims==" %%a in ('findstr "GOOGLE_SHEETS_SPREADSHEET_ID" .env') do set SPREADSHEET_ID=%%a
    if "!SPREADSHEET_ID!"=="your_spreadsheet_id_here" (
        echo ❌ GOOGLE_SHEETS_SPREADSHEET_ID not configured
        echo    Please set your Google Sheets spreadsheet ID in .env file
    ) else (
        echo ✅ Spreadsheet ID configured
    )
) else (
    echo ❌ Google Sheets environment variables missing
    echo    Please add the following to your .env file:
    echo.
    echo # Google Sheets Configuration
    echo GOOGLE_SHEETS_CREDENTIALS_PATH=storage/app/google-credentials.json
    echo GOOGLE_SHEETS_SPREADSHEET_ID=your_spreadsheet_id_here
    echo GOOGLE_SHEETS_PAYMENT_SHEET_NAME="Monthly Maintenance"
    echo GOOGLE_SHEETS_PAYMENT_RANGE=A:M
    echo GOOGLE_SHEETS_AUTO_SYNC=true
    echo GOOGLE_SHEETS_SYNC_INTERVAL=15
    echo.
)

REM 3. Test connection (if configured)
echo.
echo 🔌 Testing connection...

if exist "storage\app\google-credentials.json" (
    findstr /C:"GOOGLE_SHEETS_SPREADSHEET_ID" .env >nul 2>&1
    if %errorlevel% equ 0 (
        echo    Running connection test...
        php artisan payments:sync-sheets --test
    ) else (
        echo    ⏭️  Skipping connection test (spreadsheet ID not configured)
    )
) else (
    echo    ⏭️  Skipping connection test (credentials missing)
)

REM 4. Show next steps
echo.
echo 📝 Next Steps:
echo.
echo 1. 🔑 Google Cloud Setup (if not done):
echo    - Create Google Cloud project
echo    - Enable Google Sheets API
echo    - Create service account
echo    - Download credentials JSON
echo.
echo 2. 📊 Google Sheets Setup:
echo    - Create/open your spreadsheet
echo    - Share with service account email
echo    - Create 'Monthly Maintenance' sheet
echo    - Set up column headers (A-M)
echo    - Add resident lookup formulas
echo.
echo 3. ⚙️  Laravel Configuration:
echo    - Copy credentials to storage\app\google-credentials.json
echo    - Update .env with spreadsheet ID
echo    - Test connection: php artisan payments:sync-sheets --test
echo.
echo 4. 🚀 Start Syncing:
echo    - Manual sync: php artisan payments:sync-sheets bidirectional
echo    - Enable scheduler: Add to Task Scheduler
echo    - Use API endpoints for programmatic access
echo.
echo 📖 For detailed instructions, see: docs\GOOGLE_SHEETS_SYNC.md
echo.
echo 🎉 Setup complete! Happy syncing!

pause