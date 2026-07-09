const N = window.NimbusOps;

N.handleCreateTechnician = async function handleCreateTechnician(event, workspace) {
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
            headers: N.jsonAuthHeaders(),
            body: JSON.stringify(payload),
        });

        const result = await response.json();

        if (response.status === 401) {
            N.clearToken();
            N.renderLogin();
            return;
        }

        if (!response.ok) {
            const firstError = result.errors
                ? Object.values(result.errors).flat()[0]
                : result.message;

            throw new Error(firstError || 'Technician could not be created.');
        }

        await N.renderTechniciansPage(workspace);
    } catch (errorMessage) {
        error.textContent = errorMessage.message;
    } finally {
        button.disabled = false;
        button.textContent = 'Save Technician';
    }
}

N.renderTechniciansPage = async function renderTechniciansPage(workspace) {
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
        .addEventListener('click', () => N.renderTechniciansPage(workspace));

    document
        .querySelector('#technicianForm')
        .addEventListener('submit', (event) => N.handleCreateTechnician(event, workspace));

    try {
        const serviceAreasResponse = await fetch('/api/service-areas', {
            headers: N.authHeaders(),
        });

        if (serviceAreasResponse.status === 401) {
            N.clearToken();
            N.renderLogin();
            return;
        }

        if (!serviceAreasResponse.ok) {
            throw new Error('Unable to load service areas.');
        }

        const serviceAreasResult = await serviceAreasResponse.json();
        const serviceAreas = serviceAreasResult.data ?? [];
        const serviceAreaSelect = document.querySelector('#technicianServiceAreaSelect');

        serviceAreaSelect.innerHTML = `<option value="">No service area</option>${N.renderSelectOptions(serviceAreas, (serviceArea) => serviceArea.name ?? `Service Area #${serviceArea.id}`)}`;
    } catch (error) {
        document.querySelector('#technicianFormError').textContent = error.message;
    }

    try {
        const response = await fetch('/api/technicians', {
            headers: N.authHeaders(),
        });

        if (response.status === 401) {
            N.clearToken();
            N.renderLogin();
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
                        <span>${N.escapeHtml(name)}</span>
                        <span>${N.escapeHtml(skill)}</span>
                        <span><b class="badge ${N.statusBadgeClass(availability)}">${N.escapeHtml(N.roleLabel(availability))}</b></span>
                        <span>${N.escapeHtml(serviceArea)}</span>
                    </div>
                `;
            }).join('')}
        `;
    } catch (error) {
        const content = document.querySelector('#techniciansContent');
        content.className = 'empty-state error-state';
        content.innerHTML = `
            <strong>Could not load technicians.</strong>
            <span>${N.escapeHtml(error.message)}</span>
        `;
    }

}
