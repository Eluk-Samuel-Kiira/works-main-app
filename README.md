# Works — AI-Powered Job Posting Platform

**Works** is the core backend application for an AI-powered job posting platform. Built with Laravel 12, it serves as the central API consumed by the Works web frontend. It handles job listings, company profiles, user management, authentication, and all supporting lookup data (industries, job types, salary ranges, etc.).

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 12 |
| Language | PHP 8.2+ |
| Database | PostgreSQL |
| Auth | Laravel Sanctum + Breeze |
| Permissions | Spatie Laravel Permission |
| Queue | Database |
| Frontend Assets | Vite |

---

## Features

- RESTful API (v1) with full CRUD for job-related resources
- User authentication (register, login, password reset, email verification)
- Job listings with filtering by category, type, location, salary, experience, and education level
- Company profiles and industry classification
- Featured and urgent job post support
- API key management and request logging
- Payment plans and transaction tracking
- Role-based access control via Spatie permissions
- Built-in API tester page (`/api-test.html`)

---

## Prerequisites

- PHP >= 8.2
- Composer
- Node.js & npm
- PostgreSQL

---

## Getting Started

### 1. Clone the repository

```bash
git clone <repository-url>
cd works-main-app
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure your database:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=works
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 4. Create the database

```bash
sudo -i -u postgres psql -c "CREATE DATABASE works;"
```

### 5. Run migrations

```bash
php artisan migrate
```

### 6. (Optional) Seed the database

```bash
php artisan db:seed
```

---

## Running the Application

### Development (server + queue + Vite in one command)

```bash
composer dev
```

### Or run individually

```bash
php artisan serve       # API server at http://127.0.0.1:8000
npm run dev             # Vite asset watcher
php artisan queue:listen --tries=1
```

---

## API Overview

Base URL: `http://127.0.0.1:8000/api`

### Read-only endpoints (consumed by web frontend)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/jobs-data-from-main` | All job listings |
| GET | `/jobs-data-from-main/featured` | Featured jobs |
| GET | `/jobs-data-from-main/urgent` | Urgent jobs |
| GET | `/jobs-data-from-main/{job}` | Single job by slug |
| GET | `/jobs-data-from-main/id/{id}` | Single job by ID |
| GET | `/popular-searches` | Popular search terms |
| GET | `/user-data` | Authenticated user data |

### v1 CRUD API (`/api/v1/`)

| Resource | Endpoint |
|----------|----------|
| Users | `/v1/users` |
| Companies | `/v1/companies` |
| Industries | `/v1/industries` |
| Job Categories | `/v1/job-categories` |
| Job Types | `/v1/job-types` |
| Job Locations | `/v1/job-locations` |
| Experience Levels | `/v1/experience-levels` |
| Education Levels | `/v1/education-levels` |
| Salary Ranges | `/v1/salary-ranges` |

All v1 resources support standard `index`, `store`, `show`, `update`, and `destroy` operations.

---

## API Tester

A built-in vanilla JS API tester is available at:

```
http://127.0.0.1:8000/api-test.html
```

---

## Running Tests

```bash
composer test
```

---

## License

MIT
