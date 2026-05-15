const params     = new URLSearchParams(window.location.search);
const courseId   = params.get('id');
const courseType = params.get('type') || 'course';

document.addEventListener('DOMContentLoaded', () => {
    if (!courseId) {
        window.location.href = 'manage_courses.html';
        return;
    }
    loadCourse();
    document.getElementById('edit-course-form').addEventListener('submit', handleSubmit);
});

async function loadCourse() {
    try {
        const response = await fetch(
            `../../api/courses.php?action=admin_get&id=${courseId}&type=${courseType}`
        );
        const result = await response.json();

        if (!result.success) {
            alert('Course not found.');
            window.location.href = 'manage_courses.html';
            return;
        }

        const c = result.course;
        document.getElementById('title').value        = c.Title        || '';
        document.getElementById('category').value     = c.CategoryId   || '';
        document.getElementById('description').value  = c.Description  || '';
        document.getElementById('price').value        = c.Price        || '';
        document.getElementById('duration').value     = c.DurationHours|| '';
        document.getElementById('level').value        = capitalize(c.Level) || '';
        document.getElementById('language').value     = c.Language     || '';
        document.getElementById('isPublished').checked = parseInt(c.IsPublished) === 1;
        document.querySelector('.section-subtitle strong').textContent = c.Title;
    } catch (err) {
        alert('Failed to load course data.');
    }
}

async function handleSubmit(e) {
    e.preventDefault();

    const btn    = document.getElementById('submit-btn');
    const msgBox = document.getElementById('form-message');

    btn.disabled         = true;
    btn.textContent      = 'Saving...';
    msgBox.style.display = 'none';

    const formData = new FormData(this);
    formData.append('action', 'update');
    formData.append('course_id', courseId);
    formData.append('course_type', courseType);

    try {
        const response = await fetch('../../api/courses.php', { method: 'POST', body: formData });
        const result   = await response.json();

        msgBox.style.display    = 'block';
        msgBox.style.background = result.success ? '#d1fae5' : '#fee2e2';
        msgBox.style.color      = result.success ? '#065f46' : '#991b1b';
        msgBox.style.border     = result.success ? '1px solid #6ee7b7' : '1px solid #fca5a5';
        msgBox.textContent      = result.message;

        if (result.success) {
            setTimeout(() => window.location.href = 'manage_courses.html', 1500);
        }
    } catch (err) {
        msgBox.style.display    = 'block';
        msgBox.style.background = '#fee2e2';
        msgBox.style.color      = '#991b1b';
        msgBox.style.border     = '1px solid #fca5a5';
        msgBox.textContent      = 'Something went wrong. Please try again.';
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '<span class="material-symbols-outlined">save</span> Save Changes';
    }
}

