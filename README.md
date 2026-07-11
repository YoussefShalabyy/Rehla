# Rehla (VistaStay) - Backend API

This is the Laravel backend for the Rehla / VistaStay platform.

## Local Development Setup

To get the project running locally, each developer needs to set up their own local MySQL database. The database is not shared in development to prevent conflicts.

### 1. Prerequisites
- PHP 8.3+
- Composer
- MySQL Database Server (e.g., via XAMPP, MAMP, Laravel Herd, or Docker)
- PHPMyAdmin, TablePlus, or any other database client

### 2. Database Setup
1. Open PHPMyAdmin or your preferred MySQL client.
2. Create a new empty database named `rehla`.
   ```sql
   CREATE DATABASE rehla;
   ```
   *(Note: You can use any name, but `rehla` is recommended).*
3. Copy the environment variables example file:
   ```bash
   cp .env.example .env
   ```
4. Update your `.env` file to connect to the local MySQL database:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=rehla
   DB_USERNAME=root
   DB_PASSWORD=
   ```
   *(Update username and password according to your local MySQL installation).*

### 3. Application Setup
Once the database is created and `.env` is configured:

1. **Install Dependencies:**
   ```bash
   composer install
   ```

2. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```

3. **Migrate and Seed the Database:**
   Run the following command to create all tables and populate the database with required platform settings, admin users, and demo listings.
   ```bash
   php artisan migrate:fresh --seed
   ```
   *Note: This command is entirely safe to run repeatedly on your local environment if you want to wipe the database and start fresh with demo data.*

4. **Serve the API:**
   ```bash
   php artisan serve
   ```
   The API will be available at `http://127.0.0.1:8000/api/v1/`.

## Running Tests
This project uses Pest for testing. A separate testing database `rehla_test` is used automatically (configured in `phpunit.xml`). Make sure you have created a `rehla_test` database if it's not automatically created by your environment.

```bash
php artisan test
```

## Documentation
- Product Requirements: `docs/PRD.md`
- Database Schema: `docs/DATABASE_SCHEMA.md`
- API Reference: `docs/API_REFERENCE.md`
- Business Rules: `docs/BUSINESS_RULES.md`
