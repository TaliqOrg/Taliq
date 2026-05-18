/*
 * Task 3:  Course Listing & Filters
 * Task 14: Help Modal
 * Author:  Abdulhadi Shamea
 */

// Pagination & filter state
let allCourses = [];
let filteredCourses = [];
let currentPage = 1;
let coursesPerPage = 6;
let totalPages = 1;

// CategoryId mapping: checkbox value → DB CategoryId
const CATEGORY_MAP = {
    'technology':  3,
    'business':    2,
    'design':      4,
    'data-science': 1
};

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('course-container');
    if (!container) return;

    const limit = parseInt(container.getAttribute('data-limit')) || 0;

    if (limit > 0) {
        fetchCourses(limit);
    } else {
        fetchCoursesWithPagination();
        attachFilterListeners();
    }
});

// ══════════════════════════════════════════════════════════════════════════════
// FETCH
// ══════════════════════════════════════════════════════════════════════════════

// Task 3 | Author: Abdulhadi Shamea
function fetchCoursesWithPagination() {
    fetch('/taleeq/Taliq/api/courses.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.records && data.records.length > 0) {
                allCourses = data.records;

                // If user came from the header search bar, pre-filter by the query
                const urlParams = new URLSearchParams(window.location.search);
                const searchQuery = urlParams.get('q');
                if (searchQuery) {
                    filteredCourses = allCourses.filter(c =>
                        c.Title.toLowerCase().includes(searchQuery.toLowerCase())
                    );
                } else {
                    filteredCourses = allCourses;
                }

                totalPages = Math.ceil(filteredCourses.length / coursesPerPage);
                updateResultsCount();
                renderCourses();
                renderPagination();
            } else {
                document.getElementById('course-container').innerHTML = '<p>No courses available right now.</p>';
                hidePagination();
            }
        })
        .catch(() => {
            document.getElementById('course-container').innerHTML = '<p>Error loading courses. Please try again.</p>';
        });
}

// Task 3 | Author: Abdulhadi Shamea
function fetchCourses(limit) {
    let apiUrl = '/taleeq/Taliq/api/courses.php?action=list';
    if (limit > 0) apiUrl += `&limit=${limit}`;

    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('course-container');
            if (data.success && data.records && data.records.length > 0) {
                container.innerHTML = data.records.map(course => createCourseCard(course)).join('');

            } else {
                container.innerHTML = '<p>No courses available right now.</p>';
            }
        })
        .catch(error => console.error('Error fetching courses:', error));
}

// ══════════════════════════════════════════════════════════════════════════════
// FILTERS & SORT
// ══════════════════════════════════════════════════════════════════════════════

// Task 3 | Author: Abdulhadi Shamea
function attachFilterListeners() {
    document.querySelectorAll('.filters-sidebar input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', applyFilters);
    });
    const sortSelect = document.getElementById('sort');
    if (sortSelect) sortSelect.addEventListener('change', applyFilters);
}

// Task 3 | Author: Abdulhadi Shamea
function applyFilters() {
    const checkedCategories = getCheckedValues('category');
    const checkedTypes      = getCheckedValues('type');
    const checkedLevels     = getCheckedValues('level');
    const checkedPrices     = getCheckedValues('price');
    const checkedLanguages  = getCheckedValues('language');
    const sortValue         = document.getElementById('sort')?.value || 'popular';

    // "all" checked (or nothing checked) means no filter on that group
    const categoryIds = (checkedCategories.includes('all') || checkedCategories.length === 0)
        ? null
        : checkedCategories.map(v => CATEGORY_MAP[v]).filter(Boolean);

    const typeValues = (checkedTypes.includes('all') || checkedTypes.length === 0)
        ? null
        : checkedTypes.map(v => v === 'online' ? 'course' : 'workshop');

    const levelValues = checkedLevels.length === 0 ? null : checkedLevels;

    const langValues = checkedLanguages.length === 0
        ? null
        : checkedLanguages.map(l => l.toLowerCase());

    filteredCourses = allCourses.filter(course => {
        if (categoryIds && !categoryIds.includes(parseInt(course.CategoryId))) return false;
        if (typeValues  && !typeValues.includes(course.CourseType))             return false;
        if (levelValues && !levelValues.includes((course.Level || '').toLowerCase())) return false;
        if (langValues  && !langValues.includes((course.Language || '').toLowerCase())) return false;

        if (checkedPrices.length > 0) {
            const price = parseFloat(course.Price);
            const inRange = checkedPrices.some(range => {
                if (range === '0-500')    return price <= 500;
                if (range === '500-1000') return price > 500 && price <= 1000;
                if (range === '1000+')    return price > 1000;
                return false;
            });
            if (!inRange) return false;
        }

        return true;
    });

    // Sort
    switch (sortValue) {
        case 'price-low':
            filteredCourses.sort((a, b) => parseFloat(a.Price) - parseFloat(b.Price));
            break;
        case 'price-high':
            filteredCourses.sort((a, b) => parseFloat(b.Price) - parseFloat(a.Price));
            break;
        case 'rating':
            filteredCourses.sort((a, b) => parseFloat(b.AverageRating || 0) - parseFloat(a.AverageRating || 0));
            break;
        case 'newest':
            filteredCourses.sort((a, b) => new Date(b.CreatedAt) - new Date(a.CreatedAt));
            break;
        // 'popular' keeps original API order
    }

    currentPage = 1;
    totalPages = Math.ceil(filteredCourses.length / coursesPerPage) || 1;
    updateResultsCount();
    renderCourses();
    renderPagination();
}

// Task 3 | Author: Abdulhadi Shamea
function getCheckedValues(name) {
    return [...document.querySelectorAll(`input[name="${name}"]:checked`)].map(cb => cb.value);
}

// ══════════════════════════════════════════════════════════════════════════════
// RENDER
// ══════════════════════════════════════════════════════════════════════════════

// Task 3 | Author: Abdulhadi Shamea
function renderCourses() {
    const container = document.getElementById('course-container');
    if (!container) return;

    if (filteredCourses.length === 0) {
        container.innerHTML = '<p style="grid-column:1/-1;padding:2rem;text-align:center;">No courses match your filters.</p>';
        return;
    }

    const startIndex = (currentPage - 1) * coursesPerPage;
    const endIndex   = startIndex + coursesPerPage;

    container.innerHTML = filteredCourses
        .slice(startIndex, endIndex)
        .map(course => createCourseCard(course))
        .join('');

    container.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Task 3 | Author: Abdulhadi Shamea
function updateResultsCount() {
    const el = document.querySelector('.results-count');
    if (!el) return;
    const total = filteredCourses.length;
    const start = Math.min((currentPage - 1) * coursesPerPage + 1, total);
    const end   = Math.min(currentPage * coursesPerPage, total);
    el.textContent = total > 0
        ? `Showing ${start}-${end} of ${total} courses`
        : 'No courses found';
}

// Task 3 | Author: Abdulhadi Shamea
function createCourseCard(course) {
    const badgeClass    = course.CourseType === 'course' ? 'badge-online' : 'badge-onsite';
    const badgeText     = course.CourseType === 'course' ? 'Online' : 'On-Site';
    const placeholder   = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZTJlOGYwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNiIgZmlsbD0iIzY0NzQ4YiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
    const thumbnail     = course.ThumbnailUrl || placeholder;
    const courseId      = course.CourseType === 'course'   ? course.CourseId : null;
    const workshopId    = course.CourseType === 'workshop' ? course.CourseId : null;

    return `
        <a href="/taleeq/Taliq/pages/course_details.html?id=${course.CourseId}&type=${course.CourseType}" class="card">
            <div class="card-image-container">
                <img class="card-img" src="${thumbnail}" alt="${course.Title}">
                <span class="badge ${badgeClass}">${badgeText}</span>
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

// ══════════════════════════════════════════════════════════════════════════════
// PAGINATION
// ══════════════════════════════════════════════════════════════════════════════

// Task 3 | Author: Abdulhadi Shamea
function renderPagination() {
    const container = document.querySelector('.pagination');
    if (!container) return;

    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = `
        <button class="pagination-btn" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
            <span class="material-symbols-outlined">chevron_left</span>
        </button>
    `;

    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage   = Math.min(totalPages, startPage + maxVisible - 1);
    if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }

    if (startPage > 1) {
        html += `<button class="pagination-btn" onclick="goToPage(1)">1</button>`;
        if (startPage > 2) html += `<span class="pagination-ellipsis">...</span>`;
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += `<span class="pagination-ellipsis">...</span>`;
        html += `<button class="pagination-btn" onclick="goToPage(${totalPages})">${totalPages}</button>`;
    }

    html += `
        <button class="pagination-btn" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
            <span class="material-symbols-outlined">chevron_right</span>
        </button>
    `;

    container.innerHTML = html;
}

// Task 3 | Author: Abdulhadi Shamea
function goToPage(page) {
    if (page < 1 || page > totalPages || page === currentPage) return;
    currentPage = page;
    updateResultsCount();
    renderCourses();
    renderPagination();
}

// Task 3 | Author: Abdulhadi Shamea
function hidePagination() {
    const container = document.querySelector('.pagination');
    if (container) container.style.display = 'none';
}

// Task 14 | Author: Abdulhadi Shamea
function openHelpModal() {
    document.getElementById('helpModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Task 14 | Author: Abdulhadi Shamea
function closeHelpModal() {
    document.getElementById('helpModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

window.onclick = function (event) {
    const modal = document.getElementById('helpModal');
    if (event.target === modal) closeHelpModal();
};
