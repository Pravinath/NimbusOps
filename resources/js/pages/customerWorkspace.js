const N = window.NimbusOps;

N.availabilityOptions = [
    ['available', 'Available'],
    ['busy', 'Busy'],
    ['offline', 'Offline'],
    ['on_leave', 'On leave'],
];

N.renderCustomerHome = async function renderCustomerHome(workspace) {
    workspace.innerHTML = `<section class="empty-state">Loading your service overview...</section>`;

    try {
        const [complaintsResult, notificationsResult] = await Promise.all([
            N.fetchApi('/api/complaints'),
            N.fetchApi('/api/notifications'),
        ]);
        const complaints = complaintsResult.data ?? [];
        const unread = notificationsResult.unread_count ?? 0;
        const open = complaints.filter((item) => !['resolved', 'closed', 'cancelled'].includes(item.status)).length;
        const resolved = N.countByStatus(complaints, ['resolved', 'closed']);
        const recent = complaints.slice(0, 4);

        workspace.innerHTML = `
            <header class="topbar"><div><p class="eyebrow">Customer service portal</p><h1>Welcome, ${N.escapeHtml(N.currentUser.name)}</h1><p class="user-line">Track requests and stay informed throughout every service visit.</p></div><button class="primary-button" id="customerNewRequest">New Service Request</button></header>
            <section class="stats-grid customer-stats"><article class="stat-card"><span>Open requests</span><strong>${open}</strong><small>Currently receiving attention</small></article><article class="stat-card"><span>Resolved</span><strong>${resolved}</strong><small>Completed service requests</small></article><article class="stat-card"><span>Notifications</span><strong>${unread}</strong><small>Unread updates</small></article><article class="stat-card"><span>Total history</span><strong>${complaints.length}</strong><small>Requests in your account</small></article></section>
            <section class="content-grid"><article class="panel"><div class="panel-header"><h2>Recent Service Requests</h2><button id="viewCustomerRequests">View all</button></div>${recent.length ? `<div class="table">${N.renderComplaintTable(recent)}</div>` : '<div class="empty-state"><strong>No requests yet.</strong><span>Create your first service request when you need support.</span></div>'}</article><article class="panel customer-help"><p class="eyebrow">Need assistance?</p><h2>We keep every update in one place.</h2><p>Your request history, status changes, and service notifications remain connected to your account.</p></article></section>
        `;

        document.querySelector('#customerNewRequest').addEventListener('click', () => N.renderComplaintsPage(workspace, true));
        document.querySelector('#viewCustomerRequests').addEventListener('click', () => N.activatePage('complaints'));
    } catch (error) {
        workspace.innerHTML = `<section class="empty-state error-state"><strong>Could not load your overview.</strong><span>${N.escapeHtml(error.message)}</span></section>`;
    }
}

N.activatePage = function activatePage(page) {
    const button = document.querySelector(`.nav-item[data-page="${page}"]`);
    if (button) {
        document.querySelectorAll('.nav-item').forEach((item) => item.classList.remove('active'));
        button.classList.add('active');
    }
    N.renderPage(page);
}

N.workOrderCustomerName = function workOrderCustomerName(workOrder) {
    return workOrder.complaint?.customer?.user?.name ?? 'Customer';
}

N.renderWorkOrderRows = function renderWorkOrderRows(workOrders) {
    return `<div class="table-row table-head work-order-row"><span>Work order</span><span>Customer</span><span>Status</span><span>Priority</span></div>${workOrders.map((order) => `<div class="table-row work-order-row"><span>#WO-${N.escapeHtml(order.id)}</span><span>${N.escapeHtml(N.workOrderCustomerName(order))}</span><span><b class="badge ${N.statusBadgeClass(order.status)}">${N.escapeHtml(order.status)}</b></span><span>${N.escapeHtml(order.complaint?.priority ?? '-')}</span></div>`).join('')}`;
}

N.renderTechnicianHome = async function renderTechnicianHome(workspace) {
    workspace.innerHTML = `<section class="empty-state">Loading your field workspace...</section>`;

    try {
        const [ordersResult, notificationsResult] = await Promise.all([
            N.fetchApi('/api/work-orders'),
            N.fetchApi('/api/notifications'),
        ]);
        const orders = ordersResult.data ?? [];
        const active = orders.filter((item) => !['completed', 'cancelled'].includes(item.status)).length;
        const completed = N.countByStatus(orders, ['completed']);
        const profile = N.currentUser.technician ?? {};

        const availability = profile.availability_status ?? 'available';
        const availabilityOptions = N.availabilityOptions
            .map(([value, label]) => `<option value="${value}" ${availability === value ? 'selected' : ''}>${label}</option>`)
            .join('');

        workspace.innerHTML = `
            <header class="topbar"><div><p class="eyebrow">Field service workspace</p><h1>Good day, ${N.escapeHtml(N.currentUser.name)}</h1><p class="user-line">Review assignments and keep customers informed from dispatch to completion.</p></div><span class="availability-pill ${N.statusBadgeClass(availability)}">${N.escapeHtml(N.roleLabel(availability))}</span></header>
            <section class="stats-grid technician-stats"><article class="stat-card"><span>Active assignments</span><strong>${active}</strong><small>Jobs requiring attention</small></article><article class="stat-card"><span>Completed</span><strong>${completed}</strong><small>Finished work orders</small></article><article class="stat-card"><span>Skill area</span><strong class="stat-text">${N.escapeHtml(profile.skill_category ?? 'Not assigned')}</strong><small>Your current specialization</small></article><article class="stat-card"><span>Unread updates</span><strong>${notificationsResult.unread_count ?? 0}</strong><small>Operational notifications</small></article></section>
            <section class="technician-control-grid">
                <article class="panel availability-panel">
                    <div>
                        <p class="eyebrow">Dispatch availability</p>
                        <h2>Update your availability</h2>
                        <p>Keep dispatch informed before new jobs are assigned.</p>
                    </div>
                    <form class="availability-form" id="technicianAvailabilityForm">
                        <label><span>Status</span><select name="availability_status">${availabilityOptions}</select></label>
                        <button class="primary-button" type="submit" ${profile.id ? '' : 'disabled'}>Save Availability</button>
                        <p class="form-error availability-error" id="availabilityError"></p>
                    </form>
                </article>
                <section class="panel"><div class="panel-header"><h2>Current Assignments</h2><button id="viewAllWorkOrders">View all</button></div>${orders.length ? `<div class="table">${N.renderWorkOrderRows(orders.slice(0, 5))}</div>` : '<div class="empty-state"><strong>No work assigned.</strong><span>New assignments will appear here automatically.</span></div>'}</section>
            </section>
        `;
        document.querySelector('#viewAllWorkOrders').addEventListener('click', () => N.activatePage('work-orders'));
        document.querySelector('#technicianAvailabilityForm')?.addEventListener('submit', (event) => N.handleTechnicianAvailabilityUpdate(event, workspace, profile.id));
    } catch (error) {
        workspace.innerHTML = `<section class="empty-state error-state"><strong>Could not load field assignments.</strong><span>${N.escapeHtml(error.message)}</span></section>`;
    }
}

N.renderWorkOrdersPage = async function renderWorkOrdersPage(workspace) {
    workspace.innerHTML = `<header class="topbar"><div><p class="eyebrow">Assigned field work</p><h1>Work Orders</h1><p class="user-line">Your authorized service assignments and current progress.</p></div><button class="secondary-button" id="refreshWorkOrders">Refresh</button></header><section class="panel"><div id="workOrdersContent" class="empty-state">Loading work orders...</div></section>`;
    document.querySelector('#refreshWorkOrders').addEventListener('click', () => N.renderWorkOrdersPage(workspace));
    try {
        const result = await N.fetchApi('/api/work-orders');
        const orders = result.data ?? [];
        const content = document.querySelector('#workOrdersContent');
        content.className = orders.length ? 'table' : 'empty-state';
        content.innerHTML = orders.length ? N.renderWorkOrderRows(orders) : '<strong>No assigned work orders.</strong><span>Your dispatcher has not assigned any work yet.</span>';
    } catch (error) {
        document.querySelector('#workOrdersContent').innerHTML = `<strong>Could not load work orders.</strong><span>${N.escapeHtml(error.message)}</span>`;
    }
}

N.renderNotificationsPage = async function renderNotificationsPage(workspace) {
    workspace.innerHTML = `<header class="topbar"><div><p class="eyebrow">Account activity</p><h1>Notifications</h1><p class="user-line">Important service and operational updates for your account.</p></div><button class="secondary-button" id="refreshNotifications">Refresh</button></header><section class="panel"><div id="notificationsContent" class="empty-state">Loading notifications...</div></section>`;
    document.querySelector('#refreshNotifications').addEventListener('click', () => N.renderNotificationsPage(workspace));
    try {
        const result = await N.fetchApi('/api/notifications');
        const notifications = result.data?.data ?? result.data ?? [];
        const content = document.querySelector('#notificationsContent');
        content.className = notifications.length ? 'notification-list' : 'empty-state';
        content.innerHTML = notifications.length ? notifications.map((notification) => `<article class="notification-item ${notification.read_at ? '' : 'unread'}"><span></span><div><strong>${N.escapeHtml(notification.data?.title ?? notification.data?.message ?? 'NimbusOps update')}</strong><p>${N.escapeHtml(notification.data?.message ?? 'Your service record has been updated.')}</p><small>${N.escapeHtml(new Date(notification.created_at).toLocaleString())}</small></div></article>`).join('') : '<strong>You are all caught up.</strong><span>New updates will appear here.</span>';
    } catch (error) {
        document.querySelector('#notificationsContent').innerHTML = `<strong>Could not load notifications.</strong><span>${N.escapeHtml(error.message)}</span>`;
    }
}

N.renderInventoryHome = async function renderInventoryHome(workspace) {
    workspace.innerHTML = `<header class="topbar"><div><p class="eyebrow">Parts and stock control</p><h1>Inventory Workspace</h1><p class="user-line">Monitor availability and low-stock operational risk.</p></div></header><section id="inventorySummary" class="empty-state">Loading inventory...</section>`;
    try {
        const [partsResult, lowResult] = await Promise.all([N.fetchApi('/api/spare-parts'), N.fetchApi('/api/inventory/low-stock')]);
        const parts = partsResult.data ?? [];
        const low = lowResult.data ?? [];
        document.querySelector('#inventorySummary').className = 'stats-grid';
        document.querySelector('#inventorySummary').innerHTML = `<article class="stat-card"><span>Catalog items</span><strong>${parts.length}</strong><small>Tracked spare parts</small></article><article class="stat-card"><span>Low stock</span><strong>${low.length}</strong><small>Items needing attention</small></article>`;
    } catch (error) {
        document.querySelector('#inventorySummary').innerHTML = `<strong>Could not load inventory.</strong><span>${N.escapeHtml(error.message)}</span>`;
    }
}

N.handleTechnicianAvailabilityUpdate = async function handleTechnicianAvailabilityUpdate(event, workspace, technicianId) {
    event.preventDefault();
    const form = event.target;
    const error = document.querySelector('#availabilityError');
    const button = form.querySelector('button[type="submit"]');
    const select = form.querySelector('select[name="availability_status"]');

    if (!technicianId) {
        error.textContent = 'Your technician profile is not ready yet.';
        return;
    }

    error.textContent = '';
    button.disabled = true;
    button.textContent = 'Saving...';

    try {
        const response = await fetch(`/api/technicians/${technicianId}/availability`, {
            method: 'PATCH',
            headers: N.jsonAuthHeaders(),
            body: JSON.stringify({ availability_status: select.value }),
        });
        const result = await response.json();
        if (!response.ok) throw new Error(N.registrationErrors(result));

        N.currentUser.technician = result.data;
        await N.renderTechnicianHome(workspace);
    } catch (updateError) {
        error.textContent = updateError.message;
        button.disabled = false;
        button.textContent = 'Save Availability';
    }
}