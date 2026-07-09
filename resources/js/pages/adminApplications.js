const N = window.NimbusOps;

N.reviewStatusOptions = [
    ['under_review', 'Under Review'],
    ['information_required', 'Information Required'],
    ['interview_scheduled', 'Interview Scheduled'],
    ['rejected', 'Rejected'],
];

N.renderAdminApplicationsPage = async function renderAdminApplicationsPage(workspace) {
    workspace.innerHTML = `
        <header class="topbar">
            <div>
                <p class="eyebrow">Technician onboarding</p>
                <h1>Applications</h1>
                <p class="user-line">Review applicants, documents, interview status, and technician activation.</p>
            </div>
            <button class="secondary-button" id="refreshApplicationsButton">Refresh</button>
        </header>

        <section class="panel">
            <div class="panel-header">
                <h2>Application Review Queue</h2>
            </div>
            <div id="applicationsContent" class="empty-state">Loading technician applications...</div>
        </section>
    `;

    workspace.querySelector('#refreshApplicationsButton').addEventListener('click', () => N.renderAdminApplicationsPage(workspace));

    try {
        const result = await N.fetchApi('/api/admin/technician-applications');
        const applications = result.data ?? [];
        const content = workspace.querySelector('#applicationsContent');

        if (!applications.length) {
            content.className = 'empty-state';
            content.innerHTML = '<strong>No technician applications yet.</strong><span>New applicants will appear here after submission.</span>';
            return;
        }

        content.className = 'application-review-list';
        content.innerHTML = applications.map(N.renderApplicationReviewCard).join('');
        N.bindApplicationReviewActions(workspace);
    } catch (error) {
        const content = workspace.querySelector('#applicationsContent');
        content.className = 'empty-state error-state';
        content.innerHTML = `<strong>Could not load applications.</strong><span>${N.escapeHtml(error.message)}</span>`;
    }
}

N.renderApplicationReviewCard = function renderApplicationReviewCard(application) {
    const documents = application.documents ?? [];
    const documentList = documents.length
        ? documents.map((document) => `<span>${N.escapeHtml(N.documentTypeLabel?.(document.document_type) ?? N.roleLabel(document.document_type))}: ${N.escapeHtml(document.original_name)} <button class="document-view-button" type="button" data-application-id="${application.id}" data-document-id="${document.id}">View</button></span>`).join('')
        : '<span>No documents uploaded yet.</span>';
    const statusOptions = N.reviewStatusOptions
        .map(([value, label]) => `<option value="${value}" ${application.status === value ? 'selected' : ''}>${label}</option>`)
        .join('');

    const approvedSummary = application.status === 'approved'
        ? `<div class="activation-summary"><strong>Activated technician</strong><span>${N.escapeHtml(application.user?.name ?? application.full_name ?? 'Applicant')} can now access the technician workspace.</span></div>`
        : '';
    const reviewForm = application.status === 'approved'
        ? ''
        : `<form class="application-review-form">
                <label class="review-field"><span>Status</span><select name="status">${statusOptions}</select></label>
                <label class="review-field full-field"><span>Review notes</span><textarea name="review_notes" rows="4" placeholder="Add review note for the applicant record.">${N.escapeHtml(application.review_notes ?? '')}</textarea></label>
                <label class="review-field full-field rejection-reason-field"><span>Rejection reason</span><textarea name="rejection_reason" rows="4" placeholder="Required only when rejecting.">${N.escapeHtml(application.rejection_reason ?? '')}</textarea></label>
                <p class="form-error full-field review-error"></p>
                <div class="form-actions full-field application-actions">
                    <button class="secondary-button update-application-button" type="submit">Update Status</button>
                    <button class="primary-button approve-application-button" type="button">Approve & Activate</button>
                </div>
            </form>`;

    return `
        <article class="application-review-card ${application.status === 'approved' ? 'is-approved' : ''}" data-application-id="${application.id}">
            <div class="application-review-main">
                <div>
                    <p class="eyebrow">${N.escapeHtml(application.application_reference)}</p>
                    <h3>${N.escapeHtml(application.full_name ?? application.user?.name ?? 'Applicant')}</h3>
                    <p>${N.escapeHtml(application.user?.email ?? '-')} - ${N.escapeHtml(application.city ?? '-')}</p>
                </div>
                <span class="badge ${N.statusBadgeClass(application.status)}">${N.escapeHtml(N.roleLabel(application.status))}</span>
            </div>

            <dl class="application-review-meta">
                <div><dt>Qualification</dt><dd>${N.escapeHtml(application.highest_qualification ?? '-')}</dd></div>
                <div><dt>Experience</dt><dd>${N.escapeHtml(application.years_experience ?? 0)} years</dd></div>
                <div><dt>Skills</dt><dd>${N.escapeHtml((application.skills ?? []).map(N.roleLabel).join(', ') || '-')}</dd></div>
                <div><dt>Preferred Area</dt><dd>${N.escapeHtml(application.preferred_service_area?.name ?? 'Flexible')}</dd></div>
            </dl>

            <div class="document-mini-list"><strong>Documents</strong>${documentList}</div>
            ${approvedSummary}
            ${reviewForm}
        </article>
    `;
}

N.bindApplicationReviewActions = function bindApplicationReviewActions(workspace) {
    workspace.querySelectorAll('.application-review-card').forEach((card) => {
        const form = card.querySelector('.application-review-form');
        const error = card.querySelector('.review-error');
        const updateButton = card.querySelector('.update-application-button');
        const approveButton = card.querySelector('.approve-application-button');
        const applicationId = card.dataset.applicationId;

        card.querySelectorAll('.document-view-button').forEach((button) => {
            button.addEventListener('click', () => {
                N.openApplicationDocument(button.dataset.applicationId, button.dataset.documentId, button);
            });
        });

        if (!form || !error || !updateButton || !approveButton) {
            return;
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(form);
            const payload = {
                status: formData.get('status'),
                review_notes: formData.get('review_notes'),
                rejection_reason: formData.get('rejection_reason'),
            };

            error.textContent = '';
            updateButton.disabled = true;
            updateButton.textContent = 'Updating...';

            try {
                const response = await fetch(`/api/admin/technician-applications/${applicationId}/status`, {
                    method: 'PATCH',
                    headers: N.jsonAuthHeaders(),
                    body: JSON.stringify(payload),
                });
                const result = await response.json();
                if (!response.ok) throw new Error(N.registrationErrors(result));
                await N.renderAdminApplicationsPage(workspace);
            } catch (reviewError) {
                error.textContent = reviewError.message;
                updateButton.disabled = false;
                updateButton.textContent = 'Update Status';
            }
        });

        approveButton.addEventListener('click', async () => {
            error.textContent = '';
            approveButton.disabled = true;
            approveButton.textContent = 'Approving...';

            try {
                const response = await fetch(`/api/admin/technician-applications/${applicationId}/approve`, {
                    method: 'POST',
                    headers: N.authHeaders(),
                });
                const result = await response.json();
                if (!response.ok) throw new Error(N.registrationErrors(result));
                await N.renderAdminApplicationsPage(workspace);
            } catch (approvalError) {
                error.textContent = approvalError.message;
                approveButton.disabled = false;
                approveButton.textContent = 'Approve & Activate';
            }
        });
    });
}
N.openApplicationDocument = async function openApplicationDocument(applicationId, documentId, button) {
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Opening...';

    try {
        const response = await fetch(`/api/admin/technician-applications/${applicationId}/documents/${documentId}/view`, {
            headers: N.authHeaders(),
        });

        if (!response.ok) {
            let message = 'Could not open document.';
            try {
                const result = await response.json();
                message = N.registrationErrors(result);
            } catch (_) {
                message = response.status === 404 ? 'Document file was not found.' : message;
            }
            throw new Error(message);
        }

        const fileBlob = await response.blob();
        const fileUrl = URL.createObjectURL(fileBlob);
        window.open(fileUrl, '_blank', 'noopener');
        setTimeout(() => URL.revokeObjectURL(fileUrl), 60000);
    } catch (error) {
        alert(error.message);
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
}