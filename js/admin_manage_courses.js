/*
 * Task 10: Manage Courses (Admin)
 * Author:  Fadhlallah Almohammed
 */

let allCourses = [];

document.addEventListener('DOMContentLoaded', () => {
    loadCourses();
    document.getElementById('search-input')?.addEventListener('input', applyFilters);
    document.getElementById('status-filter')?.addEventListener('change', applyFilters);
    document.getElementById('type-filter')?.addEventListener('change', applyFilters);
});

// Task 10 | Author: Fadhlallah Almohammed
async function loadCourses() {
    const container = document.getElementById('courses-list');
    if (!container) return;
    
    container.innerHTML = `
        <div class="ui-state-box">
            <span class="material-symbols-outlined spin-icon">sync</span>
            <p>Loading courses...</p>
        </div>`;

    try {
        const response = await fetch('../../api/courses.php?action=admin_list');
        const result   = await response.json();

        if (result.success && result.records.length > 0) {
            allCourses = result.records;
            renderCourses(allCourses);
        } else {
            container.innerHTML = `
                <div class="ui-state-box empty">
                    <span class="material-symbols-outlined state-icon">inventory_2</span>
                    <h3>No Courses Found</h3>
                    <p>You haven't created any courses yet.</p>
                </div>`;
        }
    } catch (err) {
        container.innerHTML = `
            <div class="ui-state-box error">
                <span class="material-symbols-outlined state-icon">error</span>
                <p>Failed to load courses. Please check your connection.</p>
            </div>`;
    }
}

// Task 10 | Author: Fadhlallah Almohammed
function renderCourses(courses) {
    const container = document.getElementById('courses-list');
    if (!courses.length) {
        container.innerHTML = `
            <div class="ui-state-box empty">
                <span class="material-symbols-outlined state-icon">search_off</span>
                <h3>No Matches Found</h3>
                <p>No courses match your current search or filters.</p>
            </div>`;
        return;
    }
    container.innerHTML = courses.map(c => createCard(c)).join('');
}

// Task 10 | Author: Fadhlallah Almohammed
function createCard(course) {
    const isPublished = parseInt(course.IsPublished) === 1;
    const badge       = isPublished
        ? '<span class="badge-published">Published</span>'
        : '<span class="badge-draft">Draft</span>';
    let thumbnail = course.ThumbnailUrl || '../../images/placeholder.png';

    if (thumbnail.startsWith('/Taliq/')) {
        thumbnail = '/taleeq' + thumbnail;
    }
    const typeLabel   = course.CourseType === 'workshop' ? ' (Workshop)' : '';
    const editUrl     = `edit_course_details.html?id=${course.CourseId}&type=${course.CourseType}`;
    const curriculumUrl = `course_curriculum.html?id=${course.CourseId}`;

    return `
        <div class="admin-list-card" data-id="${course.CourseId}" data-type="${course.CourseType}">
            <img src="${thumbnail}" alt="Thumbnail" class="admin-card-img" onerror="this.src='../../images/placeholder.png'">
            <div class="admin-card-info">
                <h3 class="admin-card-title">
                    ${escapeHtml(course.Title)}${typeLabel} ${badge}
                </h3>
                <div class="admin-card-meta">
                    <span class="meta-item">
                        <span class="material-symbols-outlined">payments</span> ${course.Price} SAR
                    </span>
                    <span class="meta-item">
                        <span class="material-symbols-outlined">school</span> ${capitalize(course.Level || 'All')}
                    </span>
                </div>
            </div>
            <div class="admin-card-actions">
                <a href="${editUrl}" class="action-btn" title="Edit Metadata">
                    <span class="material-symbols-outlined">edit</span> Edit Course
                </a>
                <a href="${curriculumUrl}" class="action-btn primary" title="Manage Lessons">
                    <span class="material-symbols-outlined">view_list</span> Curriculum
                </a>
                <button class="action-btn danger" onclick="deleteCourse(${course.CourseId}, '${course.CourseType}', this)" title="Delete Course">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </div>
        </div>`;
}

// Task 10 | Author: Fadhlallah Almohammed
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

// Task 10 | Author: Fadhlallah Almohammed
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