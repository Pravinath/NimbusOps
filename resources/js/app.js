import './bootstrap';

const app = document.querySelector('#app');
const tokenKey = 'nimbusops_token';
let currentUser = null;

function getToken() {
    return localStorage.getItem(tokenKey);
}

function saveToken(token) {
    localStorage.setItem(tokenKey, token);
}

function clearToken() {
    localStorage.removeItem(tokenKey);
}

function authHeaders() {
    return {
        Accept: 'application/json',
        Authorization: `Bearer ${getToken()}`,
    };
}

function jsonAuthHeaders() {
    return {
        ...authHeaders(),
        'Content-Type': 'application/json',
    };
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function statusBadgeClass(status) {
    const normalized = String(status ?? '').toLowerCase();

    if (['resolved', 'completed', 'active'].includes(normalized)) {
        return 'success';
    }

    if (['assigned', 'in_progress', 'in progress'].includes(normalized)) {
        return 'info';
    }

    return 'warning';
}
function renderLogin() {
    app.innerHTML = `
        <main class="auth-page">
            <section class="auth-panel">
                <div class="auth-brand">
                    <span class="brand-mark">N</span>
                    <div>
                        <strong>NimbusOps</strong>
                        <small>Service Desk Dashboard</small>
                    </div>
                </div>

                <form class="login-form" id="loginForm">
                    <div>
                        <h1>Sign in</h1>
                        <p>Use your NimbusOps account to continue.</p>
                    </div>

                    <label>
                        Email
                        <input
                            type="email"
                            name="email"
                            value="admin@nimbusops.test"
                            required
                        >
                    </label>

                    <label>
                        Password
                        <input
                            type="password"
                            name="password"
                            value="password123"
                            required
                        >
                    </label>

                    <p class="form-error" id="formError"></p>

                    <button class="primary-button" type="submit">Login</button>
                </form>
            </section>
        </main>
    `;

    document.querySelector('#loginForm').addEventListener('submit', handleLogin);
}

async function handleLogin(event) {
    event.preventDefault();

    const form = event.target;
    const error = document.querySelector('#formError');
    const button = form.querySelector('button');

    error.textContent = '';
    button.disabled = true;
    button.textContent = 'Logging in...';

    const formData = new FormData(form);

    try {
        const response = await fetch('/api/auth/login', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: formData.get('email'),
                password: formData.get('password'),
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Login failed.');
        }

        saveToken(data.token);
        currentUser = data.user;
        renderDashboard(data.user);
    } catch (errorMessage) {
        error.textContent = errorMessage.message;
    } finally {
        button.disabled = false;
        button.textContent = 'Login';
    }
}

async function fetchCurrentUser() {
    const response = await fetch('/api/me', {
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${getToken()}`,
        },
    });

    if (!response.ok) {
        clearToken();
        renderLogin();
        return null;
    }

    const data = await response.json();

    return data.user;
}

async function logout() {
    await fetch('/api/auth/logout', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${getToken()}`,
        },
    });

    clearToken();
    renderLogin();
}

function renderDashboard(user) {
    currentUser = user;
    app.innerHTML = `
        <main class="layout">
            <aside class="sidebar">
                <div class="brand">
                    <span class="brand-mark">N</span>
                    <div>
                        <strong>NimbusOps</strong>
                        <small>Service Desk</small>
                    </div>
                </div>

                <nav class="nav">
                    <button class="nav-item active" data-page="dashboard">Dashboard</button>
                    <button class="nav-item" data-page="complaints">Complaints</button>
                    <button class="nav-item" data-page="customers">Customers</button>
                    <button class="nav-item" data-page="technicians">Technicians</button>
                    <button class="nav-item" data-page="reports">Reports</button>
                </nav>
            </aside>

            <section class="workspace">
                <header class="topbar">
                    <div>
                        <p class="eyebrow">Operations overview</p>
                        <h1>Dashboard</h1>
                        <p class="user-line">Signed in as ${user.name} (${user.role})</p>
                    </div>

                    <div class="topbar-actions">
                        <button class="primary-button">New Complaint</button>
                        <button class="secondary-button" id="logoutButton">Logout</button>
                    </div>
                </header>

                <section class="stats-grid">
                    <article class="stat-card">
                        <span>Open Complaints</span>
                        <strong>24</strong>
                        <small>8 high priority</small>
                    </article>

                    <article class="stat-card">
                        <span>Available Technicians</span>
                        <strong>12</strong>
                        <small>3 on urgent jobs</small>
                    </article>

                    <article class="stat-card">
                        <span>SLA Risk</span>
                        <strong>5</strong>
                        <small>Needs attention today</small>
                    </article>

                    <article class="stat-card">
                        <span>Resolved Today</span>
                        <strong>18</strong>
                        <small>+12% from yesterday</small>
                    </article>
                </section>

                <section class="content-grid">
                    <article class="panel">
                        <div class="panel-header">
                            <h2>Recent Complaints</h2>
                            <button>View all</button>
                        </div>

                        <div class="table">
                            <div class="table-row table-head">
                                <span>Ticket</span>
                                <span>Customer</span>
                                <span>Status</span>
                                <span>Priority</span>
                            </div>
                            <div class="table-row">
                                <span>#CMP-1042</span>
                                <span>Arun Stores</span>
                                <span><b class="badge warning">Pending</b></span>
                                <span>High</span>
                            </div>
                            <div class="table-row">
                                <span>#CMP-1041</span>
                                <span>Nova Clinic</span>
                                <span><b class="badge info">Assigned</b></span>
                                <span>Medium</span>
                            </div>
                            <div class="table-row">
                                <span>#CMP-1040</span>
                                <span>Metro Foods</span>
                                <span><b class="badge success">Resolved</b></span>
                                <span>Low</span>
                            </div>
                        </div>
                    </article>

                    <article class="panel">
                        <div class="panel-header">
                            <h2>Technician Load</h2>
                        </div>

                        <div class="workload-list">
                            <div>
                                <span>Electrical</span>
                                <strong>72%</strong>
                            </div>
                            <div>
                                <span>Plumbing</span>
                                <strong>58%</strong>
                            </div>
                            <div>
                                <span>Network</span>
                                <strong>81%</strong>
                            </div>
                        </div>
                    </article>
                </section>
            </section>
        </main>
    `;

    document.querySelector('#logoutButton').addEventListener('click', logout);

    document.querySelectorAll('.nav-item').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.nav-item').forEach((item) => {
                item.classList.remove('active');
            });

            button.classList.add('active');
            renderPage(button.dataset.page);
        });
    });
}

function renderDashboardPage(workspace) {
    workspace.innerHTML = `
        <header class="topbar">
            <div>
                <p class="eyebrow">Operations overview</p>
                <h1>Dashboard</h1>
                <p class="user-line">Signed in as ${currentUser.name} (${currentUser.role})</p>
            </div>

            <div class="topbar-actions">
                <button class="primary-button">New Complaint</button>
                <button class="secondary-button" id="logoutButton">Logout</button>
            </div>
        </header>

        <section class="stats-grid">
            <article class="stat-card">
                <span>Open Complaints</span>
                <strong>24</strong>
                <small>8 high priority</small>
            </article>

            <article class="stat-card">
                <span>Available Technicians</span>
                <strong>12</strong>
                <small>3 on urgent jobs</small>
            </article>

            <article class="stat-card">
                <span>SLA Risk</span>
                <strong>5</strong>
                <small>Needs attention today</small>
            </article>

            <article class="stat-card">
                <span>Resolved Today</span>
                <strong>18</strong>
                <small>+12% from yesterday</small>
            </article>
        </section>

        <section class="content-grid">
            <article class="panel">
                <div class="panel-header">
                    <h2>Recent Complaints</h2>
                    <button>View all</button>
                </div>

                <div class="table">
                    <div class="table-row table-head">
                        <span>Ticket</span>
                        <span>Customer</span>
                        <span>Status</span>
                        <span>Priority</span>
                    </div>
                    <div class="table-row">
                        <span>#CMP-1042</span>
                        <span>Arun Stores</span>
                        <span><b class="badge warning">Pending</b></span>
                        <span>High</span>
                    </div>
                    <div class="table-row">
                        <span>#CMP-1041</span>
                        <span>Nova Clinic</span>
                        <span><b class="badge info">Assigned</b></span>
                        <span>Medium</span>
                    </div>
                    <div class="table-row">
                        <span>#CMP-1040</span>
                        <span>Metro Foods</span>
                        <span><b class="badge success">Resolved</b></span>
                        <span>Low</span>
                    </div>
                </div>
            </article>

            <article class="panel">
                <div class="panel-header">
                    <h2>Technician Load</h2>
                </div>

                <div class="workload-list">
                    <div>
                        <span>Electrical</span>
                        <strong>72%</strong>
                    </div>
                    <div>
                        <span>Plumbing</span>
                        <strong>58%</strong>
                    </div>
                    <div>
                        <span>Network</span>
                        <strong>81%</strong>
                    </div>
                </div>
            </article>
        </section>
    `;

    document.querySelector('#logoutButton').addEventListener('click', logout);
}

async function loadComplaintFormOptions() {
    const [customersResponse, serviceAreasResponse] = await Promise.all([
        fetch('/api/customers', { headers: authHeaders() }),
        fetch('/api/service-areas', { headers: authHeaders() }),
    ]);

    if (customersResponse.status === 401 || serviceAreasResponse.status === 401) {
        clearToken();
        renderLogin();
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

function renderSelectOptions(items, labelGetter) {
    return items
        .map((item) => `<option value="${escapeHtml(item.id)}">${escapeHtml(labelGetter(item))}</option>`)
        .join('');
}

function complaintCustomerName(complaint) {
    return complaint.customer?.name
        ?? complaint.customer?.user?.name
        ?? complaint.customer_name
        ?? `Customer #${complaint.customer_id ?? '-'}`;
}

function renderComplaintTable(complaints) {
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
                    <span>#CMP-${escapeHtml(complaint.id)}</span>
                    <span>${escapeHtml(complaintCustomerName(complaint))}</span>
                    <span><b class="badge ${statusBadgeClass(status)}">${escapeHtml(status)}</b></span>
                    <span>${escapeHtml(priority)}</span>
                </div>
            `;
        }).join('')}
    `;
}

async function handleCreateComplaint(event, workspace) {
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
            headers: jsonAuthHeaders(),
            body: JSON.stringify(payload),
        });

        const result = await response.json();

        if (response.status === 401) {
            clearToken();
            renderLogin();
            return;
        }

        if (!response.ok) {
            const firstError = result.errors
                ? Object.values(result.errors).flat()[0]
                : result.message;

            throw new Error(firstError || 'Complaint could not be created.');
        }

        await renderComplaintsPage(workspace);
    } catch (errorMessage) {
        error.textContent = errorMessage.message;
    } finally {
        button.disabled = false;
        button.textContent = 'Save Complaint';
    }
}

async function handleCreateTechnician(event, workspace) {
    event.preventDefault();

    const form = event.target;
    const error = document.querySelector('#technicianFormError');
    const button = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);

    error.textContent = '';
    button.disabled = true;
    button.textContent = 'Creating...';

    const payload = {
        user_id: Number(formData.get('user_id')),
        skill_category: formData.get('skill_category'),
        availability_status: formData.get('availability_status'),
    };

    if (formData.get('service_area_id')) {
        payload.service_area_id = Number(formData.get('service_area_id'));
    }

    try {
        const response = await fetch('/api/technicians', {
            method: 'POST',
            headers: jsonAuthHeaders(),
            body: JSON.stringify(payload),
        });

        const result = await response.json();

        if (response.status === 401) {
            clearToken();
            renderLogin();
            return;
        }

        if (!response.ok) {
            const firstError = result.errors
                ? Object.values(result.errors).flat()[0]
                : result.message;

            throw new Error(firstError || 'Technician could not be created.');
        }

        await renderTechniciansPage(workspace);
    } catch (errorMessage) {
        error.textContent = errorMessage.message;
    } finally {
        button.disabled = false;
        button.textContent = 'Save Technician';
    }
}

async function renderTechniciansPage(workspace) {
    workspace.innerHTML = `
        <header class="topbar">
            <div>
                <p class="eyebrow">Field workforce</p>
                <h1>Technicians</h1>
                <p class="user-line">Monitor availability, skill area, and workload before assignment.</p>
            </div>

            <button class="primary-button" id="openTechnicianFormButton">Add Technician</button>
        </header>

        <section class="panel hidden" id="technicianFormPanel">
            <div class="panel-header">
                <h2>Add Technician</h2>
                <button id="closeTechnicianFormButton">Close</button>
            </div>

            <form class="entity-form" id="technicianForm">
                <label>
                    User ID
                    <input type="number" name="user_id" min="1" placeholder="Example: 4" required>
                </label>

                <label>
                    Service Area
                    <select name="service_area_id" id="technicianServiceAreaSelect">
                        <option value="">No service area</option>
                    </select>
                </label>

                <label>
                    Skill Category
                    <select name="skill_category" required>
                        <option value="ac">AC</option>
                        <option value="network">Network</option>
                        <option value="electrical">Electrical</option>
                        <option value="plumbing">Plumbing</option>
                        <option value="appliance">Appliance</option>
                        <option value="facility">Facility</option>
                        <option value="general">General</option>
                    </select>
                </label>

                <label>
                    Availability
                    <select name="availability_status">
                        <option value="available">Available</option>
                        <option value="busy">Busy</option>
                        <option value="offline">Offline</option>
                        <option value="on_leave">On leave</option>
                    </select>
                </label>

                <p class="form-help full-field">Use an existing user ID with role technician. User ID 4 is already used in your current local database.</p>
                <p class="form-error full-field" id="technicianFormError"></p>

                <div class="form-actions full-field">
                    <button class="primary-button" type="submit">Save Technician</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2>Technician Directory</h2>
                <button id="refreshTechniciansButton">Refresh</button>
            </div>

            <div id="techniciansContent" class="empty-state">Loading technicians...</div>
        </section>
    `;

    const formPanel = document.querySelector('#technicianFormPanel');

    document
        .querySelector('#openTechnicianFormButton')
        .addEventListener('click', () => formPanel.classList.remove('hidden'));

    document
        .querySelector('#closeTechnicianFormButton')
        .addEventListener('click', () => formPanel.classList.add('hidden'));

    document
        .querySelector('#refreshTechniciansButton')
        .addEventListener('click', () => renderTechniciansPage(workspace));

    document
        .querySelector('#technicianForm')
        .addEventListener('submit', (event) => handleCreateTechnician(event, workspace));

    try {
        const serviceAreasResponse = await fetch('/api/service-areas', {
            headers: authHeaders(),
        });

        if (serviceAreasResponse.status === 401) {
            clearToken();
            renderLogin();
            return;
        }

        if (!serviceAreasResponse.ok) {
            throw new Error('Unable to load service areas.');
        }

        const serviceAreasResult = await serviceAreasResponse.json();
        const serviceAreas = serviceAreasResult.data ?? [];
        const serviceAreaSelect = document.querySelector('#technicianServiceAreaSelect');

        serviceAreaSelect.innerHTML = `<option value="">No service area</option>${renderSelectOptions(serviceAreas, (serviceArea) => serviceArea.name ?? `Service Area #${serviceArea.id}`)}`;
    } catch (error) {
        document.querySelector('#technicianFormError').textContent = error.message;
    }

    try {
        const response = await fetch('/api/technicians', {
            headers: authHeaders(),
        });

        if (response.status === 401) {
            clearToken();
            renderLogin();
            return;
        }

        if (!response.ok) {
            throw new Error('Unable to load technicians.');
        }

        const result = await response.json();
        const technicians = result.data ?? [];
        const content = document.querySelector('#techniciansContent');

        if (technicians.length === 0) {
            content.className = 'empty-state';
            content.innerHTML = `
                <strong>No technicians found yet.</strong>
                <span>Create a technician profile and it will appear here.</span>
            `;
            return;
        }

        content.className = 'table';
        content.innerHTML = `
            <div class="table-row table-head technician-table-row">
                <span>Name</span>
                <span>Skill</span>
                <span>Availability</span>
                <span>Service Area</span>
            </div>
            ${technicians.map((technician) => {
                const name = technician.name ?? technician.user?.name ?? `Technician #${technician.id}`;
                const skill = technician.skill_category ?? technician.skill ?? '-';
                const availability = technician.availability_status ?? technician.status ?? 'available';
                const serviceArea = technician.service_area?.name ?? technician.serviceArea?.name ?? '-';

                return `
                    <div class="table-row technician-table-row">
                        <span>${escapeHtml(name)}</span>
                        <span>${escapeHtml(skill)}</span>
                        <span><b class="badge ${statusBadgeClass(availability)}">${escapeHtml(availability)}</b></span>
                        <span>${escapeHtml(serviceArea)}</span>
                    </div>
                `;
            }).join('')}
        `;
    } catch (error) {
        const content = document.querySelector('#techniciansContent');
        content.className = 'empty-state error-state';
        content.innerHTML = `
            <strong>Could not load technicians.</strong>
            <span>${escapeHtml(error.message)}</span>
        `;
    }
}
async function handleCreateCustomer(event, workspace) {
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
            headers: jsonAuthHeaders(),
            body: JSON.stringify(payload),
        });

        const result = await response.json();

        if (response.status === 401) {
            clearToken();
            renderLogin();
            return;
        }

        if (!response.ok) {
            const firstError = result.errors
                ? Object.values(result.errors).flat()[0]
                : result.message;

            throw new Error(firstError || 'Customer could not be created.');
        }

        await renderCustomersPage(workspace);
    } catch (errorMessage) {
        error.textContent = errorMessage.message;
    } finally {
        button.disabled = false;
        button.textContent = 'Save Customer';
    }
}

async function renderCustomersPage(workspace) {
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
        .addEventListener('click', () => renderCustomersPage(workspace));

    document
        .querySelector('#customerForm')
        .addEventListener('submit', (event) => handleCreateCustomer(event, workspace));

    try {
        const response = await fetch('/api/customers', {
            headers: authHeaders(),
        });

        if (response.status === 401) {
            clearToken();
            renderLogin();
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
                        <span>${escapeHtml(name)}</span>
                        <span>${escapeHtml(email)}</span>
                        <span>${escapeHtml(customer.city ?? '-')}</span>
                        <span><b class="badge ${statusBadgeClass(status)}">${escapeHtml(status)}</b></span>
                    </div>
                `;
            }).join('')}
        `;
    } catch (error) {
        const content = document.querySelector('#customersContent');
        content.className = 'empty-state error-state';
        content.innerHTML = `
            <strong>Could not load customers.</strong>
            <span>${escapeHtml(error.message)}</span>
        `;
    }
}
async function renderComplaintsPage(workspace) {
    workspace.innerHTML = `
        <header class="topbar">
            <div>
                <p class="eyebrow">Complaint management</p>
                <h1>Complaints</h1>
                <p class="user-line">Track customer issues, priority, SLA risk, and current status.</p>
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
                <h2>Complaint Queue</h2>
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
        .addEventListener('click', () => renderComplaintsPage(workspace));

    document
        .querySelector('#complaintForm')
        .addEventListener('submit', (event) => handleCreateComplaint(event, workspace));

    try {
        const { customers, serviceAreas } = await loadComplaintFormOptions();

        const customerSelect = document.querySelector('#customerSelect');
        const serviceAreaSelect = document.querySelector('#serviceAreaSelect');

        customerSelect.innerHTML = customers.length
            ? `<option value="">Select customer</option>${renderSelectOptions(customers, (customer) => customer.name ?? customer.user?.name ?? `Customer #${customer.id}`)}`
            : '<option value="">No customers available</option>';

        serviceAreaSelect.innerHTML = `<option value="">No service area</option>${renderSelectOptions(serviceAreas, (serviceArea) => serviceArea.name ?? `Service Area #${serviceArea.id}`)}`;
    } catch (error) {
        document.querySelector('#customerSelect').innerHTML = '<option value="">Could not load customers</option>';
        document.querySelector('#complaintFormError').textContent = error.message;
    }

    try {
        const response = await fetch('/api/complaints', {
            headers: authHeaders(),
        });

        if (response.status === 401) {
            clearToken();
            renderLogin();
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
        content.innerHTML = renderComplaintTable(complaints);
    } catch (error) {
        const content = document.querySelector('#complaintsContent');
        content.className = 'empty-state error-state';
        content.innerHTML = `
            <strong>Could not load complaints.</strong>
            <span>${escapeHtml(error.message)}</span>
        `;
    }
}
function reportValue(value, suffix = '') {
    return `${value ?? 0}${suffix}`;
}

async function renderReportsPage(workspace) {
    workspace.innerHTML = `
        <header class="topbar">
            <div>
                <p class="eyebrow">Analytics</p>
                <h1>Reports</h1>
                <p class="user-line">Review SLA performance, resolution trends, and operational workload.</p>
            </div>

            <button class="primary-button" id="refreshReportsButton">Refresh Reports</button>
        </header>

        <section id="reportsContent" class="empty-state">Loading reports...</section>
    `;

    document
        .querySelector('#refreshReportsButton')
        .addEventListener('click', () => renderReportsPage(workspace));

    try {
        const response = await fetch('/api/admin/dashboard', {
            headers: authHeaders(),
        });

        if (response.status === 401) {
            clearToken();
            renderLogin();
            return;
        }

        if (!response.ok) {
            throw new Error('Unable to load reports.');
        }

        const result = await response.json();
        const data = result.data ?? {};
        const complaints = data.complaints ?? {};
        const sla = data.sla ?? {};
        const technicians = data.technicians ?? {};
        const satisfaction = data.customer_satisfaction ?? {};
        const inventory = data.inventory ?? {};
        const content = document.querySelector('#reportsContent');

        content.className = '';
        content.innerHTML = `
            <section class="stats-grid">
                <article class="stat-card">
                    <span>Total Complaints</span>
                    <strong>${reportValue(complaints.total)}</strong>
                    <small>${reportValue(complaints.pending)} pending</small>
                </article>

                <article class="stat-card">
                    <span>Resolution Rate</span>
                    <strong>${reportValue(complaints.resolution_rate, '%')}</strong>
                    <small>${reportValue(complaints.resolved)} resolved</small>
                </article>

                <article class="stat-card">
                    <span>SLA Compliance</span>
                    <strong>${reportValue(sla.compliance_rate, '%')}</strong>
                    <small>${reportValue(sla.breached)} breached</small>
                </article>

                <article class="stat-card">
                    <span>Available Technicians</span>
                    <strong>${reportValue(technicians.available)}</strong>
                    <small>${reportValue(technicians.total)} total technicians</small>
                </article>
            </section>

            <section class="content-grid">
                <article class="panel">
                    <div class="panel-header">
                        <h2>Operations Summary</h2>
                    </div>

                    <div class="table">
                        <div class="table-row report-table-row">
                            <span>Open workload</span>
                            <strong>${reportValue(complaints.pending)} complaints</strong>
                        </div>
                        <div class="table-row report-table-row">
                            <span>Within SLA</span>
                            <strong>${reportValue(sla.within_sla)} tracked complaints</strong>
                        </div>
                        <div class="table-row report-table-row">
                            <span>Customer feedback</span>
                            <strong>${reportValue(satisfaction.average_rating)} average rating</strong>
                        </div>
                        <div class="table-row report-table-row">
                            <span>Low stock items</span>
                            <strong>${reportValue(inventory.low_stock_items)} items</strong>
                        </div>
                    </div>
                </article>

                <article class="panel">
                    <div class="panel-header">
                        <h2>Technician Status</h2>
                    </div>

                    <div class="workload-list">
                        <div>
                            <span>Available</span>
                            <strong>${reportValue(technicians.available)}</strong>
                        </div>
                        <div>
                            <span>Busy</span>
                            <strong>${reportValue(technicians.busy)}</strong>
                        </div>
                        <div>
                            <span>Total</span>
                            <strong>${reportValue(technicians.total)}</strong>
                        </div>
                    </div>
                </article>
            </section>
        `;
    } catch (error) {
        const content = document.querySelector('#reportsContent');
        content.className = 'empty-state error-state';
        content.innerHTML = `
            <strong>Could not load reports.</strong>
            <span>${escapeHtml(error.message)}</span>
        `;
    }
}
function renderPage(page) {
    const workspace = document.querySelector('.workspace');

    if (page === 'dashboard') {
        renderDashboardPage(workspace);
        return;
    }

    if (page === 'complaints') {
        renderComplaintsPage(workspace);
        return;
    }

    if (page === 'customers') {
        renderCustomersPage(workspace);
        return;
    }
    if (page === 'technicians') {
        renderTechniciansPage(workspace);
        return;
    }
    if (page === 'reports') {
        renderReportsPage(workspace);
    }}
async function startApp() {
    if (!getToken()) {
        renderLogin();
        return;
    }

    const user = await fetchCurrentUser();

    if (user) {
        renderDashboard(user);
    }
}

startApp();
