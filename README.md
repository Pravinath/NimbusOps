# NimbusOps

[![NimbusOps CI](https://github.com/Pravinath/NimbusOps/actions/workflows/ci.yml/badge.svg)](https://github.com/Pravinath/NimbusOps/actions/workflows/ci.yml)

NimbusOps is an API-first field service and complaint resolution platform built with Laravel. It covers complaint intake, AI-assisted classification, technician dispatch, work orders, SLA monitoring, spare-parts usage, feedback, notifications, auditing, and reporting.

## Business Problem

Service organizations often coordinate complaints through calls, messaging apps, and spreadsheets. That creates delayed resolution, poor SLA visibility, uneven technician workload, missing inventory history, and limited reporting. NimbusOps centralizes those workflows behind a secure REST API.

## Roles

Customer, call-center agent, dispatcher, technician, inventory manager, supervisor, and administrator.

## Features

- Sanctum token authentication and active/inactive users
- Role middleware and object-level policies
- Customer, service-area, and technician management
- Complaint status workflow and timeline
- Replaceable mock AI classifier
- Technician ranking and assignment history
- Automatic work-order creation and job workflow
- SLA deadlines, scheduled breach checks, and escalation
- Inventory, stock movements, work-order usage, and low-stock alerts
- Feedback and technician performance scores
- Database notifications and audit logs
- Dashboard and reporting APIs
- Docker, Postman, PHPUnit, Pint, and GitHub Actions

## Stack

PHP 8.3, Laravel 12, Sanctum 4, MySQL 8.4, Redis 7, Nginx, Docker Compose, PHPUnit 11, and Postman.

## Architecture

NimbusOps is a modular monolith. Business capabilities live in `app/Modules`; shared Eloquent models live in `app/Models`.

```text
app/Modules/
├── Auth          ├── AIClassification  ├── WorkOrder
├── Customer      ├── Dispatch          ├── SLA
├── ServiceArea   ├── Inventory         ├── Feedback
├── Technician    ├── Notification      ├── Reporting
├── Complaint     └── Audit
```

See [ARCHITECTURE.md](ARCHITECTURE.md).

## Docker Setup

Requirements: Git and Docker Desktop.

```bash
git clone https://github.com/Pravinath/NimbusOps.git
cd NimbusOps
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Open:

- Laravel: http://localhost:8000
- phpMyAdmin: http://localhost:8080
- MySQL host/user/password: `mysql` / `nimbusops` / `secret`

Stop containers with `docker compose down`.

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Demo Accounts

All demo accounts use `password123`.

| Role | Email |
|---|---|
| Customer | customer@nimbusops.test |
| Agent | agent@nimbusops.test |
| Dispatcher | dispatcher@nimbusops.test |
| Technician | technician@nimbusops.test |
| Inventory | inventory@nimbusops.test |
| Supervisor | supervisor@nimbusops.test |
| Admin | admin@nimbusops.test |

## API Authentication

```http
POST /api/auth/login
Accept: application/json
Content-Type: application/json

{"email":"admin@nimbusops.test","password":"password123"}
```

Protected calls require:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

## Postman

Import:

```text
postman/NimbusOps.postman_collection.json
postman/NimbusOps.local.postman_environment.json
```

Select `NimbusOps Local`. Login requests save tokens automatically; creation requests save IDs for later steps.

## Quality Checks

```bash
composer validate --strict
php vendor/bin/pint --test
php artisan test --do-not-cache-result
```

Current suite: **54 tests, 167 assertions**.

## Documentation

- [Architecture](ARCHITECTURE.md)
- [Database schema](DATABASE_SCHEMA.md)
- [API](API_DOCUMENTATION.md)
- [Security](SECURITY.md)
- [Testing](TESTING.md)
- [Demo workflow](docs/DEMO_FLOW.md)

## Screenshots

Suggested portfolio screenshots: Postman login, complaint workflow, phpMyAdmin schema, Docker containers, GitHub Actions success, and dashboard JSON.

## Future Work

Production AI provider, web/mobile UI, geospatial routing, queued email/SMS/push notifications, media storage, multi-tenancy, OpenAPI generation, and production observability.

> Demo credentials and Docker passwords are for local development only.
