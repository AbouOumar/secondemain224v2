// Authentication Service for Seconde Main 224
class AuthService {
    constructor() {
        this.user = null;
        this.token = null;
        this.init();
    }

    init() {
        // Check if user is already logged in (from localStorage or cookie)
        const storedUser = localStorage.getItem('sm224_user');
        const storedToken = localStorage.getItem('sm224_token');
        
        if (storedUser && storedToken) {
            try {
                this.user = JSON.parse(storedUser);
                this.token = storedToken;
                // Set axios headers if using axios
                this.setupAxiosInterceptor();
            } catch (e) {
                this.clear();
            }
        }
    }

    setupAxiosInterceptor() {
        // If using axios, set up interceptor
        if (typeof axios !== 'undefined') {
            axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
        }
    }

    async login(email, password) {
        try {
            const response = await fetch('/api/v1/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ email, password })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Login failed');
            }

            const data = await response.json();
            this.setUser(data.user, data.token);
            return { success: true, user: this.user };
        } catch (error) {
            console.error('Login error:', error);
            return { success: false, error: error.message };
        }
    }

    async register(userData) {
        try {
            const response = await fetch('/api/v1/auth/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(userData)
            });

            if (!response.ok) {
                const errorData = await response.json();
                if (errorData.errors) {
                    throw new Error(Object.values(errorData.errors).flat().join(', '));
                } else {
                    throw new Error(errorData.message || 'Registration failed');
                }
            }

            const data = await response.json();
            this.setUser(data.user, data.token);
            return { success: true, user: this.user };
        } catch (error) {
            console.error('Registration error:', error);
            return { success: false, error: error.message };
        }
    }

    async logout() {
        try {
            const response = await fetch('/api/v1/auth/logout', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Authorization': `Bearer ${this.token}`
                }
            });

            // Clear local state regardless of API response
            this.clear();
            
            if (!response.ok) {
                console.warn('Logout API call failed but clearing local state');
            }
            
            return { success: true };
        } catch (error) {
            console.error('Logout error:', error);
            // Clear local state even on error
            this.clear();
            return { success: true };
        }
    }

    setUser(user, token) {
        this.user = user;
        this.token = token;
        
        // Store in localStorage
        localStorage.setItem('sm224_user', JSON.stringify(user));
        localStorage.setItem('sm224_token', token);
        
        // Setup axios interceptor if available
        this.setupAxiosInterceptor();
        
        // Dispatch auth change event
        window.dispatchEvent(new CustomEvent('auth:change', { detail: { user: this.user } }));
    }

    clear() {
        this.user = null;
        this.token = null;
        localStorage.removeItem('sm224_user');
        localStorage.removeItem('sm224_token');
        
        // Remove axios auth header
        if (typeof axios !== 'undefined') {
            delete axios.defaults.headers.common['Authorization'];
        }
        
        // Dispatch auth change event
        window.dispatchEvent(new CustomEvent('auth:change', { detail: { user: null } }));
    }

    isAuthenticated() {
        return !!this.user && !!this.token;
    }

    getUser() {
        return this.user;
    }

    getToken() {
        return this.token;
    }
}

// Initialize auth service
const auth = new AuthService();

// Helper functions for Blade templates
window.isAuthenticated = () => auth.isAuthenticated();
window.getCurrentUser = () => auth.getUser();
window.getAuthToken = () => auth.getToken();

// Redirect if not authenticated (for protected pages)
window.requireAuth = function(redirectTo = '/login') {
    if (!auth.isAuthenticated()) {
        // Store intended URL for redirect after login
        localStorage.setItem('sm224_intended_url', window.location.pathname + window.location.search);
        window.location.href = redirectTo;
    }
};

// Redirect if authenticated (for guest pages like login/register)
window.requireGuest = function(redirectTo = '/') {
    if (auth.isAuthenticated()) {
        window.location.href = redirectTo;
    }
};

// Handle auth state changes for UI updates
document.addEventListener('auth:change', (e) => {
    const user = e.detail.user;
    const isAuth = !!user;
    
    // Update UI elements that depend on auth state
    document.querySelectorAll('[data-auth-status="authenticated"]').forEach(el => {
        el.style.display = isAuth ? '' : 'none';
    });
    
    document.querySelectorAll('[data-auth-status="guest"]').forEach(el => {
        el.style.display = isAuth ? 'none' : '';
    });
    
    // Update user info in UI
    document.querySelectorAll('[data-user="name"]').forEach(el => {
        el.textContent = user ? user.name : '';
    });
    
    document.querySelectorAll('[data-user="email"]').forEach(el => {
        el.textContent = user ? user.email : '';
    });
    
    document.querySelectorAll('[data-user="avatar"]').forEach(el => {
        if (user && user.avatar) {
            el.src = user.avatar;
        } else {
            el.src = '/assets/img/apple-icon.png'; // default avatar
        }
    });
});

// Initialize UI on page load
document.addEventListener('DOMContentLoaded', () => {
    // Trigger auth change event to set initial UI state
    window.dispatchEvent(new CustomEvent('auth:change', { detail: { user: auth.getUser() } }));
    
    // Handle intended URL after login
    const intendedUrl = localStorage.getItem('sm224_intended_url');
    if (intendedUrl && window.location.pathname.includes('/login')) {
        // Clear after checking
        localStorage.removeItem('sm224_intended_url');
        // We'll redirect after successful login in the login form handler
    }
});

// Export for use in other modules
window.AuthService = AuthService;
window.auth = auth;