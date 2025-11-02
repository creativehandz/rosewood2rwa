# RWA Management System

A comprehensive Resident Welfare Association management system built with React frontend and Laravel backend to manage monthly maintenance collections and resident information.

## Project Overview

This system helps RWA teams efficiently manage:
- Resident information and contact details
- Monthly maintenance charge collections
- Payment tracking and status monitoring
- Google Sheets integration for data synchronization
- Comprehensive filtering and reporting

## Technology Stack

### Backend (Laravel)
- **Framework**: Laravel 12.x
- **Database**: MySQL
- **API**: RESTful API with JSON responses
- **Authentication**: Laravel Sanctum (future implementation)

### Frontend (React)
- **Framework**: React 18.x
- **Styling**: CSS3 (to be enhanced with Bootstrap/Tailwind)
- **HTTP Client**: Axios (to be added)
- **State Management**: React Context API (to be implemented)

### Database
- **Host**: srv1100.hstgr.io
- **Database**: u151629516_rosewood2rwa
- **Engine**: MySQL

## Project Structure

```
rosewood2rwa/
├── rwa-backend/          # Laravel backend API
│   ├── app/
│   │   ├── Models/       # Eloquent models
│   │   └── Http/Controllers/ # API controllers
│   ├── database/
│   │   ├── migrations/   # Database migrations
│   │   └── seeders/      # Sample data seeders
│   └── routes/
│       └── api.php       # API routes
└── rwa-frontend/         # React frontend
    ├── src/
    ├── public/
    └── package.json
```

## Database Schema

### Tables

#### `residents`
- `id` (Primary Key)
- `flat_number` (Unique)
- `owner_name`
- `contact_number`
- `email`
- `address`
- `status` (active/inactive)
- `monthly_maintenance`
- `google_sheet_data` (JSON)
- `timestamps`

#### `payments`
- `id` (Primary Key)
- `resident_id` (Foreign Key)
- `amount`
- `payment_date` (Nullable)
- `due_date`
- `payment_month` (YYYY-MM format)
- `status` (paid/pending/overdue)
- `payment_method`
- `transaction_reference`
- `remarks`
- `google_sheet_data` (JSON)
- `timestamps`

#### `maintenance_charges`
- `id` (Primary Key)
- `month` (Unique, YYYY-MM format)
- `basic_maintenance`
- `additional_charges`
- `discount`
- `penalty`
- `due_date`
- `description`
- `status` (active/inactive)
- `timestamps`

## API Endpoints

### Residents
- `GET /api/v1/residents` - List all residents with filters
- `POST /api/v1/residents` - Create new resident
- `GET /api/v1/residents/{id}` - Get specific resident
- `PUT /api/v1/residents/{id}` - Update resident
- `DELETE /api/v1/residents/{id}` - Delete resident
- `GET /api/v1/residents/{id}/payments` - Get resident's payments
- `GET /api/v1/residents/filter/payers` - Get paying residents
- `GET /api/v1/residents/filter/non-payers` - Get non-paying residents

### Payments
- `GET /api/v1/payments` - List all payments with filters
- `POST /api/v1/payments` - Record new payment
- `GET /api/v1/payments/{id}` - Get specific payment
- `PUT /api/v1/payments/{id}` - Update payment
- `DELETE /api/v1/payments/{id}` - Delete payment
- `GET /api/v1/payments/filter/by-status/{status}` - Filter by status
- `GET /api/v1/payments/filter/by-month/{month}` - Filter by month
- `GET /api/v1/payments/filter/overdue` - Get overdue payments

### Dashboard
- `GET /api/v1/dashboard/stats` - Get dashboard statistics

### Google Sheets Integration
- `POST /api/v1/sync/google-sheets` - Sync with Google Sheets (future)

## Setup Instructions

### Backend Setup

1. **Navigate to backend directory**:
   ```bash
   cd rwa-backend
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Environment configuration**:
   The `.env` file is already configured with the database credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=srv1100.hstgr.io
   DB_PORT=3306
   DB_DATABASE=u151629516_rosewood2rwa
   DB_USERNAME=u151629516_rosewood2rwa
   DB_PASSWORD=*I91fKBv7
   ```

4. **Run migrations**:
   ```bash
   php artisan migrate
   ```

5. **Seed sample data** (optional):
   ```bash
   php artisan db:seed --class=ResidentSeeder
   ```

6. **Start the server**:
   ```bash
   php artisan serve
   ```
   Backend will be available at `http://localhost:8000`

### Frontend Setup

1. **Navigate to frontend directory**:
   ```bash
   cd rwa-frontend
   ```

2. **Install dependencies**:
   ```bash
   npm install
   ```

3. **Start development server**:
   ```bash
   npm start
   ```
   Frontend will be available at `http://localhost:3000`

## Features

### Current Features
✅ **Backend API**:
- Complete CRUD operations for residents
- Payment management with status tracking
- Maintenance charge configuration
- Advanced filtering capabilities
- Dashboard statistics
- Database with sample data

✅ **Database**:
- Properly structured MySQL database
- Relationships between residents and payments
- Sample data for testing

### Planned Features
🔄 **Frontend Interface**:
- Resident listing with filters (payers/non-payers)
- Payment status dashboard
- Date-wise filtering
- Add/Edit resident functionality
- Payment recording interface

🔄 **Google Sheets Integration**:
- Bi-directional sync with Google Sheets
- Import existing data from sheets
- Export reports to sheets

🔄 **Enhanced Features**:
- User authentication
- Role-based permissions
- Email notifications for due payments
- Receipt generation
- Monthly reports
- Payment reminders

## Sample Data

The system comes with sample data including:
- 6 sample residents (A-101 to C-301)
- Mix of paying and non-paying residents
- Current and previous month payment records
- Maintenance charge configuration

## Usage Examples

### Get All Residents
```
GET /api/v1/residents
```

### Filter Paying Residents
```
GET /api/v1/residents/filter/payers
```

### Get Dashboard Statistics
```
GET /api/v1/dashboard/stats
```

### Record a Payment
```
POST /api/v1/payments
Content-Type: application/json

{
  "resident_id": 1,
  "amount": 2500.00,
  "payment_date": "2025-11-02",
  "due_date": "2025-11-15",
  "payment_month": "2025-11",
  "status": "paid",
  "payment_method": "online",
  "transaction_reference": "TXN123456"
}
```

## Development Status

- ✅ Backend API development: Complete and Running
- ✅ Database schema: Complete and Populated
- ✅ Sample data: Complete (6 residents with payment history)
- ✅ API Testing Interface: Available at http://localhost:8000/api-test.html
- 🔄 Frontend development: In Progress
- 🔄 Google Sheets integration: Planned
- 🔄 Authentication: Planned
- 🔄 Deployment: Planned

## Support

For any questions or issues, please contact the development team.

---
**Last Updated**: November 2, 2025