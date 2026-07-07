# Appointment Booking System API

A Laravel appointment booking REST API for a medical station.

## Requirements

- PHP 8.3+
- Composer
- MySQL Server
- Git

## Installation, setup

Clone the repository:

```bash
git clone https://github.com/jarvasijudit/appointment-api.git
cd appointment-api
```

Intsall dependencies:
```
composer install
```

Copy the environment file and generate the application key:
```
cp .env.example .env
php artisan key:generate
```

Create a new MySQL database (for example, appointment_api) and make sure your MySQL server is running.
Configure the database connection in the .env file:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appointment_api
DB_USERNAME=root
DB_PASSWORD=
```

## Database Migration
Run the database migrations:
```
php artisan migrate
```

## Database Seeding (Optional)
Populate the database with sample data:
```
php artisan db:seed
```

## Running the Application
Start the development server:
```
php artisan serve
```

The API will be available at:
```
http://localhost:8000/api
```

## Running Tests
```
php artisan test
```