function updateCartBadge(count) {
    const badge = document.querySelector('.cart-badge');
    if (!badge) return;

    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'flex';
    } else {
        badge.textContent = '';
        badge.style.display = 'none';
    }
}

async function loadCartBadge() {
    try {
        const response = await fetch('/taleeq/Taliq/api/cart.php?action=count');
        const result = await response.json();
        if (result.success) {
            updateCartBadge(result.count);
        }
    } catch (error) {
        console.error('Could not load cart count:', error);
    }
}

async function quickAddToCart(courseId, workshopId, price) {
    try {
        const response = await fetch('/taleeq/Taliq/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add',
                course_id: courseId,
                workshop_id: workshopId,
                price: price,
                quantity: 1
            })
        });

        const result = await response.json();

        if (result.success) {
            updateCartBadge(result.count);
            showToast('Item added to cart!', 'success');
        } else {
            if (response.status === 401 && result.redirect) {
                window.location.href = result.redirect;
            } else {
                showToast(result.message || 'Could not add item', 'error');
            }
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showToast('Something went wrong', 'error');
    }
}

function showToast(message, type = 'success') {
    const existing = document.getElementById('toast-notification');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = 'toast-notification';
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 2rem;
        left: 50%;
        transform: translateX(-50%);
        padding: 1rem 2rem;
        border-radius: 0.5rem;
        font-weight: 600;
        z-index: 9999;
        animation: slideUp 0.3s ease;
        background-color: ${type === 'success' ? '#059669' : '#dc2626'};
        color: white;
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}

async function loadComponent(elementId, componentPath) {
    try {
        const response = await fetch(componentPath);
        const html = await response.text();
        document.getElementById(elementId).innerHTML = html;
    } catch (error) {
        console.error(`Error loading component ${componentPath}:`, error);
    }
}

async function initializeComponents() {
    await loadComponent('header-placeholder', '/taleeq/Taliq/components/header.html');
    await loadComponent('footer-placeholder', '/taleeq/Taliq/components/footer.html');

    await updateHeaderFooter();

    // Wire the header search bar — redirect to explore page with search query
    const searchInput = document.getElementById('headerSearchInput');
    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    window.location.href = '/taleeq/Taliq/pages/explore.html?q=' + encodeURIComponent(query);
                }
            }
        });
    }
}

async function updateHeaderFooter() {
    const session = await checkSession();
    
    const visitorLinks = document.getElementById('visitorLinks');
    const userLinks = document.getElementById('userLinks');
    const adminLinks = document.getElementById('adminLinks');
    const headerSearch = document.getElementById('headerSearch');
    const headerLogo = document.getElementById('headerLogo');
    const footerLogo = document.getElementById('footerLogo');
    const footerUserLinks = document.getElementById('footerUserLinks');
    const footerAdminLinks = document.getElementById('footerAdminLinks');
    
    if (!session.authenticated) {
        if (visitorLinks) visitorLinks.style.display = 'flex';
        if (userLinks) userLinks.style.display = 'none';
        if (adminLinks) adminLinks.style.display = 'none';
        if (headerSearch) headerSearch.style.display = 'none';
        if (headerLogo) headerLogo.href = '/taleeq/Taliq/pages/welcome_page.html';
        if (footerUserLinks) footerUserLinks.style.display = 'none';
        if (footerAdminLinks) footerAdminLinks.style.display = 'none';
        return;
    }
    
    const user = session.user;
    
    if (user.role === 'admin') {
        if (visitorLinks) visitorLinks.style.display = 'none';
        if (userLinks) userLinks.style.display = 'none';
        if (adminLinks) adminLinks.style.display = 'flex';
        if (headerSearch) headerSearch.style.display = 'none';
        if (headerLogo) {
            headerLogo.textContent = 'Taliq Admin';
            headerLogo.href = '/taleeq/Taliq/pages/admin/admin_home.html';
        }
        if (footerLogo) footerLogo.textContent = 'Taliq Admin';
        if (footerUserLinks) footerUserLinks.style.display = 'none';
        if (footerAdminLinks) footerAdminLinks.style.display = 'block';
    } else {
        if (visitorLinks) visitorLinks.style.display = 'none';
        if (userLinks) userLinks.style.display = 'flex';
        if (adminLinks) adminLinks.style.display = 'none';
        if (headerSearch) headerSearch.style.display = 'flex';
        if (headerLogo) headerLogo.href = '/taleeq/Taliq/pages/user/user_home.html';
        if (footerUserLinks) footerUserLinks.style.display = 'block';
        if (footerAdminLinks) footerAdminLinks.style.display = 'none';
    }
    
    updateUserInfo(user);

    if (user.role !== 'admin') {
        loadCartBadge();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('header-placeholder') || document.getElementById('footer-placeholder')) {
        initializeComponents();
    }
});
