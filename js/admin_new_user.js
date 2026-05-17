/**
 * @file admin_new_user.js
 * @description Handles the new user creation form for administrators.
 * Validates password requirements and submits user data to the users API.
 * @version 1.0.0
 */

document.addEventListener('DOMContentLoaded', () => {



    const form = document.getElementById('new-user-form');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await createNewUser();
        });
    }
});

/**
 * Validates form inputs and creates a new user via the API.
 * @returns {Promise<void>}
 */
async function createNewUser() {



    const btn = document.getElementById('save-btn');

    const password = document.getElementById('password').value.trim();
    const confirmPassword = document.getElementById('confirmPassword').value.trim();

    if (password !== confirmPassword) {
        alert("Passwords do not match!");
        return;
    }

    if (password.length < 6) {
        alert("Password must be at least 6 characters long.");
        return;
    }

    const payload = {
        action: 'admin_create_user',
        first_name: document.getElementById('firstName').value.trim(),
        last_name: document.getElementById('lastName').value.trim(),
        email: document.getElementById('email').value.trim(),
        role: document.getElementById('role').value,
        password: password
    };

    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined spin-icon">sync</span> Creating...';

    try {
        const response = await fetch('../../api/users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.success) {

            window.location.href = 'manage_users.html';
        } else {
            alert(result.message || 'Failed to create user.');
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined">add_circle</span> Create User';
        }
    } catch (error) {
        console.error('Error creating user:', error);
        alert('A network error occurred.');
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined">add_circle</span> Create User';
    }
}