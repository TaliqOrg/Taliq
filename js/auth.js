/**
 * @file auth.js
 * @description Handles user authentication, session management, and route protection.
 * Manages login/registration form submissions, session verification, role-based
 * access control (admin vs user), and automatic page protection via the Fetch API.
 * @version 1.0.0
 */

/**
 * Initializes the login form submit handler.
 * Sends credentials to the auth API and redirects on success.
 */
function initLoginForm() {
    const loginForm = document.getElementById('loginForm');
    
    if (!loginForm) return;
    
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const loginBtn = document.getElementById('loginBtn');
        const errorMessage = document.getElementById('errorMessage');
        
        loginBtn.disabled = true;
        loginBtn.textContent = 'Logging in...';
        errorMessage.classList.remove('show');
        
        try {
            const response = await fetch('/taleeq/Taliq/api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'login',
                    email: email,
                    password: password
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.location.href = '/taleeq/Taliq' + data.redirect;
            } else {
                errorMessage.textContent = data.message;
                errorMessage.classList.add('show');
                loginBtn.disabled = false;
                loginBtn.textContent = 'Log In';
            }
            
        } catch (error) {
            console.error('Error:', error);
            errorMessage.textContent = 'An error occurred. Please try again.';
            errorMessage.classList.add('show');
            loginBtn.disabled = false;
            loginBtn.textContent = 'Log In';
        }
    });
}

/**
 * Initializes the registration form submit handler.
 * Validates inputs (full name, password match/length) and sends to the auth API.
 */
function initRegisterForm() {
    const registerForm = document.getElementById('registerForm');
    
    if (!registerForm) return;
    
    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const fullName = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm-password').value;
        const registerBtn = document.getElementById('registerBtn');
        const errorMessage = document.getElementById('errorMessage');
        
        errorMessage.classList.remove('show');
        
        const nameParts = fullName.split(' ');
        if (nameParts.length < 2) {
            errorMessage.textContent = 'Please enter your full name (first and last name)';
            errorMessage.classList.add('show');
            return;
        }
        
        const firstName = nameParts[0];
        const lastName = nameParts.slice(1).join(' ');
        
        if (password !== confirmPassword) {
            errorMessage.textContent = 'Passwords do not match';
            errorMessage.classList.add('show');
            return;
        }
        
        if (password.length < 6) {
            errorMessage.textContent = 'Password must be at least 6 characters long';
            errorMessage.classList.add('show');
            return;
        }
        
        registerBtn.disabled = true;
        registerBtn.textContent = 'Creating account...';
        
        try {
            const response = await fetch('/taleeq/Taliq/api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'register',
                    first_name: firstName,
                    last_name: lastName,
                    email: email,
                    password: password
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.location.href = '/taleeq/Taliq' + data.redirect;
            } else {
                errorMessage.textContent = data.message;
                errorMessage.classList.add('show');
                registerBtn.disabled = false;
                registerBtn.textContent = 'Sign Up';
            }
            
        } catch (error) {
            console.error('Error:', error);
            errorMessage.textContent = 'An error occurred. Please try again.';
            errorMessage.classList.add('show');
            registerBtn.disabled = false;
            registerBtn.textContent = 'Sign Up';
        }
    });
}

/**
 * Checks the current session status via the check_session API.
 * @returns {Promise<{authenticated: boolean, user: Object|null}>} The session state.
 */
async function checkSession() {
    console.log('[AUTH] checkSession() - fetching session status');
    try {
        const response = await fetch('/taleeq/Taliq/api/check_session.php');
        console.log('[AUTH] API response status:', response.status);
        const data = await response.json();
        console.log('[AUTH] Session data:', data);
        return data;
    } catch (error) {
        console.error('[AUTH] Session check error:', error);
        return { authenticated: false, user: null };
    }
}

/**
 * Enforces authentication. Redirects to login page if not authenticated.
 * @returns {Promise<Object|null>} The authenticated user object, or null.
 */
async function requireAuth() {
    console.log('[AUTH] requireAuth() called');
    const session = await checkSession();
    
    if (!session.authenticated) {
        console.log('[AUTH] Not authenticated - redirecting to login');
        window.location.href = '/taleeq/Taliq/pages/login.html';
        return null;
    }
    
    console.log('[AUTH] Authenticated as:', session.user);
    return session.user;
}

/**
 * Enforces admin role. Redirects non-admin users to the user home page.
 * @returns {Promise<Object|null>} The admin user object, or null.
 */
async function requireAdmin() {
    const user = await requireAuth();
    
    if (!user) return null;
    
    if (user.role !== 'admin') {
        window.location.href = '/taleeq/Taliq/pages/user/user_home.html';
        return null;
    }
    
    return user;
}

/**
 * Alias for requireAuth. Enforces user authentication.
 * @returns {Promise<Object|null>} The authenticated user object, or null.
 */
async function requireUser() {
    return await requireAuth();
}

/**
 * Logs the user out by redirecting to the auth logout endpoint.
 */
function logout() {
    window.location.href = '/taleeq/Taliq/api/auth.php?action=logout';
}

/**
 * Populates DOM elements with user data using data-* attribute selectors.
 * @param {Object} user - The user object with first_name, last_name, email, and role.
 */
function updateUserInfo(user) {
    const userNameElements = document.querySelectorAll('[data-user-name]');
    const userEmailElements = document.querySelectorAll('[data-user-email]');
    const userRoleElements = document.querySelectorAll('[data-user-role]');
    const userFullNameElements = document.querySelectorAll('[data-user-fullname]');
    
    userNameElements.forEach(el => el.textContent = user.first_name);
    userEmailElements.forEach(el => el.textContent = user.email);
    userRoleElements.forEach(el => el.textContent = user.role);
    userFullNameElements.forEach(el => el.textContent = `${user.first_name} ${user.last_name}`);
}

(function() {
    const path = window.location.pathname;
    console.log('[AUTH] Current path:', path);
    if (path.includes('/pages/admin/') || path.includes('/pages/user/')) {
        console.log('[AUTH] Protected path detected - hiding page');
        document.documentElement.style.visibility = 'hidden';
    }
})();

/**
 * Automatically protects pages based on URL path.
 * Admin pages require admin role; user pages require authentication.
 */
async function autoProtect() {
    const path = window.location.pathname;
    console.log('[AUTH] autoProtect() called for path:', path);
    let user = null;
    
    if (path.includes('/pages/admin/')) {
        console.log('[AUTH] Admin page detected - checking admin access');
        user = await requireAdmin();
    } else if (path.includes('/pages/user/')) {
        console.log('[AUTH] User page detected - checking user access');
        user = await requireUser();
    } else {
        console.log('[AUTH] Public page - no protection needed');
        document.documentElement.style.visibility = 'visible';
        return;
    }
    
    if (user) {
        console.log('[AUTH] User authenticated:', user);
        updateUserInfo(user);
        document.documentElement.style.visibility = 'visible';
    } else {
        console.log('[AUTH] No user - should have redirected');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initLoginForm();
    initRegisterForm();
    autoProtect();
});
