const N = window.NimbusOps;

N.loadComplaintFormOptions = async function loadComplaintFormOptions() {
    if (N.currentUser?.role === 'customer') {
        const serviceAreasResponse = await fetch('/api/service-areas', {
            headers: N.authHeaders(),
        });

        if (!serviceAreasResponse.ok) {
            throw new Error('Unable to load service areas.');
        }

        const serviceAreasResult = await serviceAreasResponse.json();

        return {
            customers: N.currentUser.customer
                ? [{ ...N.currentUser.customer, name: N.currentUser.name }]
                : [],
            serviceAreas: serviceAreasResult.data ?? [],
        };
    }

    const [customersResponse, serviceAreasResponse] = await Promise.all([
        fetch('/api/customers', { headers: N.authHeaders() }),
        fetch('/api/service-areas', { headers: N.authHeaders() }),
    ]);

    if (customersResponse.status === 401 || serviceAreasResponse.status === 401) {
        N.clearToken();
        N.renderLogin();
        return { customers: [], serviceAreas: [] };
    }

    if (!customersResponse.ok || !serviceAreasResponse.ok) {
        throw new Error('Unable to load form options.');
    }

    const customersResult = await customersResponse.json();
    const serviceAreasResult = await serviceAreasResponse.json();

    return {
        customers: customersResult.data ?? [],
        serviceAreas: serviceAreasResult.data ?? [],
    };
}

N.renderSelectOptions = function renderSelectOptions(items, labelGetter) {
    return items
        .map((item) => `<option value="${N.escapeHtml(item.id)}">${N.escapeHtml(labelGetter(item))}</option>`)
        .join('');
}

N.complaintCustomerName = function complaintCustomerName(complaint) {
    return complaint.customer?.name
        ?? complaint.customer?.user?.name
        ?? complaint.customer_name
        ?? `Customer #${complaint.customer_id ?? '-'}`;
}

N.renderComplaintTable = function renderComplaintTable(complaints) {
    return `
        <div class="table-row table-head">
            <span>Ticket</span>
            <span>Customer</span>
            <span>Status</span>
            <span>Priority</span>
        </div>
        ${complaints.map((complaint) => {
            const status = complaint.status ?? 'pending';
            const priority = complaint.priority ?? complaint.severity ?? 'normal';

            return `
                <div class="table-row">
                    <span>#CMP-${N.escapeHtml(complaint.id)}</span>
                    <span>${N.escapeHtml(N.complaintCustomerName(complaint))}</span>
                    <span><b class="badge ${N.statusBadgeClass(status)}">${N.escapeHtml(status)}</b></span>
                    <span>${N.escapeHtml(priority)}</span>
                </div>
            `;
        }).join('')}
    `;
}

N.handleCreateComplaint = async function handleCreateComplaint(event, workspace) {
    event.preventDefault();

    const form = event.target;
    const error = document.querySelector('#complaintFormError');
    const button = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);

    error.textContent = '';
    button.disabled = true;
    button.textContent = 'Creating...';

    const payload = {
        customer_id: Number(formData.get('customer_id')),
        title: formData.get('title'),
        description: formData.get('description'),
        priority: formData.get('priority'),
    };

    if (formData.get('service_area_id')) {
        payload.service_area_id = Number(formData.get('service_area_id'));
    }

    if (formData.get('preferred_visit_time')) {
        payload.preferred_visit_time = formData.get('preferred_visit_time');
    }

    try {
        const response = await fetch('/api/complaints', {
            method: 'POST',
            headers: N.jsonAuthHeaders(),
            body: JSON.stringify(payload),
        });

        const result = await response.json();

        if (response.status === 401) {
            N.clearToken();
            N.renderLogin();
            return;
        }

        if (!response.ok) {
            const firstError = result.errors
                ? Object.values(result.errors).flat()[0]
                : result.message;

            throw new Error(firstError || 'Complaint could not be created.');
        }

        await N.renderComplaintsPage(workspace);
    } catch (errorMessage) {
        error.textContent = errorMessage.message;
    } finally {
        button.disabled = false;
        button.textContent = 'Save Complaint';
    }
}
N.renderComplaintsPage = async function renderComplaintsPage(workspace) {
    const isCustomer = N.currentUser?.role === 'customer';
    workspace.innerHTML = `
        <header class="topbar">
            <div>
                <p class="eyebrow">${isCustomer ? 'Customer service portal' : 'Complaint management'}</p>
                <h1>${isCustomer ? 'My Service Requests' : 'Complaints'}</h1>
                <p class="user-line">${isCustomer ? 'Create, review, and track your service requests.' : 'Track customer issues, priority, SLA risk, and current status.'}</p>
            </div>

            <button class="primary-button" id="openComplaintFormButton">Create Complaint</button>
        </header>

        <section class="panel hidden" id="complaintFormPanel">
            <div class="panel-header">
                <h2>Create Complaint</h2>
                <button id="closeComplaintFormButton">Close</button>
            </div>

            <form class="entity-form" id="complaintForm">
                <label>
                    Customer
                    <select name="customer_id" id="customerSelect" required>
                        <option value="">Loading customers...</option>
                    </select>
                </label>

                <label>
                    Service Area
                    <select name="service_area_id" id="serviceAreaSelect">
                        <option value="">No service area</option>
                    </select>
                </label>

                <label>
                    Priority
                    <select name="priority">
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </label>

                <label>
                    Preferred Visit Time
                    <input type="datetime-local" name="preferred_visit_time">
                </label>

                <label class="full-field">
                    Title
                    <input type="text" name="title" maxlength="150" placeholder="Short complaint title" required>
                </label>

                <label class="full-field">
                    Description
                    <textarea name="description" rows="4" maxlength="5000" placeholder="Describe the customer issue" required></textarea>
                </label>

                <p class="form-error full-field" id="complaintFormError"></p>

                <div class="form-actions full-field">
                    <button class="primary-button" type="submit">Save Complaint</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2>${isCustomer ? 'Request History' : 'Complaint Queue'}</h2>
                <button id="refreshComplaintsButton">Refresh</button>
            </div>

            <div id="complaintsContent" class="empty-state">Loading complaints...</div>
        </section>
    `;

    const formPanel = document.querySelector('#complaintFormPanel');

    document
        .querySelector('#openComplaintFormButton')
        .addEventListener('click', () => formPanel.classList.remove('hidden'));

    document
        .querySelector('#closeComplaintFormButton')
        .addEventListener('click', () => formPanel.classList.add('hidden'));

    document
        .querySelector('#refreshComplaintsButton')
        .addEventListener('click', () => N.renderComplaintsPage(workspace));

    document
        .querySelector('#complaintForm')
        .addEventListener('submit', (event) => N.handleCreateComplaint(event, workspace));

    try {
        const { customers, serviceAreas } = await N.loadComplaintFormOptions();

        const customerSelect = document.querySelector('#customerSelect');
        const serviceAreaSelect = document.querySelector('#serviceAreaSelect');

        customerSelect.innerHTML = customers.length
            ? `<option value="">Select customer</option>${N.renderSelectOptions(customers, (customer) => customer.name ?? customer.user?.name ?? `Customer #${customer.id}`)}`
            : '<option value="">No customers available</option>';

        serviceAreaSelect.innerHTML = `<option value="">No service area</option>${N.renderSelectOptions(serviceAreas, (serviceArea) => serviceArea.name ?? `Service Area #${serviceArea.id}`)}`;
    } catch (error) {
        document.querySelector('#customerSelect').innerHTML = '<option value="">Could not load customers</option>';
        document.querySelector('#complaintFormError').textContent = error.message;
    }

    try {
        const response = await fetch('/api/complaints', {
            headers: N.authHeaders(),
        });

        if (response.status === 401) {
            N.clearToken();
            N.renderLogin();
            return;
        }

        if (!response.ok) {
            throw new Error('Unable to load complaints.');
        }

        const result = await response.json();
        const complaints = result.data ?? [];
        const content = document.querySelector('#complaintsContent');

        if (complaints.length === 0) {
            content.className = 'empty-state';
            content.innerHTML = `
                <strong>No complaints found yet.</strong>
                <span>Create a complaint and it will appear here.</span>
            `;
            return;
        }

        content.className = 'table';
        content.innerHTML = N.renderComplaintTable(complaints);
    } catch (error) {
        const content = document.querySelector('#complaintsContent');
        content.className = 'empty-state error-state';
        content.innerHTML = `
            <strong>Could not load complaints.</strong>
            <span>${N.escapeHtml(error.message)}</span>
        `;
    }

}
