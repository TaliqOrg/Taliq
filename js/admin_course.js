/*
 * Task 9:  Create New Course (Admin)
 * Author:  Fadhlallah Almohammed
 */

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('new-course-form');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
    e.preventDefault();

    const btn = document.getElementById('submit-btn');
    const msgBox = document.getElementById('form-message');

    btn.disabled = true;
    btn.textContent = 'Saving...';
    msgBox.style.display = 'none';

    const formData = new FormData(this);
    formData.append('action', 'create');

    try {
        const response = await fetch('../../api/courses.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        msgBox.style.display = 'block';
        msgBox.style.background = result.success ? '#d1fae5' : '#fee2e2';
        msgBox.style.color = result.success ? '#065f46' : '#991b1b';
        msgBox.style.border = result.success ? '1px solid #6ee7b7' : '1px solid #fca5a5';
        msgBox.textContent = result.message;

        if (result.success) {
            msgBox.style.display = 'block';
            msgBox.textContent = 'Course added successfully!';
            this.reset();
            setTimeout(() => {
                window.location.href = 'manage_courses.html';
            }, 1500);
        }
    } catch (err) {
        msgBox.style.display = 'block';
        msgBox.style.background = '#fee2e2';
        msgBox.style.color = '#991b1b';
        msgBox.style.border = '1px solid #fca5a5';
        msgBox.textContent = 'Something went wrong. Please try again.';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined">save</span> Save Course';
    }
    });
});
