# NimbusOps Demo Flow

Use Docker and import the Postman collection/environment first.

## Preparation

1. Start containers with `docker compose up -d`.
2. Run `docker compose exec app php artisan migrate:fresh --seed`.
3. Import both files from `postman/`.
4. Select the `NimbusOps Local` environment.

## Workflow

1. **Login as Admin**. The token is stored automatically.
2. **Create Customer Profile**. The customer ID is stored.
3. **Create Service Area**. The area ID is stored.
4. **Create Technician Profile**. The technician ID is stored.
5. **Create Spare Part**. The part ID is stored.
6. **Login as Customer**.
7. **Create Complaint** with the internet/router sample. The complaint ID is stored.
8. **Login as Dispatcher**.
9. **Classify Complaint**. Mock AI predicts category, priority, skill, parts, and SLA.
10. **Suggest Technicians** and review ranking reasons.
11. **Assign Technician**. Assignment and work-order IDs are stored.
12. **Login as Technician**.
13. **Accept Work Order**.
14. **Mark On The Way**.
15. **Start Work**.
16. **Use Spare Part**.
17. **Add Progress Update**.
18. **Complete Work Order**.
19. **Login as Customer**.
20. **Submit Feedback**.
21. **List Notifications** and mark one read.
22. **Login as Supervisor** and open SLA/technician reports.
23. **Login as Admin** and open dashboard and audit logs.

## Expected Outcomes

- Complaint ends in `resolved`.
- Work order ends in `completed`.
- Technician workload decreases.
- Used stock decreases and movement history exists.
- Customer receives resolution notification.
- Feedback updates technician performance.
- Timeline and audit records show the workflow.
- Dashboard and reports reflect the completed job.

