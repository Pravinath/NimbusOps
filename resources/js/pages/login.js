const N = window.NimbusOps;

N.renderLogin = function renderLogin() {
    N.app.innerHTML = `
        <main class="auth-page">
            <section class="auth-panel">
                <div class="auth-brand">
                    <span class="brand-mark">N</span>
                    <div>
                        <strong>NimbusOps</strong>
                        <small>Service Desk Dashboard</small>
                    </div>
                </div>

                <button class="back-link" id="backToSite" type="button">&larr; Back to website</button>

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
                            autocomplete="username"
                            placeholder="you@company.com"
                            required
                        >
                    </label>

                    <label>
                        Password
                        <input
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            required
                        >
                    </label>

                    <p class="form-error" id="formError"></p>

                    <button class="primary-button" type="submit">Login</button>
                    <div class="auth-divider"><span>New to NimbusOps?</span></div>
                    <button class="secondary-button create-account-button" id="openSignup" type="button">Create customer account</button>
                    <p class="staff-signin-note">Staff and technicians sign in using company-issued access.</p>
                </form>
            </section>
        </main>
    `;

    document.querySelector('#loginForm').addEventListener('submit', N.handleLogin);
    document.querySelector('#backToSite').addEventListener('click', N.renderLanding);
    document.querySelector('#openSignup').addEventListener('click', N.renderSignup);
}

N.handleLogin = async function handleLogin(event) {
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

        N.saveToken(data.token);
        N.currentUser = data.user;
        N.renderDashboard(data.user);
    } catch (errorMessage) {
        error.textContent = errorMessage.message;
    } finally {
        button.disabled = false;
        button.textContent = 'Login';
    }
}

N.fetchCurrentUser = async function fetchCurrentUser() {
    const response = await fetch('/api/me', {
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${N.getToken()}`,
        },
    });

    if (!response.ok) {
        N.clearToken();
        N.renderLogin();
        return null;
    }

    const data = await response.json();

    return data.user;
}

N.logout = async function logout() {
    await fetch('/api/auth/logout', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${N.getToken()}`,
        },
    });

    N.clearToken();
    N.renderLanding();
}

N.roleNavigation = {
    customer: [
        ['customer-home', 'Overview'],
        ['complaints', 'My Requests'],
        ['notifications', 'Notifications'],
    ],
    technician: [
        ['technician-home', 'Overview'],
        ['work-orders', 'Work Orders'],
        ['notifications', 'Notifications'],
    ],
    technician_applicant: [
        ['application-home', 'My Application'],
    ],
    agent: [
        ['dashboard', 'Dashboard'],
        ['complaints', 'Complaints'],
        ['customers', 'Customers'],
        ['notifications', 'Notifications'],
    ],
    dispatcher: [
        ['dashboard', 'Dashboard'],
        ['complaints', 'Dispatch Queue'],
        ['technicians', 'Technicians'],
        ['notifications', 'Notifications'],
    ],
    inventory: [
        ['inventory-home', 'Inventory'],
        ['notifications', 'Notifications'],
    ],
    supervisor: [
        ['dashboard', 'Dashboard'],
        ['complaints', 'Complaints'],
        ['customers', 'Customers'],
        ['technicians', 'Technicians'],
        ['reports', 'Reports'],
        ['notifications', 'Notifications'],
    ],
    admin: [
        ['dashboard', 'Dashboard'],
        ['complaints', 'Complaints'],
        ['customers', 'Customers'],
        ['technicians', 'Technicians'],
        ['admin-applications', 'Applications'],
        ['reports', 'Reports'],
        ['notifications', 'Notifications'],
    ],
};

N.roleLabel = function roleLabel(role) {
    return String(role ?? 'user').replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}
