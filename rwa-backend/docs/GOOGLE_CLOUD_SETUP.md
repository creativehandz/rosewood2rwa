# 🔧 Google Cloud Setup Guide for RWA Payment System

## Overview
This guide will help you set up Google Cloud to enable automatic synchronization between your Laravel payment system and Google Sheets.

## ⏱️ Estimated Time: 10-15 minutes

---

## Step 1: Create Google Cloud Project

### 1.1 Access Google Cloud Console
1. **Open your browser** and go to: https://console.cloud.google.com/
2. **Sign in** with your Google account (use the same account that owns your Google Sheets)

### 1.2 Create New Project
1. **Click the project dropdown** (top of the page, next to "Google Cloud")
2. **Click "NEW PROJECT"**
3. **Enter project details**:
   - **Project Name**: `RWA Payment System` (or any name you prefer)
   - **Organization**: Leave as default (or select your organization)
4. **Click "CREATE"**
5. **Wait for project creation** (takes 30-60 seconds)
6. **Select your new project** from the dropdown

---

## Step 2: Enable Google Sheets API

### 2.1 Navigate to APIs & Services
1. **Click the hamburger menu** (☰) in the top-left
2. **Navigate to**: "APIs & Services" → "Library"

### 2.2 Enable Google Sheets API
1. **Search for**: "Google Sheets API"
2. **Click on "Google Sheets API"** result
3. **Click "ENABLE"** button
4. **Wait for activation** (takes 10-30 seconds)
5. **You should see**: "API enabled" confirmation

### 2.3 Enable Google Drive API (Optional but Recommended)
1. **Search for**: "Google Drive API"
2. **Click on "Google Drive API"** result
3. **Click "ENABLE"** button
4. **Wait for activation**

---

## Step 3: Create Service Account

### 3.1 Navigate to Credentials
1. **Go to**: "APIs & Services" → "Credentials"
2. **Click "CREATE CREDENTIALS"**
3. **Select "Service Account"**

### 3.2 Service Account Details
1. **Service Account Name**: `rwa-payment-sync`
2. **Service Account ID**: Will auto-generate (e.g., `rwa-payment-sync@your-project.iam.gserviceaccount.com`)
3. **Description**: `Service account for RWA payment system Google Sheets sync`
4. **Click "CREATE AND CONTINUE"**

### 3.3 Grant Permissions (Optional)
1. **For "Select a role"**: You can skip this or select "Project" → "Editor"
2. **Click "CONTINUE"**
3. **Click "DONE"**

---

## Step 4: Generate Credentials JSON

### 4.1 Access Service Account
1. **In Credentials page**, find your service account in the list
2. **Click on the service account email** (e.g., `rwa-payment-sync@...`)

### 4.2 Create Key
1. **Go to "KEYS" tab**
2. **Click "ADD KEY"** → "Create new key"
3. **Select "JSON"** format
4. **Click "CREATE"**
5. **File will automatically download** to your computer
6. **Important**: Remember where this file is saved!

### 4.3 Rename and Secure File
1. **Rename the downloaded file** to: `google-credentials.json`
2. **Move it to a secure location** (we'll copy it to Laravel later)

---

## Step 5: Configure Google Sheets Access

### 5.1 Copy Service Account Email
1. **Copy the service account email** (e.g., `rwa-payment-sync@your-project.iam.gserviceaccount.com`)
2. **Keep this handy** - you'll need it for the next step

### 5.2 Share Google Sheets with Service Account
1. **Open your Google Sheets** (the one with resident data)
2. **Click "Share" button** (top-right)
3. **Paste the service account email**
4. **Set permission to "Editor"**
5. **Uncheck "Notify people"** (it's a service account, not a person)
6. **Click "Share"**

---

## Step 6: Get Spreadsheet ID

### 6.1 Find Spreadsheet ID
1. **Look at your Google Sheets URL**:
   ```
   https://docs.google.com/spreadsheets/d/SPREADSHEET_ID_HERE/edit#gid=0
   ```
2. **Copy the SPREADSHEET_ID_HERE part**
3. **Example**:
   ```
   URL: https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit
   ID:  1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms
   ```

---

## Step 7: Configure Laravel Application

### 7.1 Copy Credentials to Laravel
1. **Copy the `google-credentials.json` file**
2. **Paste it to**: `D:\reactapps\rosewood2\rosewood2rwa\rwa-backend\storage\app\google-credentials.json`

### 7.2 Update Environment Variables
1. **Open your `.env` file**
2. **Add these lines** (or update if they exist):
   ```env
   # Google Sheets Configuration
   GOOGLE_SHEETS_CREDENTIALS_PATH=storage/app/google-credentials.json
   GOOGLE_SHEETS_SPREADSHEET_ID=YOUR_SPREADSHEET_ID_HERE
   GOOGLE_SHEETS_PAYMENT_SHEET_NAME="Monthly Maintenance"
   GOOGLE_SHEETS_PAYMENT_RANGE=A:M
   GOOGLE_SHEETS_AUTO_SYNC=true
   GOOGLE_SHEETS_SYNC_INTERVAL=15
   ```
3. **Replace `YOUR_SPREADSHEET_ID_HERE`** with the ID you copied

### 7.3 Test Connection
1. **Open PowerShell/Terminal**
2. **Navigate to Laravel project**:
   ```bash
   cd D:\reactapps\rosewood2\rosewood2rwa\rwa-backend
   ```
3. **Run connection test**:
   ```bash
   php artisan payments:sync-sheets --test
   ```

---

## ✅ Success Indicators

### You've succeeded when:
1. **✅ No errors** during Google Cloud project creation
2. **✅ APIs enabled** successfully
3. **✅ Service account created** and credentials downloaded
4. **✅ Google Sheets shared** with service account
5. **✅ Laravel connection test passes**

### Expected Test Output:
```
🔄 Payment Sync Service
Direction: bidirectional
🔍 Testing Google Sheets connection...
✅ Connection successful!
Spreadsheet: Your Spreadsheet Name
```

---

## 🚨 Troubleshooting

### Common Issues:

1. **"Credentials file not found"**
   - Check file path: `storage/app/google-credentials.json`
   - Ensure file was copied correctly

2. **"Permission denied"**
   - Verify Google Sheets is shared with service account email
   - Check service account has "Editor" permissions

3. **"Spreadsheet not found"**
   - Verify spreadsheet ID in .env file
   - Ensure no extra spaces in the ID

4. **"API not enabled"**
   - Go back to Google Cloud Console
   - Re-enable Google Sheets API

---

## 🎉 Next Steps

Once setup is complete:
1. **Test manual sync**: `php artisan payments:sync-sheets bidirectional`
2. **Create some test payments** in the database
3. **Run sync to Google Sheets**: Watch them appear automatically!
4. **Try editing in Google Sheets**: Run sync from sheets to see database updates

---

## 📞 Need Help?

If you encounter any issues:
1. **Check the error messages** carefully
2. **Verify each step** was completed
3. **Test with a simple Google Sheet** first
4. **Check Google Cloud Console** for any error logs

Ready to start? Let's go through this step by step!