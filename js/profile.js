function toggleEditMode() {
    document.getElementById('viewMode').style.display = 'none';
    document.getElementById('editMode').style.display = 'grid';
    document.getElementById('editBtn').style.display = 'none';
}

function cancelEdit() {
    document.getElementById('viewMode').style.display = 'grid';
    document.getElementById('editMode').style.display = 'none';
    document.getElementById('editBtn').style.display = 'flex';
    document.getElementById('successMessage').style.display = 'none';
}

async function saveProfile() {
    const firstName = document.getElementById('editFirstName').value.trim();
    const lastName = document.getElementById('editLastName').value.trim();
    const email = document.getElementById('editEmail').value.trim();
    const phone = document.getElementById('editPhone').value.trim();

    if (!firstName || !lastName || !email) {
        alert('Please fill in all required fields');
        return;
    }

    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="material-symbols-outlined">hourglass_empty</span> Saving...';

    try {
        const response = await fetch('../../api/users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'update_profile',
                first_name: firstName,
                last_name: lastName,
                email: email,
                phone_number: phone || null
            })
        });

        const result = await response.json();

        if (result.success) {
            document.getElementById('displayFirstName').textContent = firstName;
            document.getElementById('displayLastName').textContent = lastName;
            document.getElementById('displayEmail').textContent = email;
            document.getElementById('displayPhone').textContent = phone || 'Not provided';

            const headerName = document.querySelector('[data-user-fullname]');
            const headerEmail = document.querySelector('[data-user-email]');
            if (headerName) headerName.textContent = `${firstName} ${lastName}`;
            if (headerEmail) headerEmail.textContent = email;

            document.getElementById('viewMode').style.display = 'grid';
            document.getElementById('editMode').style.display = 'none';
            document.getElementById('editBtn').style.display = 'flex';
            document.getElementById('successMessage').style.display = 'block';

            setTimeout(() => {
                document.getElementById('successMessage').style.display = 'none';
            }, 3000);
        } else {
            alert(result.message || 'Failed to update profile');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        alert('An error occurred while updating your profile');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    }
}

async function changePassword(event) {
    event.preventDefault();

    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    const errorDiv = document.getElementById('passwordError');
    const errorText = document.getElementById('passwordErrorText');
    const successDiv = document.getElementById('passwordSuccess');
    const submitBtn = event.target.querySelector('button[type="submit"]');

    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';

    if (newPassword !== confirmPassword) {
        errorText.textContent = 'New passwords do not match';
        errorDiv.style.display = 'block';
        return;
    }

    if (newPassword.length < 6) {
        errorText.textContent = 'Password must be at least 6 characters long';
        errorDiv.style.display = 'block';
        return;
    }

    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-symbols-outlined">hourglass_empty</span> Updating...';

    try {
        const response = await fetch('../../api/users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'change_password',
                current_password: currentPassword,
                new_password: newPassword
            })
        });

        const result = await response.json();

        if (result.success) {
            successDiv.style.display = 'block';
            document.getElementById('passwordForm').reset();

            setTimeout(() => {
                successDiv.style.display = 'none';
            }, 3000);
        } else {
            errorText.textContent = result.message || 'Failed to update password';
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        console.error('Error changing password:', error);
        errorText.textContent = 'An error occurred while updating your password';
        errorDiv.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

async function loadUserProfile() {
    try {
        const response = await fetch('../../api/users.php?action=profile');
        const result = await response.json();

        if (result.success && result.user) {
            const user = result.user;
            
            const displayFirstName = document.getElementById('displayFirstName');
            const displayLastName = document.getElementById('displayLastName');
            const displayEmail = document.getElementById('displayEmail');
            const displayPhone = document.getElementById('displayPhone');
            
            const editFirstName = document.getElementById('editFirstName');
            const editLastName = document.getElementById('editLastName');
            const editEmail = document.getElementById('editEmail');
            const editPhone = document.getElementById('editPhone');
            
            if (displayFirstName) displayFirstName.textContent = user.first_name;
            if (displayLastName) displayLastName.textContent = user.last_name;
            if (displayEmail) displayEmail.textContent = user.email;
            if (displayPhone) displayPhone.textContent = user.phone_number || 'Not provided';
            
            if (editFirstName) editFirstName.value = user.first_name;
            if (editLastName) editLastName.value = user.last_name;
            if (editEmail) editEmail.value = user.email;
            if (editPhone) editPhone.value = user.phone_number || '';
            
            const headerName = document.querySelector('[data-user-fullname]');
            const headerEmail = document.querySelector('[data-user-email]');
            if (headerName) headerName.textContent = `${user.first_name} ${user.last_name}`;
            if (headerEmail) headerEmail.textContent = user.email;
        }
    } catch (error) {
        console.error('Error loading user profile:', error);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadUserProfile();
    
    const navLinks = document.querySelectorAll('.profile-nav-link[data-tab]');
    const tabContents = document.querySelectorAll('.tab-content');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetTab = this.getAttribute('data-tab');
            
            navLinks.forEach(nav => nav.classList.remove('active'));
            
            this.classList.add('active');
            
            tabContents.forEach(content => content.classList.remove('active'));
            
            const targetContent = document.getElementById(targetTab);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });
});
