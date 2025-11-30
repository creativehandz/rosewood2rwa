#!/bin/bash

# Google Sheets Payment Sync Setup Script
# This script helps configure the Google Sheets integration

echo "🔧 Google Sheets Payment Sync Setup"
echo "======================================"

# Check if running in Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Error: Please run this script from Laravel project root directory"
    exit 1
fi

echo ""
echo "📋 Setup Checklist:"
echo ""

# 1. Check for credentials file
if [ -f "storage/app/google-credentials.json" ]; then
    echo "✅ Google credentials file found"
else
    echo "❌ Google credentials file missing"
    echo "   Please copy your Google service account credentials to:"
    echo "   storage/app/google-credentials.json"
    echo ""
    echo "   You can use the example file as a template:"
    echo "   cp storage/app/google-credentials.json.example storage/app/google-credentials.json"
    echo ""
fi

# 2. Check environment variables
echo ""
echo "🔍 Checking environment configuration..."

if grep -q "GOOGLE_SHEETS_SPREADSHEET_ID" .env; then
    SPREADSHEET_ID=$(grep "GOOGLE_SHEETS_SPREADSHEET_ID" .env | cut -d '=' -f2)
    if [ "$SPREADSHEET_ID" = "your_spreadsheet_id_here" ] || [ -z "$SPREADSHEET_ID" ]; then
        echo "❌ GOOGLE_SHEETS_SPREADSHEET_ID not configured"
        echo "   Please set your Google Sheets spreadsheet ID in .env file"
    else
        echo "✅ Spreadsheet ID configured"
    fi
else
    echo "❌ Google Sheets environment variables missing"
    echo "   Please add the following to your .env file:"
    echo ""
    cat << EOF
# Google Sheets Configuration
GOOGLE_SHEETS_CREDENTIALS_PATH=storage/app/google-credentials.json
GOOGLE_SHEETS_SPREADSHEET_ID=your_spreadsheet_id_here
GOOGLE_SHEETS_PAYMENT_SHEET_NAME="Monthly Maintenance"
GOOGLE_SHEETS_PAYMENT_RANGE=A:M
GOOGLE_SHEETS_AUTO_SYNC=true
GOOGLE_SHEETS_SYNC_INTERVAL=15
EOF
    echo ""
fi

# 3. Test connection (if configured)
echo ""
echo "🔌 Testing connection..."

if [ -f "storage/app/google-credentials.json" ] && grep -q "GOOGLE_SHEETS_SPREADSHEET_ID" .env; then
    SPREADSHEET_ID=$(grep "GOOGLE_SHEETS_SPREADSHEET_ID" .env | cut -d '=' -f2)
    if [ "$SPREADSHEET_ID" != "your_spreadsheet_id_here" ] && [ ! -z "$SPREADSHEET_ID" ]; then
        echo "   Running connection test..."
        php artisan payments:sync-sheets --test
    else
        echo "   ⏭️  Skipping connection test (spreadsheet ID not configured)"
    fi
else
    echo "   ⏭️  Skipping connection test (credentials or config missing)"
fi

# 4. Show next steps
echo ""
echo "📝 Next Steps:"
echo ""
echo "1. 🔑 Google Cloud Setup (if not done):"
echo "   - Create Google Cloud project"
echo "   - Enable Google Sheets API"
echo "   - Create service account"
echo "   - Download credentials JSON"
echo ""
echo "2. 📊 Google Sheets Setup:"
echo "   - Create/open your spreadsheet"
echo "   - Share with service account email"
echo "   - Create 'Monthly Maintenance' sheet"
echo "   - Set up column headers (A-M)"
echo "   - Add resident lookup formulas"
echo ""
echo "3. ⚙️  Laravel Configuration:"
echo "   - Copy credentials to storage/app/google-credentials.json"
echo "   - Update .env with spreadsheet ID"
echo "   - Test connection: php artisan payments:sync-sheets --test"
echo ""
echo "4. 🚀 Start Syncing:"
echo "   - Manual sync: php artisan payments:sync-sheets bidirectional"
echo "   - Enable scheduler: Add to crontab or task scheduler"
echo "   - Use API endpoints for programmatic access"
echo ""
echo "📖 For detailed instructions, see: docs/GOOGLE_SHEETS_SYNC.md"
echo ""
echo "🎉 Setup complete! Happy syncing!"