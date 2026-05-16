/**
 * Manage Courses Page Controller
 * Handles fetching, rendering, and state management for the course list.
 */

document.addEventListener('DOMContentLoaded', () => {

    const CourseManager = {

        container: document.querySelector('.admin-list-container'),

        // to store all list of courses
        allCourses: [],

        init: async function () {
            if (!this.container) return;

            this.setLoadingState();
            await this.loadCourses();

            this.container.addEventListener('click', (e) => {

                // Check if what clicked was the delete button
                const deleteBtn = e.target.closest('.action-btn.danger');

                if (deleteBtn) {
                    // Get the ID
                    const courseId = deleteBtn.getAttribute('data-id');
                    this.deleteCourse(courseId);
                }
            });

            // Filter Event Listeners
            const searchInput = document.querySelector('.filter-input');
            const statusSelect = document.querySelectorAll('.filter-select')[0];
            const typeSelect = document.querySelectorAll('.filter-select')[1];

            // for typing in the search box
            if (searchInput) searchInput.addEventListener('input', () => this.applyFilters());

            // for changing the dropdowns
            if (statusSelect) statusSelect.addEventListener('change', () => this.applyFilters());
            if (typeSelect) typeSelect.addEventListener('change', () => this.applyFilters());

        },

        loadCourses: async function () {
            try {
                const response = await fetch('../../api/courses.php', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    
                    this.allCourses = result.data;
                    this.render(this.allCourses);
                } 
                else {
                    this.setEmptyState();
                }
            } catch (error) {
                console.error('Failed to fetch courses:', error);
                this.setErrorState();
            }
        },

        // filter logic
        applyFilters: function() {

            const searchText = document.querySelector('.filter-input').value.toLowerCase();
            const statusVal = document.querySelectorAll('.filter-select')[0].value;
            const typeVal = document.querySelectorAll('.filter-select')[1].value;


            const filteredData = this.allCourses.filter(course => {

                const matchesSearch = course.Title.toLowerCase().includes(searchText);
                
                let matchesStatus = true;
                if (statusVal === 'published') matchesStatus = course.IsPublished == 1;
                if (statusVal === 'draft') matchesStatus = course.IsPublished == 0;

                let matchesType = true;
                if (typeVal === 'workshop') matchesType = false; 

                return matchesSearch && matchesStatus && matchesType;
            });

            if (filteredData.length > 0) {
                this.render(filteredData);
            } else {
                this.container.innerHTML = `
                    <div class="ui-state-box empty">
                        <span class="material-symbols-outlined state-icon">search_off</span>
                        <h3>No Matches Found</h3>
                        <p>No courses match your current search or filters.</p>
                    </div>`;
            }
        },

        render: function (courses) {
            this.container.innerHTML = '';
            const fragment = document.createDocumentFragment();

            courses.forEach(course => {
                const isPublished = course.IsPublished == 1;
                const statusText = isPublished ? 'Published' : 'Draft';
                const statusClass = isPublished ? 'badge-published' : 'badge-draft';

                const card = document.createElement('div');
                card.className = 'admin-list-card';


                card.innerHTML = `
                    <img src="../../${course.ThumbnailUrl || 'images/default.png'}" alt="Course Thumbnail" class="admin-card-img">

                    <div class="admin-card-info">
                        <h3 class="admin-card-title">
                            ${course.Title}
                            <span class="${statusClass}">${statusText}</span>
                        </h3>
                        <div class="admin-card-meta">
                            <span class="meta-item"><span class="material-symbols-outlined">category</span> Course</span>
                            <span class="meta-item"><span class="material-symbols-outlined">payments</span> ${course.Price} SAR</span>
                            <span class="meta-item"><span class="material-symbols-outlined">library_books</span> 0 Lessons</span>
                        </div>
                    </div>

                    <div class="admin-card-actions">
                        <a href="edit_course_details.html?id=${course.CourseId}" class="action-btn" title="Edit Metadata">
                            <span class="material-symbols-outlined">edit</span> Edit Course
                        </a>
                        <a href="course_curriculum.html?id=${course.CourseId}" class="action-btn primary" title="Manage Lessons">
                            <span class="material-symbols-outlined">view_list</span> Curriculum
                        </a>
                        <button class="action-btn danger" title="Delete Course" data-id="${course.CourseId}">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                `;

                fragment.appendChild(card);
            });

            this.container.appendChild(fragment);
        },

        setLoadingState: function () {
            this.container.innerHTML = `
                <div class="ui-state-box">
                    <span class="material-symbols-outlined spin-icon">sync</span>
                    <p>Loading courses...</p>
                </div>`;
        },

        setEmptyState: function () {
            this.container.innerHTML = `
                <div class="ui-state-box empty">
                    <span class="material-symbols-outlined state-icon">inventory_2</span>
                    <h3>No Courses Found</h3>
                    <p>You haven't created any courses yet.</p>
                </div>`;
        },

        setErrorState: function () {
            this.container.innerHTML = `
                <div class="ui-state-box error">
                    <span class="material-symbols-outlined state-icon">error</span>
                    <p>Failed to load courses. Please check your connection or contact support.</p>
                </div>`;
        },

        deleteCourse: async function(id) {

            // Confirm deletion
            if (!confirm('Are you sure you want to delete this course? This cannot be undone.')) {
                return;
            }

            try {

                // Send the DELETE request with the ID
                const response = await fetch(`../../api/courses.php?id=${id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json' }
                });

                const result = await response.json();
                
                if (result.success) {

                    // Reload the list after deletion
                    this.setLoadingState();
                    await this.loadCourses();

                }
                else {
                    alert('Error: ' + result.message);
                }
            }
            catch (error) {
                console.error('Failed to delete course:', error);
                alert('A network error occurred while trying to delete.');
            }
        },
    };

    CourseManager.init();
});