
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
    
    updateHeaderFooter();
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
        if (headerLogo) headerLogo.textContent = 'Taliq Admin';
        if (footerLogo) footerLogo.textContent = 'Taliq Admin';
        if (footerUserLinks) footerUserLinks.style.display = 'none';
        if (footerAdminLinks) footerAdminLinks.style.display = 'block';
    } else {
        if (visitorLinks) visitorLinks.style.display = 'none';
        if (userLinks) userLinks.style.display = 'flex';
        if (adminLinks) adminLinks.style.display = 'none';
        if (headerSearch) headerSearch.style.display = 'flex';
        if (footerUserLinks) footerUserLinks.style.display = 'block';
        if (footerAdminLinks) footerAdminLinks.style.display = 'none';
    }
    
    updateUserInfo(user);
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('header-placeholder') || document.getElementById('footer-placeholder')) {
        initializeComponents();
    }
});
