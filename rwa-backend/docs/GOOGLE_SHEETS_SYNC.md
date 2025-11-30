# Google Sheets Payment Sync Service

This service provides bidirectional synchronization between your Laravel payment database and Google Sheets, allowing staff to manage payments through the familiar Google Sheets interface while maintaining data integrity.

## Features

- **Bidirectional Sync**: Automatic synchronization between database and Google Sheets
- **Real-time Updates**: Changes made in either system are reflected in the other
- **Conflict Resolution**: Smart handling of data conflicts and validation
- **Batch Processing**: Efficient bulk operations for large datasets
- **Automatic Scheduling**: Built-in task scheduling for regular sync
- **Error Handling**: Comprehensive error logging and recovery
- **API Endpoints**: RESTful API for manual sync operations

## Setup Instructions

### 1. Google Cloud Setup

1. **Create Google Cloud Project**:
   - Go to [Google Cloud Console](https://console.cloud.google.com/)
   - Create a new project or select existing one

2. **Enable Google Sheets API**:
   - Go to "APIs & Services" > "Library"
   - Search for "Google Sheets API"
   - Click "Enable"

3. **Create Service Account**:
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "Service Account"
   - Fill in service account details
   - Grant "Project" > "Editor" role

4. **Generate Credentials**:
   - Click on created service account
   - Go to "Keys" tab
   - Click "Add Key" > "Create new key"
   - Choose "JSON" format
   - Download the credentials file

### 2. Google Sheets Setup

1. **Create/Open Your Spreadsheet**:
   - Open Google Sheets
   - Create new spreadsheet or open existing "Rosewood RWA" spreadsheet

2. **Share with Service Account**:
   - Click "Share" button
   - Add your service account email (from credentials file)
   - Grant "Editor" permissions

3. **Create Payment Sheet**:
   - Create a sheet named "Monthly Maintenance"
   - Set up headers in row 1:
     ```
     A: Payment Date
     B: House Number
     C: Floor
     D: Resident Name (auto-populated)
     E: Phone (auto-populated)
     F: Payment Month
     G: Amount Due
     H: Amount Paid
     I: Payment Method
     J: Status
     K: Payment Description
     L: Late Fee
     M: Notes
     ```

4. **Add Formulas** (if not already done):
   ```
   D2: =IFERROR(INDEX(ResidentNames, MATCH(B2&C2, ResidentKeys, 0)), "")
   E2: =IFERROR(INDEX(ResidentPhones, MATCH(B2&C2, ResidentKeys, 0)), "")
   ```

### 3. Laravel Setup

1. **Install Dependencies**:
   ```bash
   composer require google/apiclient
   ```

2. **Copy Credentials File**:
   ```bash
   cp path/to/downloaded/credentials.json storage/app/google-credentials.json
   ```

3. **Environment Configuration**:
   ```env
   GOOGLE_SHEETS_CREDENTIALS_PATH=storage/app/google-credentials.json
   GOOGLE_SHEETS_SPREADSHEET_ID=your_spreadsheet_id_here
   GOOGLE_SHEETS_PAYMENT_SHEET_NAME="Monthly Maintenance"
   GOOGLE_SHEETS_PAYMENT_RANGE=A:M
   GOOGLE_SHEETS_AUTO_SYNC=true
   GOOGLE_SHEETS_SYNC_INTERVAL=15
   ```

4. **Get Spreadsheet ID**:
   - From your Google Sheets URL: `https://docs.google.com/spreadsheets/d/{SPREADSHEET_ID}/edit`
   - Copy the ID and add to .env file

5. **Test Connection**:
   ```bash
   php artisan payments:sync-sheets --test
   ```

## Usage

### Command Line Interface

1. **Test Connection**:
   ```bash
   php artisan payments:sync-sheets --test
   ```

2. **Sync Database to Sheets**:
   ```bash
   php artisan payments:sync-sheets to-sheets
   ```

3. **Sync Sheets to Database**:
   ```bash
   php artisan payments:sync-sheets from-sheets
   ```

4. **Bidirectional Sync**:
   ```bash
   php artisan payments:sync-sheets bidirectional
   ```

### API Endpoints

All endpoints require authentication (Bearer token).

1. **Test Connection**:
   ```
   GET /api/v1/payments/google-sheets/test-connection
   ```

2. **Sync to Sheets**:
   ```
   POST /api/v1/payments/google-sheets/sync-to-sheets
   ```

3. **Sync from Sheets**:
   ```
   POST /api/v1/payments/google-sheets/sync-from-sheets
   ```

4. **Bidirectional Sync**:
   ```
   POST /api/v1/payments/google-sheets/bidirectional-sync
   ```

5. **Get Sync Status**:
   ```
   GET /api/v1/payments/google-sheets/sync-status
   ```

### Automatic Scheduling

The service includes automatic scheduling that runs:

1. **Every 15 minutes** (configurable):
   - Full bidirectional sync
   - Background processing
   - Error logging

2. **Business hours only** (8 AM - 8 PM, weekdays):
   - Alternative scheduling option
   - Reduced server load

To enable automatic sync:
```bash
# Add to cron tab (Linux/Mac) or Task Scheduler (Windows)
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Data Flow

### Database to Sheets Sync
1. Fetch all payments from database
2. Clear existing sheet data (preserving headers)
3. Transform payment data to sheet format
4. Batch upload to Google Sheets
5. Update sync timestamps

### Sheets to Database Sync
1. Read all data from Google Sheets
2. Validate and parse each row
3. Match residents by house number and floor
4. Create or update payment records
5. Handle conflicts and errors

### Bidirectional Sync
1. First sync: Sheets → Database (capture manual changes)
2. Second sync: Database → Sheets (ensure consistency)
3. Conflict resolution and validation

## Error Handling

### Common Issues

1. **Authentication Errors**:
   - Check credentials file path
   - Verify service account permissions
   - Ensure spreadsheet is shared

2. **Data Validation Errors**:
   - Missing required fields (house number, payment month)
   - Invalid resident information
   - Duplicate payment entries

3. **API Quota Errors**:
   - Google Sheets API has rate limits
   - Automatic retry with exponential backoff
   - Batch operations to reduce API calls

### Logging

All sync operations are logged to:
- `storage/logs/laravel.log` (general application logs)
- `storage/logs/payment-sync.log` (scheduled sync logs)

Log entries include:
- Sync direction and timestamp
- Number of records processed
- Errors and warnings
- Performance metrics

## Monitoring

### Sync Status Dashboard

Use the sync status endpoint to monitor:
- Total payments in database
- Synced vs unsynced payments
- Last sync timestamp
- Sync percentage

### Health Checks

1. **Connection Test**: Verify Google Sheets API access
2. **Data Integrity**: Compare record counts between systems
3. **Sync Freshness**: Monitor last sync timestamp

## Security

### Best Practices

1. **Credentials Security**:
   - Store credentials file outside web root
   - Use environment variables for sensitive data
   - Rotate service account keys regularly

2. **Access Control**:
   - Limit service account permissions
   - Use dedicated Google Cloud project
   - Monitor API usage and access logs

3. **Data Validation**:
   - Validate all incoming data
   - Sanitize user inputs
   - Handle malformed data gracefully

## Troubleshooting

### Performance Issues

1. **Large Datasets**:
   - Increase batch size for better performance
   - Use pagination for memory efficiency
   - Consider incremental sync

2. **API Rate Limits**:
   - Implement exponential backoff
   - Reduce sync frequency
   - Use batch operations

### Data Conflicts

1. **Duplicate Payments**:
   - Automatic conflict resolution
   - Last-modified-wins strategy
   - Manual resolution for complex cases

2. **Missing Residents**:
   - Validation during sync
   - Error reporting and logging
   - Manual data correction

## Configuration Reference

### Environment Variables

```env
# Required
GOOGLE_SHEETS_CREDENTIALS_PATH=storage/app/google-credentials.json
GOOGLE_SHEETS_SPREADSHEET_ID=your_spreadsheet_id

# Optional
GOOGLE_SHEETS_PAYMENT_SHEET_NAME="Monthly Maintenance"
GOOGLE_SHEETS_PAYMENT_RANGE=A:M
GOOGLE_SHEETS_AUTO_SYNC=true
GOOGLE_SHEETS_SYNC_INTERVAL=15
```

### Column Mappings

| Database Field | Sheet Column | Description |
|---------------|--------------|-------------|
| payment_date | A | Payment Date |
| house_number | B | House Number |
| floor | C | Floor |
| - | D | Resident Name (formula) |
| - | E | Phone (formula) |
| payment_month | F | Payment Month (YYYY-MM) |
| amount_due | G | Amount Due |
| amount_paid | H | Amount Paid |
| payment_method | I | Payment Method |
| status | J | Status |
| payment_description | K | Description |
| late_fee | L | Late Fee |
| notes | M | Notes |

## Support

For issues and questions:
1. Check the logs for error details
2. Verify Google Sheets API quotas
3. Test connection using the test command
4. Review Google Cloud Console for API errors