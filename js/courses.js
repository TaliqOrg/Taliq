// Pagination state
let allCourses = [];
let currentPage = 1;
let coursesPerPage = 6;
let totalPages = 1;

// This function runs as soon as the page loads
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('course-container');
    if (!container) return;

    const limit = parseInt(container.getAttribute('data-limit')) || 0;
    
    // If limit is set (e.g., on home page), no pagination needed
    if (limit > 0) {
        fetchCourses(limit);
    } else {
        // Explore page - fetch all and paginate
        fetchCoursesWithPagination();
    }
});

// Fetch courses with pagination (for explore page)
function fetchCoursesWithPagination() {
    fetch('/taleeq/Taliq/api/courses.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.records && data.records.length > 0) {
                allCourses = data.records;
                totalPages = Math.ceil(allCourses.length / coursesPerPage);
                
                // Update results count
                const resultsCount = document.querySelector('.results-count');
                if (resultsCount) {
                    resultsCount.textContent = `Showing ${allCourses.length} courses`;
                }
                
                renderCourses();
                renderPagination();
            } else {
                const container = document.getElementById('course-container');
                container.innerHTML = '<p>No courses available right now.</p>';
                hidePagination();
            }
        })
        .catch(error => {
            console.error('Error fetching courses:', error);
            const container = document.getElementById('course-container');
            container.innerHTML = '<p>Error loading courses. Please try again.</p>';
        });
}

// Fetch courses with limit (for home page)
function fetchCourses(limit = 0) {
    let apiUrl = '/taleeq/Taliq/api/courses.php?action=list';
    if (limit > 0) {
        apiUrl += `&limit=${limit}`;
    }

    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('course-container');

            if (data.success && data.records && data.records.length > 0) {
                container.innerHTML = '';
                data.records.forEach(course => {
                    container.innerHTML += createCourseCard(course);
                });
            } else {
                container.innerHTML = '<p>No courses available right now.</p>';
            }
        })
        .catch(error => console.error('Error fetching courses:', error));
}

// Create HTML for a single course card
function createCourseCard(course) {
    const badgeClass = (course.CourseType === "course") ? 'badge-online' : 'badge-onsite';
    const PlaceOfCourse = (course.CourseType === "course") ? 'Online' : 'On-Site';
    const placeholder = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZTJlOGYwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNiIgZmlsbD0iIzY0NzQ4YiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
    const thumbnail = course.ThumbnailUrl || placeholder;

    // Determine courseId or workshopId based on type
    const courseId = course.CourseType === 'course' ? course.CourseId : null;
    const workshopId = course.CourseType === 'workshop' ? course.CourseId : null;

    return `
        <a href="/taleeq/Taliq/pages/course_details.html?id=${course.CourseId}&type=${course.CourseType}" class="card">
            <div class="card-image-container">
                <img class="card-img" src="${thumbnail}" alt="${course.Title}">
                <span class="badge ${badgeClass}">${PlaceOfCourse}</span>
            </div>
            <div class="card-content">
                <h3 class="card-title">${course.Title}</h3>
                <div class="rating">
                    <span class="material-symbols-outlined star-icon">star</span>
                    <span class="rating-score">${parseFloat(course.AverageRating || 0).toFixed(1)}</span>
                    <span class="rating-count">(${course.RatingCount || 0} ratings)</span>
                </div>
                <div class="card-footer">
                    <span class="price">${course.Price} SAR</span>
                    <button class="add-cart-btn" onclick="event.preventDefault(); event.stopPropagation(); quickAddToCart(${courseId}, ${workshopId}, ${course.Price});">
                        <span class="material-symbols-outlined">add_shopping_cart</span>
                    </button>
                </div>
            </div>
        </a>
    `;
}

// Render courses for current page
function renderCourses() {
    const container = document.getElementById('course-container');
    if (!container) return;

    const startIndex = (currentPage - 1) * coursesPerPage;
    const endIndex = startIndex + coursesPerPage;
    const coursesToShow = allCourses.slice(startIndex, endIndex);

    container.innerHTML = '';
    coursesToShow.forEach(course => {
        container.innerHTML += createCourseCard(course);
    });

    // Update results count with current range
    const resultsCount = document.querySelector('.results-count');
    if (resultsCount) {
        const showing = Math.min(endIndex, allCourses.length);
        resultsCount.textContent = `Showing ${startIndex + 1}-${showing} of ${allCourses.length} courses`;
    }

    // Scroll to top of courses section
    container.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Render pagination controls
function renderPagination() {
    const paginationContainer = document.querySelector('.pagination');
    if (!paginationContainer) return;

    let html = '';

    // Previous button
    html += `
        <button class="pagination-btn" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
            <span class="material-symbols-outlined">chevron_left</span>
        </button>
    `;

    // Page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

    // Adjust start if we're near the end
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    // First page + ellipsis
    if (startPage > 1) {
        html += `<button class="pagination-btn" onclick="goToPage(1)">1</button>`;
        if (startPage > 2) {
            html += `<span class="pagination-ellipsis">...</span>`;
        }
    }

    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        html += `
            <button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">
                ${i}
            </button>
        `;
    }

    // Last page + ellipsis
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<span class="pagination-ellipsis">...</span>`;
        }
        html += `<button class="pagination-btn" onclick="goToPage(${totalPages})">${totalPages}</button>`;
    }

    // Next button
    html += `
        <button class="pagination-btn" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
            <span class="material-symbols-outlined">chevron_right</span>
        </button>
    `;

    paginationContainer.innerHTML = html;
}

// Go to specific page
function goToPage(page) {
    if (page < 1 || page > totalPages || page === currentPage) return;
    currentPage = page;
    renderCourses();
    renderPagination();
}

// Hide pagination when no courses
function hidePagination() {
    const paginationContainer = document.querySelector('.pagination');
    if (paginationContainer) {
        paginationContainer.style.display = 'none';
    }
}
function openHelpModal() {
    document.getElementById('helpModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeHelpModal() {
    document.getElementById('helpModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

window.onclick = function(event) {
    const modal = document.getElementById('helpModal');
    if (event.target === modal) {
        closeHelpModal();
    }
}
