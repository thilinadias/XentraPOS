// C:\xampp\htdocs\pos\assets\js\main.js

/**
 * Generic helper for API fetch requests to reduce boilerplate
 */
async function apiCall(endpoint, method = 'GET', body = null) {
    try {
        const options = {
            method: method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        };

        if (body && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(body);
        }

        const response = await fetch('/pos/' + endpoint, options);
        let text = await response.text();
        let data;
        
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Non-JSON response received", text);
            // Provide exact status code for debugging
            throw new Error(`Server returned an invalid response (HTTP ${response.status}).`);
        }

        if (!response.ok) {
            throw new Error(data.message || `HTTP Error ${response.status}`);
        }

        return data;

    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

/**
 * Helper to show bootstrap alerts dynamically
 */
function showAlert(message, type = 'danger', containerId = 'alertContainer') {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
}
