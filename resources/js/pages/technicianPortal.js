const N = window.NimbusOps;

N.renderTechnicianPortal = function renderTechnicianPortal(errorMessage = '') {
    N.app.innerHTML = `
        <main class="partner-portal">
            <section class="partner-intro">
                <button class="back-link light" id="partnerBack" type="button">&larr; Back to website</button>
                <div><p class="eyebrow">NimbusOps technician network</p><h1>Build your technical career through trusted service.</h1><p>Apply to join a professional field network with structured assignments, transparent progress, and verified customer outcomes.</p></div>
                <div class="partner-steps"><article><span>01</span><div><strong>Verify identity</strong><small>Continue securely using your Google account.</small></div></article><article><span>02</span><div><strong>Submit application</strong><small>Share skills, experience, preferred area, and supporting information.</small></div></article><article><span>03</span><div><strong>Review and interview</strong><small>NimbusOps administrators validate documents and complete an interview.</small></div></article><article><span>04</span><div><strong>Activate workspace</strong><small>Approved applicants receive technician access using the same Google account.</small></div></article></div>
            </section>
            <section class="partner-access"><div class="partner-access-card"><div class="auth-brand"><span class="brand-mark">N</span><div><strong>NimbusOps</strong><small>Technician Partner Portal</small></div></div><p class="eyebrow">Partner access</p><h2>Apply or check your status</h2><p>Choose Google or create an applicant account. Neither method grants technician access until your application is approved.</p>${errorMessage ? `<p class="form-error partner-error">${N.escapeHtml(errorMessage)}</p>` : ''}<a class="google-button" href="/auth/technician/google"><span>G</span>Continue with Google</a><div class="auth-divider"><span>or apply with email</span></div><button class="primary-button partner-signup-button" id="openPartnerSignup" type="button">Create applicant account</button><div class="partner-assurance"><p><strong>Restricted applicant access</strong><span>Applicants cannot view customers or work orders.</span></p><p><strong>Approval required</strong><span>Only an authorized administrator can activate a technician account.</span></p></div><button class="inline-action" id="partnerSignIn" type="button">Already have company credentials? Sign in</button></div></section>
        </main>
    `;
    document.querySelector('#partnerBack').addEventListener('click', N.renderLanding);
    document.querySelector('#partnerSignIn').addEventListener('click', N.renderLogin);
    document.querySelector('#openPartnerSignup').addEventListener('click', N.renderTechnicianApplicantSignup);
}

N.renderTechnicianApplicantSignup = function renderTechnicianApplicantSignup() {
    N.app.innerHTML = `
        <main class="auth-page technician-signup-page">
            <section class="auth-panel technician-signup-card">
                <div class="auth-brand"><span class="brand-mark">N</span><div><strong>NimbusOps</strong><small>Technician Applicant Account</small></div></div>
                <button class="back-link" id="backToPartnerPortal" type="button">&larr; Back to partner portal</button>
                <form class="login-form" id="technicianApplicantSignupForm">
                    <div><p class="eyebrow">Applicant registration</p><h1>Create your account</h1><p>This account lets you submit and track an application. Technician access requires administrator approval.</p></div>
                    <label>Full name<input type="text" name="name" autocomplete="name" placeholder="Your full name" maxlength="120" required></label>
                    <label>Email address<input type="email" name="email" autocomplete="email" placeholder="you@example.com" required></label>
                    <label>Password<input type="password" name="password" autocomplete="new-password" placeholder="Minimum 8 characters" minlength="8" required></label>
                    <label>Confirm password<input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Repeat your password" minlength="8" required></label>
                    <p class="form-error" id="technicianSignupError" role="alert"></p>
                    <button class="primary-button" type="submit">Create applicant account</button>
                    <p class="secure-note">Your account remains restricted until application review, interview, and final approval are complete.</p>
                </form>
            </section>
        </main>
    `;
    document.querySelector('#backToPartnerPortal').addEventListener('click', () => N.renderTechnicianPortal());
    document.querySelector('#technicianApplicantSignupForm').addEventListener('submit', N.handleTechnicianApplicantSignup);
}

N.handleTechnicianApplicantSignup = async function handleTechnicianApplicantSignup(event) {
    event.preventDefault();
    const form = event.target;
    const data = new FormData(form);
    const error = document.querySelector('#technicianSignupError');
    const button = form.querySelector('button[type="submit"]');

    if (data.get('password') !== data.get('password_confirmation')) {
        error.textContent = 'Your password confirmation does not match.';
        return;
    }

    error.textContent = '';
    button.disabled = true;
    button.textContent = 'Creating restricted account...';
    try {
        const response = await fetch('/api/auth/technician/register', {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(Object.fromEntries(data.entries())),
        });
        const result = await response.json();
        if (!response.ok) throw new Error(N.registrationErrors(result));
        N.saveToken(result.token);
        N.currentUser = result.user;
        N.renderDashboard(result.user);
    } catch (signupError) {
        error.textContent = signupError.message;
        button.disabled = false;
        button.textContent = 'Create applicant account';
    }
}

N.registrationErrors = function registrationErrors(data) {
    if (data.errors) {
        return Object.values(data.errors).flat().join(' ');
    }

    return data.message || 'We could not create your account.';
}
N.applicationStatusCopy = function applicationStatusCopy(status) {
    const messages = {
        submitted: 'Your application has been received and is waiting for initial review.',
        under_review: 'The onboarding team is reviewing your experience and application details.',
        information_required: 'Additional information is required before review can continue.',
        interview_scheduled: 'Your interview has been scheduled. Details will appear here.',
        approved: 'Your application is approved. Technician activation is being completed.',
        rejected: 'Your application was not selected for the technician network.',
    };
    return messages[status] ?? 'Your application status has been updated.';
}

N.formatProfileDate = function formatProfileDate(value) {
    if (!value) return '-';

    const [year, month, day] = String(value).slice(0, 10).split('-').map(Number);

    if (!year || !month || !day) return '-';

    return new Date(year, month - 1, day).toLocaleDateString();
}

N.renderApplicantHome = async function renderApplicantHome(workspace) {
    workspace.innerHTML = `<section class="empty-state">Loading your technician application...</section>`;
    const response = await fetch('/api/technician-applications/me', { headers: N.authHeaders() });

    if (response.ok) {
        const result = await response.json();
        const application = result.data;
        workspace.innerHTML = `
            <header class="topbar"><div><p class="eyebrow">Technician partner onboarding</p><h1>Application Status</h1><p class="user-line">Reference ${N.escapeHtml(application.application_reference)}</p></div><span class="application-status">${N.escapeHtml(N.roleLabel(application.status))}</span></header>
            <section class="application-status-layout"><article class="panel application-progress"><p class="eyebrow">Current stage</p><h2>${N.escapeHtml(N.roleLabel(application.status))}</h2><p>${N.escapeHtml(N.applicationStatusCopy(application.status))}</p><div class="progress-track"><span style="width: ${application.status === 'submitted' ? '20%' : application.status === 'under_review' ? '40%' : application.status === 'interview_scheduled' ? '70%' : application.status === 'approved' ? '100%' : '55%'}"></span></div></article><article class="panel"><div class="panel-header"><h2>Application Summary</h2></div><dl class="application-summary"><div><dt>Full name</dt><dd>${N.escapeHtml(application.full_name)}</dd></div><div><dt>Date of birth</dt><dd>${N.escapeHtml(N.formatProfileDate(application.date_of_birth))}</dd></div><div><dt>Qualification</dt><dd>${N.escapeHtml(application.highest_qualification)}</dd></div><div><dt>Experience</dt><dd>${N.escapeHtml(application.years_experience)} years</dd></div><div><dt>Skills</dt><dd>${N.escapeHtml((application.skills ?? []).map(N.roleLabel).join(', '))}</dd></div><div><dt>Preferred area</dt><dd>${N.escapeHtml(application.preferred_service_area?.name ?? 'Flexible')}</dd></div><div><dt>Submitted</dt><dd>${N.escapeHtml(new Date(application.submitted_at).toLocaleDateString())}</dd></div></dl></article></section>
                        ${N.renderApplicationDocuments(application)}
        `;
        N.bindApplicationDocumentActions(workspace);
        return;
    }

    if (response.status !== 404) {
        workspace.innerHTML = `<section class="empty-state error-state"><strong>Could not load your application.</strong><span>Please sign in again or contact NimbusOps support.</span></section>`;
        return;
    }

    workspace.innerHTML = `
        <header class="topbar"><div><p class="eyebrow">Technician partner onboarding</p><h1>Complete Your Application</h1><p class="user-line">Signed in with Google as ${N.escapeHtml(N.currentUser.email)}</p></div></header>
        <section class="panel applicant-form-panel"><div class="panel-header"><div><h2>Professional Profile</h2><p>Tell the review team about your identity, qualifications, and field-service experience.</p></div></div><form class="entity-form" id="technicianApplicationForm"><label>Full legal name<input type="text" name="full_name" value="${N.escapeHtml(N.currentUser.name ?? '')}" required maxlength="120" placeholder="Name shown on your identity document"></label><label>Date of birth<input type="date" name="date_of_birth" required></label><label class="full-field">Highest qualification<input type="text" name="highest_qualification" required maxlength="255" placeholder="Example: NVQ Level 4 in Electrical Engineering"></label><label>Phone number<input type="tel" name="phone" required maxlength="30" placeholder="077 123 4567"></label><label>City<input type="text" name="city" required maxlength="120" placeholder="Colombo"></label><label class="full-field">Address<input type="text" name="address" required maxlength="255" placeholder="Your current address"></label><label>Years of experience<input type="number" name="years_experience" required min="0" max="60" value="0"></label><label>Preferred service area<select name="preferred_service_area_id" id="applicantServiceArea"><option value="">Loading service areas...</option></select></label><fieldset class="skill-fieldset full-field"><legend>Technical skills</legend><div class="skill-options">${['network', 'electrical', 'plumbing', 'ac', 'appliance', 'facility', 'general'].map((skill) => `<label><input type="checkbox" name="skills" value="${skill}"><span>${N.roleLabel(skill)}</span></label>`).join('')}</div></fieldset><label class="full-field">Why do you want to join NimbusOps?<textarea name="motivation" rows="5" minlength="30" maxlength="3000" required placeholder="Describe your experience, service values, and why you would be a strong technician partner."></textarea></label><p class="form-error full-field" id="applicationFormError"></p><div class="form-actions full-field"><button class="primary-button" type="submit">Submit Application</button></div></form></section>
    `;

    document.querySelector('#technicianApplicationForm').addEventListener('submit', (event) => N.handleTechnicianApplication(event, workspace));
    try {
        const result = await N.fetchApi('/api/service-areas');
        document.querySelector('#applicantServiceArea').innerHTML = `<option value="">Flexible across areas</option>${N.renderSelectOptions(result.data ?? [], (area) => `${area.name} - ${area.city}`)}`;
    } catch (error) {
        document.querySelector('#applicantServiceArea').innerHTML = '<option value="">Service areas unavailable</option>';
    }
}

N.applicationDocumentTypes = [
    { value: 'identity', label: 'Identity document', help: 'NIC, passport, or other official ID.' },
    { value: 'qualification', label: 'Qualification proof', help: 'NVQ, diploma, certificate, or training proof.' },
    { value: 'experience', label: 'Experience proof', help: 'Previous employer letter, work history, or service records.' },
    { value: 'profile_photo', label: 'Profile photo', help: 'Clear photo for technician identification.' },
    { value: 'driving_license', label: 'Driving license', help: 'Required if you travel to customer locations.' },
    { value: 'police_clearance', label: 'Police clearance', help: 'Optional now, required before activation.' },
];

N.documentTypeLabel = function documentTypeLabel(type) {
    return N.applicationDocumentTypes.find((item) => item.value === type)?.label ?? N.roleLabel(type);
}

N.renderApplicationDocuments = function renderApplicationDocuments(application) {
    const documents = application.documents ?? [];
    const documentRows = documents.length
        ? documents.map((document) => `<div class="document-row"><div><strong>${N.escapeHtml(N.documentTypeLabel(document.document_type))}</strong><span>${N.escapeHtml(document.original_name)} - ${N.escapeHtml(N.roleLabel(document.status))}</span></div><button class="secondary-button small-button delete-document-button" data-document-id="${document.id}" type="button">Remove</button></div>`).join('')
        : '<div class="empty-state compact"><strong>No documents uploaded yet.</strong><span>Upload identity and qualification documents to move toward interview review.</span></div>';

    const checklist = N.applicationDocumentTypes.map((type) => {
        const uploaded = documents.some((document) => document.document_type === type.value);
        return `<article class="document-check ${uploaded ? 'complete' : ''}"><span>${uploaded ? 'Done' : 'Needed'}</span><div><strong>${N.escapeHtml(type.label)}</strong><small>${N.escapeHtml(type.help)}</small></div></article>`;
    }).join('');

    const options = N.applicationDocumentTypes.map((type) => `<option value="${type.value}">${N.escapeHtml(type.label)}</option>`).join('');

    return `<section class="panel document-panel"><div class="panel-header"><div><p class="eyebrow">Verification documents</p><h2>Upload application documents</h2><p>Submit files for admin review before interview scheduling. PDF, JPG, PNG, or WebP files up to 5 MB are accepted.</p></div></div><div class="document-grid">${checklist}</div><form class="document-upload-form" id="applicationDocumentForm"><label>Document type<select name="document_type" required>${options}</select></label><label>File<input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp,image/*,application/pdf" required></label><button class="primary-button" type="submit">Upload Document</button><p class="form-error" id="documentUploadError"></p></form><div class="document-list"><h3>Uploaded documents</h3>${documentRows}</div></section>`;
}

N.bindApplicationDocumentActions = function bindApplicationDocumentActions(workspace) {
    const form = workspace.querySelector('#applicationDocumentForm');
    const error = workspace.querySelector('#documentUploadError');

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        const data = new FormData(form);
        error.textContent = '';
        button.disabled = true;
        button.textContent = 'Uploading...';

        try {
            const response = await fetch('/api/technician-applications/me/documents', {
                method: 'POST',
                headers: N.authHeaders(),
                body: data,
            });
            const contentType = response.headers.get('content-type') ?? '';
            const result = contentType.includes('application/json') ? await response.json() : {};
            if (!response.ok) {
                throw new Error(result.message || 'Upload rejected. Use PDF, JPG, PNG, or WebP files up to 5 MB.');
            }
            await N.renderApplicantHome(workspace);
        } catch (uploadError) {
            error.textContent = uploadError.message;
            button.disabled = false;
            button.textContent = 'Upload Document';
        }
    });

    workspace.querySelectorAll('.delete-document-button').forEach((button) => {
        button.addEventListener('click', async () => {
            button.disabled = true;
            button.textContent = 'Removing...';
            try {
                const response = await fetch(`/api/technician-applications/me/documents/${button.dataset.documentId}`, {
                    method: 'DELETE',
                    headers: N.authHeaders(),
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(result.message || 'Could not remove this document.');
                await N.renderApplicantHome(workspace);
            } catch (deleteError) {
                error.textContent = deleteError.message;
                button.disabled = false;
                button.textContent = 'Remove';
            }
        });
    });
}

N.handleTechnicianApplication = async function handleTechnicianApplication(event, workspace) {
    event.preventDefault();
    const form = event.target;
    const data = new FormData(form);
    const error = document.querySelector('#applicationFormError');
    const button = form.querySelector('button[type="submit"]');
    const skills = data.getAll('skills');

    if (!skills.length) {
        error.textContent = 'Select at least one technical skill.';
        return;
    }

    const payload = {
        full_name: data.get('full_name'),
        date_of_birth: data.get('date_of_birth'),
        phone: data.get('phone'),
        address: data.get('address'),
        city: data.get('city'),
        years_experience: Number(data.get('years_experience')),
        highest_qualification: data.get('highest_qualification'),
        skills,
        motivation: data.get('motivation'),
    };
    if (data.get('preferred_service_area_id')) payload.preferred_service_area_id = Number(data.get('preferred_service_area_id'));

    error.textContent = '';
    button.disabled = true;
    button.textContent = 'Submitting securely...';
    try {
        const response = await fetch('/api/technician-applications', { method: 'POST', headers: N.jsonAuthHeaders(), body: JSON.stringify(payload) });
        const result = await response.json();
        if (!response.ok) throw new Error(N.registrationErrors(result));
        N.currentUser.technician_application = result.application;
        await N.renderApplicantHome(workspace);
    } catch (submissionError) {
        error.textContent = submissionError.message;
        button.disabled = false;
        button.textContent = 'Submit Application';
    }
}
