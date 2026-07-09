const N = window.NimbusOps;

N.handleCreateCustomer = async function handleCreateCustomer(event, workspace) {
    event.preventDefault();

    const form = event.target;
    const error = document.querySelector('#customerFormError');
    const button = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);

    error.textContent = '';
    button.disabled = true;
    button.textContent = 'Creating...';

    const payload = {
        user_id: Number(formData.get('user_id')),
        phone: formData.get('phone'),
        address: formData.get('address'),
        city: formData.get('city'),
        status: formData.get('status'),
    };

    try {
        const response = await fetch('/api/customers', {
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

            throw new Error(firstError || 'Customer could not be created.');
        }

        await N.renderCustomersPage(workspace);
    } catch (errorMessage) {
        error.textContent = errorMessage.message;
    } finally {
        button.disabled = false;
        button.textContent = 'Save Customer';
    }
}

N.renderCustomersPage = async function renderCustomersPage(workspace) {
    workspace.innerHTML = `
        <header class="topbar">
            <div>
                <p class="eyebrow">Customer records</p>
                <h1>Customers</h1>
                <p class="user-line">View customer profiles connected to complaints and service requests.</p>
            </div>

            <button class="primary-button" id="openCustomerFormButton">Add Customer</button>
        </header>

        <section class="panel hidden" id="customerFormPanel">
            <div class="panel-header">
                <h2>Add Customer</h2>
                <button id="closeCustomerFormButton">Close</button>
            </div>

            <form class="entity-form" id="customerForm">
                <label>
                    User ID
                    <input type="number" name="user_id" min="1" placeholder="Example: 1" required>
                </label>

                <label>
                    Phone
                    <input type="text" name="phone" maxlength="30" placeholder="0771234567">
                </label>

                <label>
                    City
                    <input type="text" name="city" maxlength="100" placeholder="Colombo" required>
                </label>

                <label>
                    Status
                    <select name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>

                <label class="full-field">
                    Address
                    <input type="text" name="address" maxlength="255" placeholder="Customer address" required>
                </label>

                <p class="form-help full-field">Use an existing user ID with role customer. User ID 1 is already used in your current local database.</p>
                <p class="form-error full-field" id="customerFormError"></p>

                <div class="form-actions full-field">
                    <button class="primary-button" type="submit">Save Customer</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2>Customer Directory</h2>
                <button id="refreshCustomersButton">Refresh</button>
            </div>

            <div id="customersContent" class="empty-state">Loading customers...</div>
        </section>
    `;

    const formPanel = document.querySelector('#customerFormPanel');

    document
        .querySelector('#openCustomerFormButton')
        .addEventListener('click', () => formPanel.classList.remove('hidden'));

    document
        .querySelector('#closeCustomerFormButton')
        .addEventListener('click', () => formPanel.classList.add('hidden'));

    document
        .querySelector('#refreshCustomersButton')
        .addEventListener('click', () => N.renderCustomersPage(workspace));

    document
        .querySelector('#customerForm')
        .addEventListener('submit', (event) => N.handleCreateCustomer(event, workspace));

    try {
        const response = await fetch('/api/customers', {
            headers: N.authHeaders(),
        });

        if (response.status === 401) {
            N.clearToken();
            N.renderLogin();
            return;
        }

        if (!response.ok) {
            throw new Error('Unable to load customers.');
        }

        const result = await response.json();
        const customers = result.data ?? [];
        const content = document.querySelector('#customersContent');

        if (customers.length === 0) {
            content.className = 'empty-state';
            content.innerHTML = `
                <strong>No customers found yet.</strong>
                <span>Create a customer profile and it will appear here.</span>
            `;
            return;
        }

        content.className = 'table';
        content.innerHTML = `
            <div class="table-row table-head customer-table-row">
                <span>Name</span>
                <span>Email</span>
                <span>City</span>
                <span>Status</span>
            </div>
            ${customers.map((customer) => {
                const name = customer.name ?? customer.user?.name ?? `Customer #${customer.id}`;
                const email = customer.email ?? customer.user?.email ?? '-';
                const status = customer.status ?? 'active';

                return `
                    <div class="table-row customer-table-row">
                        <span>${N.escapeHtml(name)}</span>
                        <span>${N.escapeHtml(email)}</span>
                        <span>${N.escapeHtml(customer.city ?? '-')}</span>
                        <span><b class="badge ${N.statusBadgeClass(status)}">${N.escapeHtml(status)}</b></span>
                    </div>
                `;
            }).join('')}
        `;
    } catch (error) {
        const content = document.querySelector('#customersContent');
        content.className = 'empty-state error-state';
        content.innerHTML = `
            <strong>Could not load customers.</strong>
            <span>${N.escapeHtml(error.message)}</span>
        `;
    }

}
