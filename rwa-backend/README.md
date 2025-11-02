# RWA Backend API

Laravel-based backend API for the Resident Welfare Association Management System.

## Quick Start

1. **Install dependencies**:
   ```bash
   composer install
   ```

2. **Database is already configured** in `.env` file with live MySQL credentials

3. **Run migrations**:
   ```bash
   php artisan migrate
   ```

4. **Seed sample data** (optional):
   ```bash
   php artisan db:seed --class=ResidentSeeder
   ```

5. **Start server**:
   ```bash
   php artisan serve
   ```

## API Base URL
```
http://localhost:8000/api/v1
```

## Available Models
- **Resident**: Manages resident information
- **Payment**: Tracks payment records
- **MaintenanceCharge**: Manages monthly charges

## Sample API Calls

### Get all residents
```bash
curl http://localhost:8000/api/v1/residents
```

### Get dashboard stats
```bash
curl http://localhost:8000/api/v1/dashboard/stats
```

### Get paying residents only
```bash
curl http://localhost:8000/api/v1/residents/filter/payers
```

For complete API documentation, see the main README file.

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
