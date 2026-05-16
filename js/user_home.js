// User Home Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    loadContinueLearning();
    loadRecentPurchases();
});

// ══════════════════════════════════════════════════════════════════════════════
// CONTINUE LEARNING SECTION
// ══════════════════════════════════════════════════════════════════════════════

async function loadContinueLearning() {
    const container = document.getElementById('continue-learning-container');
    if (!container) return;
    
    try {
        // Use progress sync module if available
        let courses;
        if (window.progressSync) {
            courses = await window.progressSync.getAllEnrollments(true);
        } else {
            const response = await fetch('../../api/profile.php?action=courses');
            const result = await response.json();
            courses = result.success ? result.courses : [];
        }
        
        if (courses && courses.length > 0) {
            // Filter to show only in-progress courses (not completed), limit to 4
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

// ══════════════════════════════════════════════════════════════════════════════
// RECENT PURCHASES SECTION (Using Cookies)
// ══════════════════════════════════════════════════════════════════════════════

async function loadRecentPurchases() {
    const container = document.getElementById('recent-purchases-container');
    if (!container) return;
    
    // Get recent purchases from cookie
    const recentPurchases = getRecentPurchasesCookie();
    
    if (recentPurchases && recentPurchases.length > 0) {
        // Convert cookie objects to ID format: c1,c2,w3 (c=course, w=workshop)
        const ids = recentPurchases.map(p => {
            if (p.courseId) return 'c' + p.courseId;
            if (p.workshopId) return 'w' + p.workshopId;
            return null;
        }).filter(id => id !== null);
        
        if (ids.length === 0) {
            container.innerHTML = createEmptyState('No recent purchases', 'Your recently purchased courses will appear here.', 'shopping_bag');
            return;
        }
        
        // Fetch course details for the IDs stored in cookie
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

// ══════════════════════════════════════════════════════════════════════════════
// COOKIE HELPERS FOR RECENT PURCHASES
// ══════════════════════════════════════════════════════════════════════════════

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

function setRecentPurchasesCookie(purchases) {
    // Keep only last 10 purchases, expires in 30 days
    const limitedPurchases = purchases.slice(0, 10);
    const expires = new Date();
    expires.setDate(expires.getDate() + 30);
    document.cookie = `recent_purchases=${encodeURIComponent(JSON.stringify(limitedPurchases))}; expires=${expires.toUTCString()}; path=/`;
}

function addToRecentPurchases(courseId, workshopId) {
    const purchases = getRecentPurchasesCookie();
    const newItem = { courseId, workshopId, timestamp: Date.now() };
    
    // Remove if already exists (to move to front)
    const filtered = purchases.filter(p => 
        !(p.courseId === courseId && p.workshopId === workshopId)
    );
    
    // Add to front
    filtered.unshift(newItem);
    
    setRecentPurchasesCookie(filtered);
}

// ══════════════════════════════════════════════════════════════════════════════
// UTILITY
// ══════════════════════════════════════════════════════════════════════════════

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
