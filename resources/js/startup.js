const N = window.NimbusOps;

N.handleTechnicianOAuthReturn = async function handleTechnicianOAuthReturn() {
    const url = new URL(window.location.href);
    const code = url.searchParams.get('technician_oauth_code');
    const error = url.searchParams.get('technician_error');

    if (!code && !error) return false;
    window.history.replaceState({}, document.title, '/');

    if (error) {
        N.renderTechnicianPortal(error);
        return true;
    }

    N.app.innerHTML = '<main class="auth-page"><section class="auth-panel"><h1>Completing Google sign-in</h1><p>Preparing your secure technician application workspace...</p></section></main>';
    try {
        const response = await fetch('/api/auth/technician/google/exchange', { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ code }) });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message);
        N.saveToken(result.token);
        N.currentUser = result.user;
        N.renderDashboard(result.user);
    } catch (oauthError) {
        N.renderTechnicianPortal(oauthError.message);
    }
    return true;
}

N.renderPage = function renderPage(page) {
    const workspace = document.querySelector('.workspace');

    if (page === 'dashboard') {
        N.renderDashboardPage(workspace);
        return;
    }

    if (page === 'complaints') {
        N.renderComplaintsPage(workspace);
        return;
    }

    if (page === 'customers') {
        N.renderCustomersPage(workspace);
        return;
    }
    if (page === 'technicians') {
        N.renderTechniciansPage(workspace);
        return;
    }
    if (page === 'reports') {
        N.renderReportsPage(workspace);
        return;
    }
    if (page === 'admin-applications') {
        N.renderAdminApplicationsPage(workspace);
        return;
    }
    if (page === 'customer-home') {
        N.renderCustomerHome(workspace);
        return;
    }
    if (page === 'technician-home') {
        N.renderTechnicianHome(workspace);
        return;
    }
    if (page === 'work-orders') {
        N.renderWorkOrdersPage(workspace);
        return;
    }
    if (page === 'notifications') {
        N.renderNotificationsPage(workspace);
        return;
    }
    if (page === 'inventory-home') {
        N.renderInventoryHome(workspace);
        return;
    }
    if (page === 'application-home') {
        N.renderApplicantHome(workspace);
    }
}
N.startApp = async function startApp() {
    if (await N.handleTechnicianOAuthReturn()) {
        return;
    }
    if (!N.getToken()) {
        N.renderLanding();
        return;
    }

    const user = await N.fetchCurrentUser();

    if (user) {
        N.renderDashboard(user);
    }
}

N.startApp();
