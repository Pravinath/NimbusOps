const N = window.NimbusOps;

N.fetchApi = async function fetchApi(path) {
    const response = await fetch(path, { headers: N.authHeaders() });

    if (response.status === 401) {
        N.clearToken();
        N.renderLogin();
        throw new Error('Your session has expired.');
    }

    const result = await response.json();

    if (!response.ok) {
        throw new Error(result.message || 'Unable to load this workspace.');
    }

    return result;
}
