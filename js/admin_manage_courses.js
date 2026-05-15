let allCourses = [];

document.addEventListener('DOMContentLoaded', () => {
    loadCourses();

    document.getElementById('search-input').addEventListener('input', applyFilters);
    document.getElementById('status-filter').addEventListener('change', applyFilters);
    document.getElementById('type-filter').addEventListener('change', applyFilters);
});

async function loadCourses() {
    const container = document.getElementById('courses-list');
    container.innerHTML = '<p style="padding:1rem;color:#6b7280;">Loading...</p>';

    try {
        const response = await fetch('../../api/courses.php?action=admin_list');
        const result   = await response.json();

        if (result.success) {
            allCourses = result.records;
            renderCourses(allCourses);
        } else {
            container.innerHTML = '<p style="padding:1rem;color:#ef4444;">Failed to load courses.</p>';
        }
    } catch (err) {
        container.innerHTML = '<p style="padding:1rem;color:#ef4444;">Something went wrong.</p>';
    }
}

function renderCourses(courses) {
    const container = document.getElementById('courses-list');
    if (!courses.length) {
        container.innerHTML = '<p style="padding:1rem;color:#6b7280;">No courses found.</p>';
        return;
    }
    container.innerHTML = courses.map(c => createCard(c)).join('');
}

function createCard(course) {
    const isPublished = parseInt(course.IsPublished) === 1;
    const badge       = isPublished
        ? '<span class="badge-published">Published</span>'
        : '<span class="badge-draft">Draft</span>';
    const thumbnail   = course.ThumbnailUrl || '../../images/placeholder.png';
    const typeLabel   = course.CourseType === 'workshop' ? ' (Workshop)' : '';
    const editUrl     = `edit_course_details.html?id=${course.CourseId}&type=${course.CourseType}`;

    return `
        <div class="admin-list-card" data-id="${course.CourseId}" data-type="${course.CourseType}">
            <img src="${thumbnail}" alt="Thumbnail" class="admin-card-img"
                 onerror="this.src='../../images/placeholder.png'">
            <div class="admin-card-info">
                <h3 class="admin-card-title">
                    ${escapeHtml(course.Title)}${typeLabel} ${badge}
                </h3>
                <div class="admin-card-meta">
                    <span class="meta-item">
                        <span class="material-symbols-outlined">payments</span> ${course.Price} SAR
                    </span>
                    <span class="meta-item">
                        <span class="material-symbols-outlined">school</span> ${capitalize(course.Level)}
                    </span>
                </div>
            </div>
            <div class="admin-card-actions">
                <a href="${editUrl}" class="action-btn">
                    <span class="material-symbols-outlined">edit</span> Edit Course
                </a>
                <button class="action-btn danger"
                        onclick="deleteCourse(${course.CourseId}, '${course.CourseType}', this)">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </div>
        </div>`;
}

function applyFilters() {
    const query  = document.getElementById('search-input').value.toLowerCase();
    const status = document.getElementById('status-filter').value;
    const type   = document.getElementById('type-filter').value;

    const filtered = allCourses.filter(course => {
        const matchSearch = course.Title.toLowerCase().includes(query);
        const matchStatus = status === 'all'
            ? true
            : status === 'published'
                ? parseInt(course.IsPublished) === 1
                : parseInt(course.IsPublished) === 0;
        const matchType = type === 'all' ? true : course.CourseType === type;
        return matchSearch && matchStatus && matchType;
    });

    renderCourses(filtered);
}

async function deleteCourse(courseId, courseType, btn) {
    if (!confirm('Are you sure you want to delete this course? This cannot be undone.')) return;

    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('course_id', courseId);
        formData.append('course_type', courseType);

        const response = await fetch('../../api/courses.php', { method: 'POST', body: formData });
        const result   = await response.json();

        if (result.success) {
            btn.closest('.admin-list-card').remove();
            allCourses = allCourses.filter(
                c => !(c.CourseId == courseId && c.CourseType === courseType)
            );
        } else {
            alert(result.message);
            btn.disabled = false;
        }
    } catch (err) {
        alert('Something went wrong.');
        btn.disabled = false;
    }
}

