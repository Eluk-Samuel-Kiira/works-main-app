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

## Running with Docker

This project includes Docker support based on the [Laravel Docker Examples](https://github.com/dockersamples/laravel-docker-examples) setup. It uses **PHP-FPM**, **Nginx**, **PostgreSQL**, and **Redis**, with separate configurations for development and production.

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) installed
- [Docker Compose](https://docs.docker.com/compose/install/) installed

---

### Development

The development environment is defined in `compose.dev.yaml`. It extends the production setup and adds tools like **Xdebug**, volume mounts for hot reloading, and a **workspace** sidecar container with Composer, Node.js, and NPM.

#### 1. Environment setup

```bash
cp .env.example .env
```

Update your `.env` to use the Docker service names:

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=works
DB_USERNAME=postgres
DB_PASSWORD=your_password

REDIS_HOST=redis
REDIS_PORT=6379
```

#### 2. Build and start the containers

```bash
docker compose -f compose.dev.yaml up -d --build
```

#### 3. Install dependencies & set up the application

Use the workspace container to run Composer, Artisan, and NPM commands:

```bash
# Install PHP dependencies
docker compose -f compose.dev.yaml exec workspace composer install

# Generate application key
docker compose -f compose.dev.yaml exec workspace php artisan key:generate

# Run migrations
docker compose -f compose.dev.yaml exec workspace php artisan migrate

# (Optional) Seed the database
docker compose -f compose.dev.yaml exec workspace php artisan db:seed

# Build frontend assets
docker compose -f compose.dev.yaml exec workspace npm install
docker compose -f compose.dev.yaml exec workspace npm run build
```

#### 4. Access the application

Visit [http://localhost](http://localhost) in your browser.

#### Useful commands

```bash
# Open a shell in the workspace container
docker compose -f compose.dev.yaml exec workspace bash

# View logs
docker compose -f compose.dev.yaml logs -f

# Stop the containers
docker compose -f compose.dev.yaml down
```

---

### Production

The production environment is defined in `compose.yaml` and is optimised for deployment with multi-stage builds and minimal image sizes.

#### 1. Environment setup

```bash
cp .env.example .env
```

Configure your `.env` with production values, making sure `APP_ENV=production` and `APP_DEBUG=false`.

#### 2. Build and start the containers

```bash
docker compose up -d --build
```

#### 3. Run post-deployment steps

```bash
docker compose exec php-fpm php artisan key:generate
docker compose exec php-fpm php artisan migrate --force
docker compose exec php-fpm php artisan config:cache
docker compose exec php-fpm php artisan route:cache
docker compose exec php-fpm php artisan view:cache
```

#### 4. Stop the containers

```bash
docker compose down
```

---

> **Note:** HTTPS is not configured in this setup. For production deployments, it is strongly recommended to configure SSL certificates and serve the application over HTTPS using a reverse proxy such as Nginx or Traefik.

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