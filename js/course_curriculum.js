let currentLessonsData = [];

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const courseId = urlParams.get('id');

    if (!courseId) {
        alert("No Course ID provided.");
        window.location.href = 'manage_courses.html';
        return;
    }

    loadCourseDetails(courseId);
    loadLessons(courseId);


    document.getElementById('lesson-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveNewLesson(courseId);
    });
});

async function loadCourseDetails(courseId) {
    try {
        const response = await fetch(`../../api/courses.php?action=admin_get&id=${courseId}&type=course`);
        const result = await response.json();
        if (result.success) document.getElementById('course-title-display').textContent = result.course.Title;
    } catch (err) { console.error("Failed to load details", err); }
}

async function loadLessons(courseId) {
    const container = document.getElementById('lessons-container');
    container.innerHTML = '<p style="padding:1rem;">Loading lessons...</p>';

    try {
        const response = await fetch(`../../api/courses.php?action=lessons&id=${courseId}&type=course`);
        const result = await response.json();

        if (result.success && result.course.lessons?.length > 0) {
            currentLessonsData = result.course.lessons;

            container.innerHTML = currentLessonsData.map(lesson => createLessonCard(lesson)).join('');
            initDragAndDrop();
        } else {
            currentLessonsData = [];
            container.innerHTML = '<p style="padding:1rem;color:#6b7280;">No lessons found. Create one!</p>';
        }
    } catch (err) {
        container.innerHTML = '<p style="color:#ef4444;">Failed to load lessons.</p>';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString().replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function createLessonCard(lesson) {
    const icon = lesson.ContentType === 'document' ? 'article' : (lesson.ContentType === 'quiz' ? 'quiz' : 'play_circle');

    return `
        <div class="lesson-item" id="lesson-card-${lesson.LessonId}" data-id="${lesson.LessonId}" draggable="true" style="flex-wrap: wrap;">
            
            <div class="drag-handle" title="Drag to reorder"><span class="material-symbols-outlined">drag_indicator</span></div>
            <div class="lesson-icon-wrapper icon-video">
                <span class="material-symbols-outlined">${icon}</span>
            </div>
            <div class="lesson-details">
                <h4 class="lesson-title"><span class="order-number">${lesson.SortOrder}</span>. ${escapeHtml(lesson.Title)}</h4>
                <div class="lesson-meta">
                    <span><span class="material-symbols-outlined">schedule</span> ${lesson.Duration} mins</span>
                </div>
            </div>
            <div class="lesson-actions">
                <button type="button" class="btn-icon-small" onclick="toggleEdit(${lesson.LessonId})" title="Edit Lesson">
                    <span class="material-symbols-outlined">edit</span>
                </button>
                <button type="button" class="btn-icon-small danger" onclick="deleteLesson(${lesson.LessonId})" title="Delete Lesson">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </div>

            <div class="inline-edit-area" id="edit-area-${lesson.LessonId}" style="display: none; width: 100%; flex-basis: 100%; padding-top: 15px; border-top: 1px solid #e5e7eb; margin-top: 15px;">
                <form id="edit-form-${lesson.LessonId}" class="form-group-list" onsubmit="submitInlineEdit(event, ${lesson.LessonId})">
                    <input type="hidden" name="LessonId" value="${lesson.LessonId}">
                    <input type="hidden" name="SortOrder" value="${lesson.SortOrder}">
                    
                    <div>
                        <label class="checkout-label">Lesson Title</label>
                        <input type="text" name="Title" class="checkout-input" value="${escapeHtml(lesson.Title)}" required>
                    </div>

                    <div>
                        <label class="checkout-label">Description</label>
                        <textarea rows="3" name="Description" class="checkout-input" required>${escapeHtml(lesson.Description)}</textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <label class="checkout-label">Content Type</label>
                            <select name="ContentType" class="checkout-input" required>
                                <option value="video" ${lesson.ContentType === 'video' ? 'selected' : ''}>Video</option>
                                <option value="document" ${lesson.ContentType === 'document' ? 'selected' : ''}>Document</option>
                                <option value="quiz" ${lesson.ContentType === 'quiz' ? 'selected' : ''}>Quiz</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label class="checkout-label">Duration (Minutes)</label>
                            <input type="number" name="Duration" class="checkout-input" value="${lesson.Duration}" required>
                        </div>
                    </div>

                    <div>
                        <label class="checkout-label">Content URL</label>
                        <input type="url" name="ContentUrl" class="checkout-input" value="${escapeHtml(lesson.ContentUrl)}" required>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined">save</span> Update</button>
                        <button type="button" class="btn" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db;" onclick="toggleEdit(${lesson.LessonId})">Cancel</button>
                    </div>
                </form>
            </div>

        </div>
    `;
}

// --- ACCORDION TOGGLE LOGIC ---
function toggleEdit(lessonId) {
    const editArea = document.getElementById(`edit-area-${lessonId}`);
    const card = document.getElementById(`lesson-card-${lessonId}`);

    if (editArea.style.display === 'none') {
        editArea.style.display = 'block';
        card.draggable = false; // Disable dragging while editing so it doesn't glitch
    } else {
        editArea.style.display = 'none';
        card.draggable = true; // Re-enable dragging when closed
    }
}

// --- SUBMIT INLINE EDIT ---
async function submitInlineEdit(e, lessonId) {
    e.preventDefault();
    const form = document.getElementById(`edit-form-${lessonId}`);
    const formData = new FormData(form);
    const courseId = new URLSearchParams(window.location.search).get('id');

    formData.append('action', 'update_lesson');
    formData.append('course_id', courseId);

    try {
        const response = await fetch('../../api/lessons.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            loadLessons(courseId);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (err) { alert('Network error while updating lesson.'); }
}

// --- CREATE NEW LESSON ---
async function saveNewLesson(courseId) {
    const form = document.getElementById('lesson-form');
    const formData = new FormData(form);

    formData.append('action', 'create_lesson');
    formData.append('course_id', courseId);

    formData.set('SortOrder', currentLessonsData.length + 1);

    try {
        const response = await fetch('../../api/lessons.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            form.reset();
            loadLessons(courseId);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (err) { alert('Network error while saving lesson.'); }
}

async function deleteLesson(lessonId) {
    if (!confirm("Are you sure you want to delete this lesson?")) return;
    const formData = new FormData();
    formData.append('action', 'delete_lesson');
    formData.append('lesson_id', lessonId);

    try {
        const response = await fetch('../../api/lessons.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) loadLessons(new URLSearchParams(window.location.search).get('id'));
        else alert(result.message);
    } catch (err) { alert('Failed to delete lesson.'); }
}

// --- DRAG AND DROP REORDERING ---
function initDragAndDrop() {
    const container = document.getElementById('lessons-container');
    const draggables = document.querySelectorAll('.lesson-item');

    draggables.forEach(draggable => {
        draggable.addEventListener('dragstart', () => draggable.classList.add('dragging'));
        draggable.addEventListener('dragend', () => {
            draggable.classList.remove('dragging');
            saveNewOrder();
        });
    });

    container.addEventListener('dragover', e => {
        e.preventDefault();
        const afterElement = getDragAfterElement(container, e.clientY);
        const draggable = document.querySelector('.dragging');
        if (afterElement == null) {
            container.appendChild(draggable);
        } else {
            container.insertBefore(draggable, afterElement);
        }
    });
}

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.lesson-item:not(.dragging)')];
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) return { offset: offset, element: child };
        else return closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

async function saveNewOrder() {
    const items = document.querySelectorAll('.lesson-item');
    const newOrders = [];


    items.forEach((item, index) => {
        const sortOrder = index + 1;
        item.querySelector('.order-number').textContent = sortOrder;
        newOrders.push({ lesson_id: item.dataset.id, sort_order: sortOrder });
    });

    const formData = new FormData();
    formData.append('action', 'reorder_lessons');
    formData.append('orders', JSON.stringify(newOrders));

    try {
        await fetch('../../api/lessons.php', { method: 'POST', body: formData });
    } catch (err) {
        console.error('Failed to save new order to database', err);
    }
}