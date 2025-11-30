# 📋 Google Cloud Setup Checklist

## Pre-Setup Information
- **Google Account**: Use the same account that owns your Google Sheets
- **Estimated Time**: 10-15 minutes
- **Cost**: FREE for RWA use case

---

## ✅ Step-by-Step Checklist

### Step 1: Google Cloud Project
- [ ] Go to https://console.cloud.google.com/
- [ ] Sign in with your Google account
- [ ] Create new project: "RWA Payment System"
- [ ] Select the new project

### Step 2: Enable APIs
- [ ] Go to "APIs & Services" → "Library"
- [ ] Search and enable "Google Sheets API"
- [ ] (Optional) Search and enable "Google Drive API"

### Step 3: Create Service Account
- [ ] Go to "APIs & Services" → "Credentials"
- [ ] Click "CREATE CREDENTIALS" → "Service Account"
- [ ] Name: `rwa-payment-sync`
- [ ] Description: `Service account for RWA payment system`
- [ ] Complete creation

### Step 4: Generate Credentials
- [ ] Click on your service account email
- [ ] Go to "KEYS" tab
- [ ] Click "ADD KEY" → "Create new key" → "JSON"
- [ ] Download and rename to `google-credentials.json`
- [ ] **Copy the service account email** (you'll need this!)

### Step 5: Share Google Sheets
- [ ] Open your Google Sheets
- [ ] Click "Share" button
- [ ] Add service account email with "Editor" permission
- [ ] Uncheck "Notify people" and click "Share"

### Step 6: Get Spreadsheet ID
- [ ] Copy the ID from your Google Sheets URL
- [ ] Format: `https://docs.google.com/spreadsheets/d/YOUR_ID_HERE/edit`

### Step 7: Configure Laravel
- [ ] Copy `google-credentials.json` to `storage/app/` folder
- [ ] Update `.env` file with spreadsheet ID
- [ ] Run test: `php artisan payments:sync-sheets --test`

---

## 🎯 Important Information to Collect

As you go through the setup, collect this information:

1. **Service Account Email**: `_____________________@your-project.iam.gserviceaccount.com`
2. **Spreadsheet ID**: `_________________________________________________`
3. **Project ID**: `_________________________________________________`

---

## 🔧 Quick Commands for Laravel Setup

Once you have the credentials file and spreadsheet ID:

```bash
# Navigate to Laravel project
cd D:\reactapps\rosewood2\rosewood2rwa\rwa-backend

# Test connection
php artisan payments:sync-sheets --test

# If successful, try a manual sync
php artisan payments:sync-sheets bidirectional
```

---

## ✅ Success Confirmation

You'll know it's working when:
- ✅ Connection test shows "Connection successful!"
- ✅ Manual sync completes without errors
- ✅ Data appears in Google Sheets after sync

---

## 📞 Support

If you need help with any step, just let me know:
- Which step you're on
- Any error messages you see
- Screenshots if helpful

**Ready to start? Let's begin with Step 1!**