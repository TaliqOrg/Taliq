/**
 * @file users.js
 * @description Admin user management page controller.\n * Handles listing, filtering, and deleting users.\n * @version 1.0.0
 */

let allUsers = [];

document.addEventListener('DOMContentLoaded', () => {

    if (document.getElementById('users-list')) {
        loadUsers();
        document.getElementById('search-input').addEventListener('input', applyFilters);
        document.getElementById('role-filter').addEventListener('change', applyFilters);
    }
});

/**
 * Fetches all users from the API and renders them.
 * @returns {Promise<void>}
 */
async function loadUsers() {
    const container = document.getElementById('users-list');
    container.innerHTML = '<p style="padding: 1rem;">Loading users...</p>';

    try {
        const response = await fetch('../../api/users.php?action=all_users');
        const result = await response.json();

        if (result.success && result.users) {
            allUsers = result.users;
            renderUsers(allUsers);
        } else {
            container.innerHTML = `<p style="padding: 1rem; color: red;">${result.message || 'No users found.'}</p>`;
        }
    } catch (err) {
        console.error(err);
        container.innerHTML = '<p style="padding: 1rem; color: red;">Failed to load users.</p>';
    }
}

/**
 * Renders user cards into the users list container.
 * @param {Array<Object>} usersToRender - The array of user objects to display.
 */
function renderUsers(usersToRender) {
    const container = document.getElementById('users-list');
    
    if (usersToRender.length === 0) {
        container.innerHTML = '<p style="padding: 1rem; color: #6b7280;">No matching users found.</p>';
        return;
    }

    container.innerHTML = usersToRender.map(user => `
        <div class="admin-list-card">
            <div class="admin-card-info">
                <h3 class="admin-card-title">${escapeHtml(user.FirstName)} ${escapeHtml(user.LastName)}</h3>
                <div class="admin-card-meta" style="display: flex; gap: 15px;">
                    <span class="meta-item">
                        <span class="material-symbols-outlined" style="font-size: 16px;">category</span>
                        ${capitalize(user.Role)}
                    </span>
                    <span class="meta-item">
                        <span class="material-symbols-outlined" style="font-size: 16px;">mail</span>
                        ${escapeHtml(user.Email)}
                    </span>
                </div>
            </div>
            <div class="admin-card-actions">
                <a href="edit_user.html?id=${user.UserId}" class="action-btn" title="Edit User">
                    <span class="material-symbols-outlined">edit</span> Edit
                </a>
                <button class="action-btn danger" onclick="deleteUser(${user.UserId}, this)" title="Delete User">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </div>
        </div>
    `).join('');
}

/**
 * Filters users by search term and role dropdown.
 */
function applyFilters() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const roleFilter = document.getElementById('role-filter').value.toLowerCase();

    const filtered = allUsers.filter(user => {
        const fullName = `${user.FirstName} ${user.LastName}`.toLowerCase();
        const matchesSearch = fullName.includes(searchTerm) || user.Email.toLowerCase().includes(searchTerm);
        const matchesRole = roleFilter === 'all' || user.Role.toLowerCase() === roleFilter;
        return matchesSearch && matchesRole;
    });

    renderUsers(filtered);
}

/**
 * Deletes a user after confirmation.
 * @param {number} userId - The user ID to delete.
 * @param {HTMLButtonElement} btnElement - The delete button element.
 * @returns {Promise<void>}
 */
async function deleteUser(userId, btnElement) {
    if (!confirm('Are you sure you want to delete this user? This cannot be undone.')) return;

    btnElement.disabled = true;

    try {
        const response = await fetch('../../api/users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_user', user_id: userId })
        });
        
        const result = await response.json();

        if (result.success) {

            allUsers = allUsers.filter(u => u.UserId !== userId);
            applyFilters(); 
        } else {
            alert(result.message || 'Failed to delete user');
            btnElement.disabled = false;
        }
    } catch (err) {
        console.error(err);
        alert('A network error occurred.');
        btnElement.disabled = false;
    }
}


/**
 * Escapes HTML special characters in user data.
 * @param {string} text - The raw text.
 * @returns {string} The escaped string.
 */
function escapeHtml(text) {
    if (!text) return '';
    return text.toString().replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

/**
 * Capitalizes the first character of a string.
 * @param {string} string - The input string.
 * @returns {string} The capitalized string.
 */
function capitalize(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1);
}