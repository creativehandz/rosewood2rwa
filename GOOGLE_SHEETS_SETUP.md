# Google Sheets Integration Setup Guide

This guide will help you set up Google Sheets integration for your RWA Management System.

## Prerequisites

✅ **Already Completed:**
- Google API Client library installed (`composer require google/apiclient`)
- GoogleSheetsService class created
- GoogleSheetsController with API endpoints
- Configuration files set up
- Routes added to API

## Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing project
3. Enable the following APIs:
   - Google Sheets API
   - Google Drive API

### Enable APIs:
```bash
# In Google Cloud Console, go to:
# APIs & Services > Library
# Search and enable:
# - Google Sheets API
# - Google Drive API
```

## Step 2: Create Service Account

1. Go to **APIs & Services > Credentials**
2. Click **Create Credentials > Service Account**
3. Fill in the details:
   - Service account name: `rwa-sheets-service`
   - Service account ID: `rwa-sheets-service`
   - Description: `Service account for RWA Management System Google Sheets integration`
4. Click **Create and Continue**
5. Grant roles:
   - Editor (for creating sheets)
   - Or create custom role with specific permissions
6. Click **Continue** and **Done**

## Step 3: Create Service Account Key

1. In **Credentials**, click on your service account
2. Go to **Keys** tab
3. Click **Add Key > Create New Key**
4. Select **JSON** format
5. Download the JSON file
6. Rename it to `google-service-account.json`
7. Place it in your Laravel backend root directory: `d:\reactapps\rosewood2\rosewood2rwa\rwa-backend\google-service-account.json`

## Step 4: Update Environment Variables

Update your `.env` file with the correct paths:

```env
# Google Sheets Configuration
GOOGLE_APPLICATION_NAME="RWA Management System"
GOOGLE_SERVICE_ACCOUNT_JSON_PATH="./google-service-account.json"
GOOGLE_SPREADSHEET_ID=""  # Leave empty for now, we'll create a new sheet
```

## Step 5: Create or Use Existing Google Sheet

### Option A: Create New Sheet (Recommended)
1. Use the "Create New Sheet" button in the dashboard
2. This will create a new Google Sheet and set it up automatically

### Option B: Use Existing Sheet
1. Create a Google Sheet manually
2. Share it with your service account email (found in the JSON file, looks like: `rwa-sheets-service@your-project.iam.gserviceaccount.com`)
3. Give the service account "Editor" permissions
4. Copy the Sheet ID from the URL: `https://docs.google.com/spreadsheets/d/SHEET_ID_HERE/edit`
5. Update your `.env` file:
```env
GOOGLE_SPREADSHEET_ID="your_sheet_id_here"
```

## Step 6: Test the Integration

1. Start your Laravel server: `php artisan serve`
2. Open the dashboard: `http://127.0.0.1:8000/dashboard.html`
3. Click **"Test Connection"** in the Google Sheets section
4. If successful, click **"Push All Residents"** to sync data

## File Structure

Your setup should look like this:

```
rwa-backend/
├── google-service-account.json          # Service account credentials
├── .env                                 # Environment variables
├── config/
│   └── google.php                      # Google API configuration
├── app/
│   ├── Services/
│   │   └── GoogleSheetsService.php     # Google Sheets service
│   └── Http/Controllers/
│       └── GoogleSheetsController.php  # API endpoints
└── routes/
    └── api.php                         # Routes with Google Sheets endpoints
```

## Security Notes

1. **Never commit the service account JSON file to version control**
2. Add `google-service-account.json` to your `.gitignore`
3. Keep your service account credentials secure
4. Regularly rotate your service account keys
5. Use environment variables for sensitive configuration

## Troubleshooting

### Common Issues:

1. **"File not found" error**
   - Check that `google-service-account.json` is in the correct location
   - Verify the path in `GOOGLE_SERVICE_ACCOUNT_JSON_PATH`

2. **"Permission denied" error**
   - Make sure you've shared the Google Sheet with your service account email
   - Grant "Editor" permissions to the service account

3. **"API not enabled" error**
   - Enable Google Sheets API and Google Drive API in Google Cloud Console

4. **"Invalid credentials" error**
   - Re-download the service account JSON file
   - Check that the JSON file is valid and not corrupted

### Testing Commands:

```bash
# Test the Laravel API endpoints directly:
# Test connection
curl -X GET "http://127.0.0.1:8000/api/v1/google-sheets/test-connection" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Push residents
curl -X POST "http://127.0.0.1:8000/api/v1/google-sheets/push-residents" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Create new sheet
curl -X POST "http://127.0.0.1:8000/api/v1/google-sheets/create-sheet" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "Test RWA Sheet"}'
```

## Features Available

✅ **Test Connection**: Verify Google Sheets API connectivity
✅ **Push All Residents**: Sync all resident data to Google Sheets
✅ **Create New Sheet**: Create a new Google Sheet with proper formatting
✅ **Auto Headers**: Automatically creates headers for resident data
✅ **Data Formatting**: Formats resident data appropriately for sheets
✅ **Error Handling**: Comprehensive error handling and user feedback

## Next Steps

Once setup is complete, you can:
1. Automatically sync resident data to Google Sheets
2. Share the Google Sheet with stakeholders for reporting
3. Use Google Sheets for data analysis and reporting
4. Set up scheduled syncing (future enhancement)
5. Create multiple sheets for different purposes

## Support

If you encounter any issues:
1. Check the Laravel logs: `tail -f storage/logs/laravel.log`
2. Verify your Google Cloud Console settings
3. Test the API endpoints manually
4. Check the browser console for JavaScript errors

---

**Ready to proceed?** Follow the steps above to set up your Google Sheets integration!