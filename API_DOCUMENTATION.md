# NimbusOps API Documentation

Base URL: `http://localhost:8000/api`

Send `Accept: application/json` on every request. Protected endpoints require `Authorization: Bearer <token>`.

## Auth

| Method | Endpoint | Access |
|---|---|---|
| POST | /auth/register | Public |
| POST | /auth/login | Public |
| POST | /auth/logout | Authenticated |
| GET | /me | Authenticated |

## Customers and Areas

| Method | Endpoint | Access |
|---|---|---|
| GET | /customers | Admin, agent, dispatcher, supervisor |
| POST | /customers | Admin, agent |
| GET | /customers/{id} | Admin, agent, dispatcher, supervisor |
| GET | /service-areas | Admin, dispatcher, supervisor |
| POST | /service-areas | Admin |
| GET | /service-areas/{id} | Admin, dispatcher, supervisor |

## Technicians

| Method | Endpoint |
|---|---|
| GET, POST | /technicians |
| GET | /technicians/{id} |
| PATCH | /technicians/{id}/availability |
| GET | /technicians/{id}/workload |

## Complaints

| Method | Endpoint |
|---|---|
| GET, POST | /complaints |
| GET | /complaints/{id} |
| PATCH | /complaints/{id}/status |
| GET | /complaints/{id}/timeline |

Customers can create and view only complaints associated with their own customer profile.

Statuses: `new`, `classified`, `assigned`, `technician_on_the_way`, `in_progress`, `resolved`, `closed`, `cancelled`, `escalated`.

Priorities: `low`, `medium`, `high`, `critical`.

## AI and Dispatch

| Method | Endpoint |
|---|---|
| POST | /complaints/{id}/ai-classify |
| GET | /complaints/{id}/ai-classification |
| GET | /complaints/{id}/suggest-technicians |
| POST | /complaints/{id}/assign-technician |

The AI trigger is limited to 10 requests per minute. Assignment automatically creates a work order.

## Work Orders

| Method | Endpoint |
|---|---|
| GET | /work-orders |
| GET | /work-orders/{id} |
| PATCH | /work-orders/{id}/accept |
| PATCH | /work-orders/{id}/on-the-way |
| PATCH | /work-orders/{id}/start |
| PATCH | /work-orders/{id}/pause |
| PATCH | /work-orders/{id}/complete |
| POST | /work-orders/{id}/updates |

Technicians can access only assigned work orders. Status order is enforced.

## Inventory

| Method | Endpoint |
|---|---|
| GET, POST | /spare-parts |
| GET, PATCH | /spare-parts/{id} |
| PATCH | /spare-parts/{id}/stock |
| GET | /inventory/low-stock |
| GET | /stock-movements |
| POST | /work-orders/{id}/use-spare-part |

Stock cannot become negative. Every adjustment and work-order usage creates movement history.

## Feedback and Notifications

| Method | Endpoint |
|---|---|
| POST | /feedback |
| GET | /feedback/complaint/{complaint} |
| GET | /notifications |
| PATCH | /notifications/{notification}/read |

Feedback is accepted only after work-order completion and recalculates technician performance.

## Reports and Audit

| Method | Endpoint |
|---|---|
| GET | /admin/dashboard |
| GET | /reports/sla-performance |
| GET | /reports/technician-performance |
| GET | /reports/area-wise-complaints |
| GET | /reports/spare-parts-usage |
| GET | /reports/customer-satisfaction |
| GET | /reports/common-issue-categories |
| GET | /reports/monthly-complaint-trends |
| GET | /audit-logs |

Audit logs are admin-only. Reporting routes apply role-specific restrictions.

## Response Conventions

- `200`: successful read/update
- `201`: resource created
- `401`: missing or invalid authentication
- `403`: authenticated but unauthorized
- `404`: resource not found
- `422`: validation or workflow violation
- `429`: rate limit exceeded

Use the Postman collection for sample payloads and automatic environment variables.

