const N = window.NimbusOps;

N.getToken = function getToken() {
    return localStorage.getItem(N.tokenKey);
}

N.saveToken = function saveToken(token) {
    localStorage.setItem(N.tokenKey, token);
}

N.clearToken = function clearToken() {
    localStorage.removeItem(N.tokenKey);
}

N.authHeaders = function authHeaders() {
    return {
        Accept: 'application/json',
        Authorization: `Bearer ${N.getToken()}`,
    };
}

N.jsonAuthHeaders = function jsonAuthHeaders() {
    return {
        ...N.authHeaders(),
        'Content-Type': 'application/json',
    };
}

N.escapeHtml = function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

N.statusBadgeClass = function statusBadgeClass(status) {
    const normalized = String(status ?? '').toLowerCase();

    if (['available'].includes(normalized)) {
        return 'availability-available';
    }

    if (['on_leave', 'on leave'].includes(normalized)) {
        return 'availability-leave';
    }

    if (['offline'].includes(normalized)) {
        return 'availability-offline';
    }

    if (['busy'].includes(normalized)) {
        return 'warning';
    }

    if (['resolved', 'completed', 'active', 'approved'].includes(normalized)) {
        return 'success';
    }

    if (['assigned', 'in_progress', 'in progress', 'under_review'].includes(normalized)) {
        return 'info';
    }

    if (['rejected', 'cancelled'].includes(normalized)) {
        return 'danger';
    }

    return 'warning';
}
