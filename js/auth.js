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

async function requireAdmin() {
    const user = await requireAuth();
    
    if (!user) return null;
    
    if (user.role !== 'admin') {
        window.location.href = '/taleeq/Taliq/pages/user/user_home.html';
        return null;
    }
    
    return user;
}

async function requireUser() {
    return await requireAuth();
}

function logout() {
    window.location.href = '/taleeq/Taliq/api/auth.php?action=logout';
}

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
    autoProtect();
});
