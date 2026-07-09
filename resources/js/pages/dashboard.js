const N = window.NimbusOps;

N.renderDashboard = function renderDashboard(user) {
    N.currentUser = user;
    const navigation = N.roleNavigation[user.role] ?? N.roleNavigation.customer;
    const defaultPage = navigation[0][0];

    N.app.innerHTML = `
        <main class="layout">
            <aside class="sidebar">
                <div class="brand"><span class="brand-mark">N</span><div><strong>NimbusOps</strong><small>${N.escapeHtml(N.roleLabel(user.role))} Workspace</small></div></div>
                <nav class="nav">${navigation.map(([page, label], index) => `<button class="nav-item ${index === 0 ? 'active' : ''}" data-page="${page}">${label}</button>`).join('')}</nav>
                <div class="sidebar-profile"><span>${N.escapeHtml(user.name?.charAt(0) ?? 'U')}</span><div><strong>${N.escapeHtml(user.name)}</strong><small>${N.escapeHtml(N.roleLabel(user.role))}</small></div></div>
                <button class="sidebar-logout" id="logoutButton">Sign out</button>
            </aside>
            <section class="workspace"></section>
        </main>
    `;

    document.querySelector('#logoutButton').addEventListener('click', N.logout);
    document.querySelectorAll('.nav-item').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.nav-item').forEach((item) => item.classList.remove('active'));
            button.classList.add('active');
            N.renderPage(button.dataset.page);
        });
    });

    N.renderPage(defaultPage);
}

N.renderDashboardPage = async function renderDashboardPage(workspace) {
    workspace.innerHTML = `
        <header class="topbar">
            <div>
                <p class="eyebrow">Operations overview</p>
                <h1>Dashboard</h1>
                <p class="user-line">Signed in as ${N.escapeHtml(N.currentUser.name)} (${N.escapeHtml(N.currentUser.role)})</p>
            </div>

            <div class="topbar-actions">
                <button class="primary-button" id="dashboardNewComplaintButton">New Complaint</button>
                <button class="secondary-button" id="dashboardRefreshButton">Refresh</button>
            </div>
        </header>

        <div id="dashboardContent" class="empty-state">Loading live operations data...</div>
    `;

    document.querySelector('#dashboardNewComplaintButton').addEventListener('click', () => {
        const complaintsButton = document.querySelector('.nav-item[data-page="complaints"]');
        complaintsButton?.click();
    });

    document.querySelector('#dashboardRefreshButton').addEventListener('click', () => N.renderDashboardPage(workspace));

    try {
        const [summary, complaintsResult, techniciansResult] = await Promise.all([
            N.fetchApi('/api/admin/dashboard'),
            N.fetchApi('/api/complaints'),
            N.fetchApi('/api/reports/technician-performance'),
        ]);

        const data = summary.data ?? {};
        const complaints = data.complaints ?? {};
        const sla = data.sla ?? {};
        const technicians = data.technicians ?? {};
        const recentComplaints = (complaintsResult.data ?? []).slice(0, 5);
        const technicianRows = (techniciansResult.data ?? []).slice(0, 5);

        workspace.querySelector('#dashboardContent').outerHTML = `
            <section class="stats-grid">
                <article class="stat-card">
                    <span>Open Complaints</span>
                    <strong>${N.reportValue(complaints.pending)}</strong>
                    <small>${N.reportValue(complaints.total)} total complaints</small>
                </article>

                <article class="stat-card">
                    <span>Available Technicians</span>
                    <strong>${N.reportValue(technicians.available)}</strong>
                    <small>${N.reportValue(technicians.total)} total technicians</small>
                </article>

                <article class="stat-card">
                    <span>SLA Risk</span>
                    <strong>${N.reportValue(sla.breached)}</strong>
                    <small>${N.reportValue(sla.within_sla)} within SLA</small>
                </article>

                <article class="stat-card">
                    <span>Resolved</span>
                    <strong>${N.reportValue(complaints.resolved)}</strong>
                    <small>${N.reportValue(complaints.resolution_rate, '%')} resolution rate</small>
                </article>
            </section>

            <section class="content-grid">
                <article class="panel">
                    <div class="panel-header">
                        <h2>Recent Complaints</h2>
                        <button id="dashboardViewComplaintsButton">View all</button>
                    </div>

                    ${recentComplaints.length
                        ? `<div class="table">${N.renderComplaintTable(recentComplaints)}</div>`
                        : '<div class="empty-state"><strong>No complaints found yet.</strong><span>New customer requests will appear here.</span></div>'}
                </article>

                <article class="panel">
                    <div class="panel-header">
                        <h2>Technician Load</h2>
                    </div>

                    ${technicianRows.length
                        ? `<div class="workload-list">${technicianRows.map((technician) => `
                            <div>
                                <span>${N.escapeHtml(technician.name ?? `Technician #${technician.technician_id}`)}</span>
                                <strong>${N.escapeHtml(technician.availability_status ?? 'available')}</strong>
                            </div>
                        `).join('')}</div>`
                        : '<div class="empty-state"><strong>No technicians found yet.</strong><span>Approved technicians will appear here.</span></div>'}
                </article>
            </section>
        `;

        document.querySelector('#dashboardViewComplaintsButton')?.addEventListener('click', () => {
            document.querySelector('.nav-item[data-page="complaints"]')?.click();
        });
    } catch (error) {
        const content = workspace.querySelector('#dashboardContent');
        content.className = 'empty-state error-state';
        content.innerHTML = `
            <strong>Could not load dashboard data.</strong>
            <span>${N.escapeHtml(error.message)}</span>
        `;
    }
}