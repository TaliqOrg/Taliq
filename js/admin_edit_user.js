document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const userId = urlParams.get('id');

    if (!userId) {
        alert("No User ID provided.");
        window.location.href = 'manage_users.html';
        return;
    }

    loadUserData(userId);

    document.getElementById('edit-user-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveUserDetails(userId);
    });
});

async function loadUserData(userId) {
    try {
        const response = await fetch(`../../api/users.php?action=get_user&id=${userId}`);
        const result = await response.json();

        if (result.success && result.user) {
            const user = result.user;
            

            document.getElementById('user-name-display').textContent = `${user.FirstName} ${user.LastName}`;
            

            document.getElementById('firstName').value = user.FirstName;
            document.getElementById('lastName').value = user.LastName;
            document.getElementById('email').value = user.Email;
            document.getElementById('role').value = user.Role;
        } else {
            alert(result.message || 'Failed to load user.');
            window.location.href = 'manage_users.html';
        }
    } catch (error) {
        console.error('Error fetching user:', error);
        alert('Network error occurred while fetching user data.');
    }
}

async function saveUserDetails(userId) {
    const btn = document.getElementById('save-btn');

    const password = document.getElementById('password').value.trim();
    const confirmPassword = document.getElementById('confirmPassword').value.trim();

    if (password && password !== confirmPassword) {
        alert("Passwords do not match!");
        return;
    }

    const payload = {
        action: 'admin_update_user',
        user_id: parseInt(userId), 
        first_name: document.getElementById('firstName').value.trim(),
        last_name: document.getElementById('lastName').value.trim(),
        email: document.getElementById('email').value.trim(),
        role: document.getElementById('role').value
    };

    if (password) {
        payload.password = password;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined spin-icon">sync</span> Saving...';

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
            alert(result.message || 'Failed to update user.');
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined">save</span> Save Changes';
        }
    } catch (error) {
        alert('A network error occurred.');
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined">save</span> Save Changes';
    }
}