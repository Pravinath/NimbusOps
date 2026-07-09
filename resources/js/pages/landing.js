const N = window.NimbusOps;

N.renderLanding = function renderLanding() {
    N.app.innerHTML = `
        <main class="public-site">
            <header class="public-header">
                <a class="public-brand" href="#" aria-label="NimbusOps home">
                    <span class="brand-mark">N</span>
                    <span><strong>NimbusOps</strong><small>Service Operations</small></span>
                </a>
                <nav class="public-nav" aria-label="Main navigation">
                    <a href="#platform">Platform</a>
                    <a href="#capabilities">Capabilities</a>
                    <a href="#roles">Teams</a>
                    <a href="#security">Security</a>
                </nav>
                <div class="public-header-actions"><button class="partner-button" id="openTechnicianPortal">Become a Technician</button><button class="secondary-button" id="openLogin">Sign in</button></div>
            </header>

            <section class="hero-section">
                <div class="hero-copy">
                    <p class="eyebrow">Connected field service management</p>
                    <h1>Turn every service request into a resolved customer outcome.</h1>
                    <p class="hero-lead">NimbusOps brings intake, intelligent triage, dispatch, field work, SLA control, inventory, and reporting into one accountable operations platform.</p>
                    <div class="hero-actions">
                        <button class="primary-button" id="heroLogin">Open workspace</button>
                        <a class="text-link" href="#platform">Explore the platform <span aria-hidden="true">&rarr;</span></a>
                        <button class="text-button" id="heroTechnicianPortal">Join as a technician</button>
                    </div>
                    <dl class="proof-strip">
                        <div><dt>One system</dt><dd>From complaint to closure</dd></div>
                        <div><dt>Role aware</dt><dd>Purpose-built workspaces</dd></div>
                        <div><dt>Audit ready</dt><dd>Every action traceable</dd></div>
                    </dl>
                </div>
                <div class="hero-product" aria-label="NimbusOps operations preview">
                    <div class="product-bar"><span>Operations command center</span><span class="live-label">Live</span></div>
                    <div class="product-metrics">
                        <article><small>Open cases</small><strong>24</strong><span>Across 6 service areas</span></article>
                        <article><small>Within SLA</small><strong>94%</strong><span>Up 8% this month</span></article>
                    </div>
                    <div class="dispatch-preview">
                        <div class="dispatch-title"><strong>Priority dispatch</strong><span>Today</span></div>
                        <div class="dispatch-row"><span class="priority-dot critical"></span><div><strong>Network outage</strong><small>Colombo Central</small></div><b>Assigned</b></div>
                        <div class="dispatch-row"><span class="priority-dot"></span><div><strong>Cooling system fault</strong><small>North District</small></div><b>En route</b></div>
                        <div class="dispatch-row"><span class="priority-dot normal"></span><div><strong>Preventive inspection</strong><small>Harbour Zone</small></div><b>Scheduled</b></div>
                    </div>
                </div>
            </section>

            <section class="trust-band" aria-label="Platform coverage">
                <span>Built for coordinated service teams</span>
                <strong>Customer Care</strong><strong>Dispatch</strong><strong>Field Service</strong><strong>Inventory</strong><strong>Leadership</strong>
            </section>

            <section class="public-section platform-section" id="platform">
                <div class="section-heading">
                    <p class="eyebrow">One operational record</p>
                    <h2>Clarity from first report to final proof.</h2>
                    <p>Replace fragmented calls, spreadsheets, and status messages with a workflow that keeps every team aligned.</p>
                </div>
                <div class="workflow-grid">
                    <article><span>01</span><h3>Capture</h3><p>Register customer issues with service area, urgency, preferred visit time, and complete context.</p></article>
                    <article><span>02</span><h3>Prioritize</h3><p>Classify work, apply SLA policies, and surface operational risk before deadlines are missed.</p></article>
                    <article><span>03</span><h3>Dispatch</h3><p>Match available technicians by skill, area, and workload with accountable assignments.</p></article>
                    <article><span>04</span><h3>Resolve</h3><p>Track progress, parts, customer updates, completion evidence, and service feedback.</p></article>
                </div>
            </section>

            <section class="public-section capabilities-section" id="capabilities">
                <div class="section-heading compact">
                    <p class="eyebrow">Operational intelligence</p>
                    <h2>Everything teams need to act with confidence.</h2>
                </div>
                <div class="capability-grid">
                    <article><span class="feature-icon">AI</span><h3>Intelligent classification</h3><p>Consistent category and priority recommendations for incoming complaints.</p></article>
                    <article><span class="feature-icon">SLA</span><h3>Deadline control</h3><p>Policy-driven targets, breach detection, escalation, and performance reporting.</p></article>
                    <article><span class="feature-icon">QR</span><h3>Trackable field work</h3><p>Secure service references designed for QR-enabled progress and verification.</p></article>
                    <article><span class="feature-icon">360</span><h3>Complete audit history</h3><p>A reliable timeline of status, assignment, inventory, and user activity.</p></article>
                    <article><span class="feature-icon">OPS</span><h3>Inventory connection</h3><p>Track parts usage, stock movement, and low-stock risk alongside service work.</p></article>
                    <article><span class="feature-icon">BI</span><h3>Leadership reporting</h3><p>Monitor workload, resolution, SLA compliance, coverage, and customer outcomes.</p></article>
                </div>
            </section>

            <section class="public-section roles-section" id="roles">
                <div class="section-heading compact">
                    <p class="eyebrow">Designed around responsibility</p>
                    <h2>The right view for every role.</h2>
                </div>
                <div class="role-list">
                    <span>Administrators</span><span>Service agents</span><span>Dispatchers</span><span>Technicians</span><span>Customers</span><span>Supervisors</span><span>Inventory teams</span>
                </div>
            </section>

            <section class="security-section" id="security">
                <div><p class="eyebrow">Control and accountability</p><h2>Built for responsible operations.</h2></div>
                <div class="security-points"><p><strong>Role-based access</strong><span>Users see and perform only the work appropriate to their responsibilities.</span></p><p><strong>Token-protected APIs</strong><span>Authenticated workflows and protected operational information.</span></p><p><strong>Traceable activity</strong><span>Audit records support review, governance, and continuous improvement.</span></p></div>
            </section>

            <section class="public-cta">
                <div><p class="eyebrow">NimbusOps workspace</p><h2>Ready to run service operations as one team?</h2></div>
                <button class="primary-button" id="ctaLogin">Sign in to NimbusOps</button>
            </section>

            <footer class="public-footer"><div class="public-brand"><span class="brand-mark">N</span><span><strong>NimbusOps</strong><small>Service Operations</small></span></div><p>Connected operations. Accountable service.</p><span>&copy; ${new Date().getFullYear()} NimbusOps</span></footer>
        </main>
    `;

    ['#openLogin', '#heroLogin', '#ctaLogin'].forEach((selector) => {
        document.querySelector(selector).addEventListener('click', N.renderLogin);
    });
    ['#openTechnicianPortal', '#heroTechnicianPortal'].forEach((selector) => {
        document.querySelector(selector).addEventListener('click', () => N.renderTechnicianPortal());
    });
}
