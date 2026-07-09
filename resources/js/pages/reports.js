const N = window.NimbusOps;

N.reportValue = function reportValue(value, suffix = '') {
    return `${value ?? 0}${suffix}`;
}

N.renderReportsPage = async function renderReportsPage(workspace) {
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
        .addEventListener('click', () => N.renderReportsPage(workspace));

    try {
        const response = await fetch('/api/admin/dashboard', {
            headers: N.authHeaders(),
        });

        if (response.status === 401) {
            N.clearToken();
            N.renderLogin();
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
                    <strong>${N.reportValue(complaints.total)}</strong>
                    <small>${N.reportValue(complaints.pending)} pending</small>
                </article>

                <article class="stat-card">
                    <span>Resolution Rate</span>
                    <strong>${N.reportValue(complaints.resolution_rate, '%')}</strong>
                    <small>${N.reportValue(complaints.resolved)} resolved</small>
                </article>

                <article class="stat-card">
                    <span>SLA Compliance</span>
                    <strong>${N.reportValue(sla.compliance_rate, '%')}</strong>
                    <small>${N.reportValue(sla.breached)} breached</small>
                </article>

                <article class="stat-card">
                    <span>Available Technicians</span>
                    <strong>${N.reportValue(technicians.available)}</strong>
                    <small>${N.reportValue(technicians.total)} total technicians</small>
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
                            <strong>${N.reportValue(complaints.pending)} complaints</strong>
                        </div>
                        <div class="table-row report-table-row">
                            <span>Within SLA</span>
                            <strong>${N.reportValue(sla.within_sla)} tracked complaints</strong>
                        </div>
                        <div class="table-row report-table-row">
                            <span>Customer feedback</span>
                            <strong>${N.reportValue(satisfaction.average_rating)} average rating</strong>
                        </div>
                        <div class="table-row report-table-row">
                            <span>Low stock items</span>
                            <strong>${N.reportValue(inventory.low_stock_items)} items</strong>
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
                            <strong>${N.reportValue(technicians.available)}</strong>
                        </div>
                        <div>
                            <span>Busy</span>
                            <strong>${N.reportValue(technicians.busy)}</strong>
                        </div>
                        <div>
                            <span>Total</span>
                            <strong>${N.reportValue(technicians.total)}</strong>
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
            <span>${N.escapeHtml(error.message)}</span>
        `;
    }
}

N.countByStatus = function countByStatus(items, statuses) {
    return items.filter((item) => statuses.includes(item.status)).length;
}
