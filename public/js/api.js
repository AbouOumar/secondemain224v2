// API Service for Seconde Main 224
class APIService {
    constructor(baseUrl = '') {
        this.baseUrl = baseUrl;
        this.token = null;
    }

    setToken(token) {
        this.token = token;
    }

    getHeaders() {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
        
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        
        return headers;
    }

    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = `${this.baseUrl}/api/v1/${endpoint}${queryString ? `?${queryString}` : ''}`;
        
        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: this.getHeaders()
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error(`API GET ${endpoint} error:`, error);
            throw error;
        }
    }

    async post(endpoint, data = {}) {
        const url = `${this.baseUrl}/api/v1/${endpoint}`;
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error(`API POST ${endpoint} error:`, error);
            throw error;
        }
    }

    async put(endpoint, data = {}) {
        const url = `${this.baseUrl}/api/v1/${endpoint}`;
        
        try {
            const response = await fetch(url, {
                method: 'PUT',
                headers: this.getHeaders(),
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error(`API PUT ${endpoint} error:`, error);
            throw error;
        }
    }

    async delete(endpoint) {
        const url = `${this.baseUrl}/api/v1/${endpoint}`;
        
        try {
            const response = await fetch(url, {
                method: 'DELETE',
                headers: this.getHeaders()
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error(`API DELETE ${endpoint} error:`, error);
            throw error;
        }
    }
}

// Initialize API service
const api = new APIService();

// Helper function to get CSRF token from meta tag
function getCSRFToken() {
    const token = document.querySelector('meta[name="csrf-token"]');
    return token ? token.getAttribute('content') : null;
}

// Set up AJAX headers for Laravel
document.addEventListener('DOMContentLoaded', function() {
    const token = getCSRFToken();
    if (token) {
        // Axios-like setup for fetch
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            // Add CSRF token to headers for same-origin requests
            if (url.startsWith('/') || url.startsWith(window.location.origin)) {
                const headers = new Headers(options.headers || {});
                headers.set('X-CSRF-TOKEN', token);
                options.headers = headers;
            }
            return originalFetch(url, options);
        };
    }
});