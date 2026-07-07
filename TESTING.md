# NimbusOps Testing Guide

## Test Stack

NimbusOps uses PHPUnit through Laravel's Artisan test runner. Tests use in-memory SQLite, array cache/session drivers, and synchronous queues as configured in `phpunit.xml`.

## Run Tests

```bash
php artisan test --do-not-cache-result
```

Run one class:

```bash
php artisan test --filter=WorkOrderTest
```

## Quality Gates

```bash
composer validate --strict
php vendor/bin/pint --test
git diff --check
```

Apply formatting with `php vendor/bin/pint`, then rerun tests.

## Current Coverage Areas

- Sanctum authentication and protected routes
- Customer profile validation
- Service-area APIs
- Technician creation, skills, availability, and authorization
- Complaint ownership, timeline, and workflow
- AI classification and access control
- Technician ranking and assignment
- Work-order access, progress, and completion
- SLA deadlines, breaches, and command
- Inventory movements, usage, and low stock
- Feedback and performance calculation
- Notifications and read state
- Audit access and records
- Dashboard and reporting aggregation

Current suite: **54 tests and 167 assertions**.

## Docker Verification

```bash
docker compose up -d --build
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan about
docker compose exec redis redis-cli ping
docker compose ps
```

Expected: MySQL healthy, Redis `PONG`, and all five services running.

## CI

`.github/workflows/ci.yml` runs on pushes to `main` and `phase-*` plus pull requests to `main`. It validates Composer, installs dependencies, checks Pint, and runs the full test suite.

## Adding Tests

- Use `RefreshDatabase` for database tests.
- Test success, validation failure, authorization failure, and state changes.
- Assert both HTTP responses and database effects.
- Keep external providers replaceable and deterministic in tests.

