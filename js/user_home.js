/**
 * @file user_home.js
 * @description User home dashboard controller.
 * Loads continue-learning course cards and recent purchases from cookies.
 * @version 1.0.0
 */

document.addEventListener('DOMContentLoaded', function() {
    loadContinueLearning();
    loadRecentPurchases();
});



/**
 * Loads and renders courses the user is currently progressing through.
 * @returns {Promise<void>}
 */
async function loadContinueLearning() {
    const container = document.getElementById('continue-learning-container');
    if (!container) return;
    
    try {

        let courses;
        if (window.progressSync) {
            courses = await window.progressSync.getAllEnrollments(true);
        } else {
            const response = await fetch('../../api/profile.php?action=courses');
            const result = await response.json();
            courses = result.success ? result.courses : [];
        }
        
        if (courses && courses.length > 0) {

            const inProgressCourses = courses
                .filter(c => c.CompletionStatus !== 'completed')
                .slice(0, 4);
            
            if (inProgressCourses.length > 0) {
                container.innerHTML = inProgressCourses.map(course => createContinueLearningCard(course)).join('');
            } else {
                container.innerHTML = createEmptyState('No courses in progress', 'Start learning by enrolling in a course!', 'school');
            }
        } else {
            container.innerHTML = createEmptyState('No enrolled courses', 'Browse our catalog and start learning today!', 'school');
        }
    } catch (error) {
        console.error('Error loading courses:', error);
        container.innerHTML = '<p class="error-text">Error loading courses</p>';
    }
}

/**
 * Creates an HTML card for a course in the continue-learning section.
 * @param {Object} course - The enrolled course data.
 * @returns {string} The card HTML markup.
 */
function createContinueLearningCard(course) {
    const placeholder = '../../images/placeholder-course.png';
    const thumbnail   = course.ThumbnailUrl || placeholder;
    const itemId      = course.CourseId || course.WorkshopId;
    const itemType    = course.ItemType;
    const badgeClass  = itemType === 'course' ? 'badge-online' : 'badge-onsite';
    const badgeText   = itemType === 'course' ? 'Online' : 'On-site';
    const linkUrl     = itemType === 'course'
        ? `course_player.html?course_id=${itemId}`
        : `../course_details.html?id=${itemId}&type=workshop`;

    let bottomSection;
    if (itemType === 'workshop') {
        const dateText = course.NextSessionDate
            ? new Date(course.NextSessionDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
            : 'Registered';
        bottomSection = `
            <div style="margin-top:0.75rem; display:flex; align-items:center; justify-content:space-between;">
                <span style="font-size:0.85rem; color:var(--on-surface-variant);">
                    <span class="material-symbols-outlined" style="font-size:1rem;vertical-align:middle;">event</span>
                    ${dateText}
                </span>
                <span style="font-size:0.8rem; font-weight:600; color:var(--primary);">View Details →</span>
            </div>`;
    } else {
        const progress = Math.round(course.ProgressPercentage || 0);
        bottomSection = `
            <div class="progress-container" style="margin-top:0.75rem;">
                <div class="progress-bar">
                    <div class="progress-fill" style="width:${progress}%;"></div>
                </div>
                <span class="progress-text">${progress}% Complete</span>
            </div>`;
    }

    return `
        <a href="${linkUrl}" class="card" data-course-id="${itemId}">
            <div class="card-image-container">
                <img src="${thumbnail}" alt="${course.Title}" class="card-img" onerror="this.src='${placeholder}'">
                <span class="badge ${badgeClass}">${badgeText}</span>
            </div>
            <div class="card-content">
                <h3 class="card-title">${course.Title}</h3>
                ${bottomSection}
            </div>
        </a>`;
}



/**
 * Loads recent purchase data from cookies and renders purchase cards.
 * @returns {Promise<void>}
 */
async function loadRecentPurchases() {
    const container = document.getElementById('recent-purchases-container');
    if (!container) return;
    

    const recentPurchases = getRecentPurchasesCookie();
    
    if (recentPurchases && recentPurchases.length > 0) {

        const ids = recentPurchases.map(p => {
            if (p.courseId) return 'c' + p.courseId;
            if (p.workshopId) return 'w' + p.workshopId;
            return null;
        }).filter(id => id !== null);
        
        if (ids.length === 0) {
            container.innerHTML = createEmptyState('No recent purchases', 'Your recently purchased courses will appear here.', 'shopping_bag');
            return;
        }
        

        try {
            const response = await fetch('../../api/courses.php?action=by_ids&ids=' + ids.join(','));
            const result = await response.json();
            
            if (result.success && result.records && result.records.length > 0) {
                container.innerHTML = result.records.slice(0, 4).map(course => createPurchaseCard(course)).join('');
            } else {
                container.innerHTML = createEmptyState('No recent purchases', 'Your recently purchased courses will appear here.', 'shopping_bag');
            }
        } catch (error) {
            console.error('Error loading recent purchases:', error);
            container.innerHTML = createEmptyState('No recent purchases', 'Your recently purchased courses will appear here.', 'shopping_bag');
        }
    } else {
        container.innerHTML = createEmptyState('No recent purchases', 'Your recently purchased courses will appear here.', 'shopping_bag');
    }
}

/**
 * Creates an HTML card for a recent purchase item.
 * @param {Object} course - The course/workshop data.
 * @returns {string} The card HTML markup.
 */
function createPurchaseCard(course) {
    const placeholder = '../../images/placeholder-course.png';
    const thumbnail = course.ThumbnailUrl || placeholder;
    const itemId = course.CourseId || course.WorkshopId;
    const itemType = course.CourseType || 'course';
    const badgeClass = itemType === 'course' ? 'badge-online' : 'badge-onsite';
    const badgeText = itemType === 'course' ? 'Online' : 'On-site';
    
    return `
        <a href="../course_details.html?id=${itemId}&type=${itemType}" class="card">
            <div class="card-image-container">
                <img src="${thumbnail}" alt="${course.Title}" class="card-img" onerror="this.src='${placeholder}'">
                <span class="badge ${badgeClass}">${badgeText}</span>
            </div>
            <div class="card-content">
                <h3 class="card-title">${course.Title}</h3>
            </div>
        </a>
    `;
}



/**
 * Reads the recent_purchases cookie and returns the parsed array.
 * @returns {Array<Object>} The array of recent purchase objects.
 */
function getRecentPurchasesCookie() {
    const cookie = document.cookie
        .split('; ')
        .find(row => row.startsWith('recent_purchases='));
    
    if (cookie) {
        try {
            return JSON.parse(decodeURIComponent(cookie.split('=')[1]));
        } catch (e) {
            return [];
        }
    }
    return [];
}

/**
 * Stores the recent purchases array in a 30-day cookie (max 10 items).
 * @param {Array<Object>} purchases - The purchases to persist.
 */
function setRecentPurchasesCookie(purchases) {
    // Keep only last 10 purchases, expires in 30 days
    const limitedPurchases = purchases.slice(0, 10);
    const expires = new Date();
    expires.setDate(expires.getDate() + 30);
    document.cookie = `recent_purchases=${encodeURIComponent(JSON.stringify(limitedPurchases))}; expires=${expires.toUTCString()}; path=/`;
}

/**
 * Adds a newly purchased item to the recent purchases cookie.
 * @param {number|null} courseId - The course ID, or null.
 * @param {number|null} workshopId - The workshop ID, or null.
 */
function addToRecentPurchases(courseId, workshopId) {
    const purchases = getRecentPurchasesCookie();
    const newItem = { courseId, workshopId, timestamp: Date.now() };
    

    const filtered = purchases.filter(p => 
        !(p.courseId === courseId && p.workshopId === workshopId)
    );
    

    filtered.unshift(newItem);
    
    setRecentPurchasesCookie(filtered);
}



/**
 * Generates a generic empty state HTML block.
 * @param {string} title - The heading text.
 * @param {string} message - The descriptive text.
 * @param {string} icon - The Material icon name.
 * @returns {string} The empty state HTML markup.
 */
function createEmptyState(title, message, icon) {
    return `
        <div class="empty-state-small">
            <span class="material-symbols-outlined">${icon}</span>
            <div>
                <h4>${title}</h4>
                <p>${message}</p>
            </div>
        </div>
    `;
}
