# NimbusOps Security

## Authentication

Laravel Sanctum issues personal access tokens. Passwords use Laravel's hashed cast. Logout deletes the active token. Inactive users cannot log in.

## Authorization

- Role middleware restricts routes by business role.
- Complaint policy prevents customers viewing other customers' complaints.
- Work-order policy prevents technicians viewing or changing unassigned jobs.
- Form Requests add contextual authorization to sensitive writes.
- Audit logs have no update or delete endpoint.

## Validation

All write endpoints use Form Request validation. Enumerated values restrict roles, priorities, skills, availability, complaint statuses, and work-order statuses. Foreign IDs must exist and unique constraints prevent duplicate profiles and feedback.

## Workflow Integrity

Complaint and work-order state transitions are explicitly defined. Invalid jumps return HTTP 422. Assignment, inventory, SLA, and feedback operations use transactions. Row locking protects technician assignment and stock quantities from concurrent updates.

## API Protection

- Protected routes use `auth:sanctum`.
- AI classification uses `throttle:10,1`.
- Requests should send `Accept: application/json`.
- CORS should be restricted to approved frontend origins in production.
- Production must set `APP_DEBUG=false`.

## Secrets

Secrets belong in `.env`, which is ignored by Git. Never commit API keys, production passwords, tokens, or customer data. Docker credentials in this repository are local-development defaults only.

## Audit Trail

Sensitive actions record actor, entity, IP address, user agent, timestamp, and metadata. Covered events include complaint creation, AI classification, technician assignment, work-order completion, stock changes, part usage, feedback, and SLA breaches.

## Notifications

Notifications are scoped to the owning user. The mark-read endpoint queries through the authenticated user's notification relationship, preventing access to another user's notification ID.

## Production Checklist

- Rotate all demo and Docker credentials.
- Use HTTPS and secure reverse-proxy headers.
- Restrict CORS and trusted proxies.
- Use a managed database and Redis with authentication.
- Queue notifications and long-running jobs.
- Configure backups and retention.
- Centralize logs without sensitive payloads.
- Run dependency/security scanning.
- Configure rate limits for login and other high-volume endpoints.
- Disable demo users or replace their passwords.

