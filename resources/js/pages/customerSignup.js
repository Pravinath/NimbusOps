const N = window.NimbusOps;

N.renderSignup = function renderSignup() {
    N.app.innerHTML = `
        <main class="signup-page">
            <section class="signup-story">
                <button class="back-link light" id="signupBackToSite" type="button">&larr; Back to website</button>
                <div>
                    <p class="eyebrow">NimbusOps customer access</p>
                    <h1>Service support that stays visible from request to resolution.</h1>
                    <p>Create your secure customer workspace to report issues, follow progress, receive updates, and review completed service.</p>
                </div>
                <div class="signup-benefits">
                    <article><span>01</span><div><strong>Submit with context</strong><small>Share the issue, location, priority, and preferred visit time.</small></div></article>
                    <article><span>02</span><div><strong>Track every movement</strong><small>Follow status changes and technician progress in one timeline.</small></div></article>
                    <article><span>03</span><div><strong>Close the feedback loop</strong><small>Confirm the outcome and rate your service experience.</small></div></article>
                </div>
                <p class="staff-note"><strong>Joining as a technician?</strong> Use the separate Technician Partner Portal to apply with Google or email.</p>
            </section>

            <section class="signup-form-panel">
                <div class="auth-brand">
                    <span class="brand-mark">N</span>
                    <div><strong>NimbusOps</strong><small>Customer workspace</small></div>
                </div>

                <form class="signup-form" id="signupForm">
                    <header><p class="eyebrow">Create account</p><h2>Start your customer workspace</h2><span>Already registered? <button class="inline-action" id="signupToLogin" type="button">Sign in</button></span></header>

                    <div class="signup-grid">
                        <label class="full-field">Full name<input type="text" name="name" autocomplete="name" placeholder="Your full name" required maxlength="120"></label>
                        <label>Email address<input type="email" name="email" autocomplete="email" placeholder="you@company.com" required></label>
                        <label>Phone number<input type="tel" name="phone" autocomplete="tel" placeholder="077 123 4567" required maxlength="30"></label>
                        <label>City<input type="text" name="city" autocomplete="address-level2" placeholder="Colombo" required maxlength="120"></label>
                        <label class="full-field">Service address<input type="text" name="address" autocomplete="street-address" placeholder="Building number and street" required maxlength="255"></label>
                        <label>Password<input type="password" name="password" autocomplete="new-password" placeholder="Minimum 8 characters" required minlength="8"></label>
                        <label>Confirm password<input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Repeat password" required minlength="8"></label>
                    </div>

                    <label class="consent-row"><input type="checkbox" name="terms" required><span>I agree to responsible use of the NimbusOps service portal.</span></label>
                    <p class="form-error" id="signupError" role="alert"></p>
                    <button class="primary-button signup-submit" type="submit">Create customer account</button>
                    <p class="secure-note">Customer registration cannot create staff or administrative access.</p>
                </form>
            </section>
        </main>
    `;

    document.querySelector('#signupForm').addEventListener('submit', N.handleSignup);
    document.querySelector('#signupBackToSite').addEventListener('click', N.renderLanding);
    document.querySelector('#signupToLogin').addEventListener('click', N.renderLogin);
}

N.handleSignup = async function handleSignup(event) {
    event.preventDefault();

    const form = event.target;
    const error = document.querySelector('#signupError');
    const button = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);

    error.textContent = '';

    if (formData.get('password') !== formData.get('password_confirmation')) {
        error.textContent = 'Your password confirmation does not match.';
        return;
    }

    button.disabled = true;
    button.textContent = 'Creating secure workspace...';

    try {
        const response = await fetch('/api/auth/register', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData.entries())),
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(N.registrationErrors(data));
        }

        N.saveToken(data.token);
        N.currentUser = data.user;
        N.renderDashboard(data.user);
    } catch (signupError) {
        error.textContent = signupError.message;
    } finally {
        button.disabled = false;
        button.textContent = 'Create customer account';
    }
}
