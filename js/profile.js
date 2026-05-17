/**
 * @file profile.js
 * @description User profile page controller.
 * Manages tabbed navigation across gamification, personal info, my courses,
 * certificates, and orders sections with live data loading.
 * @version 1.0.0
 */
document.addEventListener('DOMContentLoaded', function() {
    initProfileTabs();
    loadProfileSummary();
    

    if (window.progressSync) {

        window.progressSync.on('points_updated', (points) => {
            const pointsValue = document.getElementById('stat-points');
            if (pointsValue) {
                pointsValue.textContent = formatNumber(points.TotalPoints || 0);
            }
        });
        

        window.progressSync.on('enrollments_updated', () => {

            const coursesTab = document.getElementById('courses');
            if (coursesTab && coursesTab.classList.contains('active')) {
                loadEnrolledCourses();
            }
        });
    }
    

    const hash = window.location.hash.replace('#', '');
    if (hash) {
        activateTab(hash);
    } else {
        loadGamificationData();
    }
});

/**
 * Activates a profile tab by ID and loads its data.
 * @param {string} tabId - The tab panel element ID.
 */
function activateTab(tabId) {
    const navLinks = document.querySelectorAll('.profile-nav-link[data-tab]');
    const tabContents = document.querySelectorAll('.tab-content');
    

    const targetLink = document.querySelector(`.profile-nav-link[data-tab="${tabId}"]`);
    if (!targetLink) return;
    

    navLinks.forEach(nav => nav.classList.remove('active'));
    targetLink.classList.add('active');
    
    tabContents.forEach(content => content.classList.remove('active'));
    
    const targetContent = document.getElementById(tabId);
    if (targetContent) {
        targetContent.classList.add('active');
        loadTabData(tabId);
    }
}


/**
 * Initializes click handlers for profile navigation tabs.
 */
function initProfileTabs() {
    const navLinks = document.querySelectorAll('.profile-nav-link[data-tab]');
    const tabContents = document.querySelectorAll('.tab-content');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetTab = this.getAttribute('data-tab');
            
            navLinks.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
            
            tabContents.forEach(content => content.classList.remove('active'));
            
            const targetContent = document.getElementById(targetTab);
            if (targetContent) {
                targetContent.classList.add('active');
                

                loadTabData(targetTab);
            }
        });
    });
}

/**
 * Routes a tab activation to the appropriate data loader.
 * @param {string} tab - The tab identifier.
 */
function loadTabData(tab) {
    switch(tab) {
        case 'gamification':
            loadGamificationData();
            break;
        case 'personal-info':
            loadPersonalInfo();
            break;
        case 'my-courses':
            loadEnrolledCourses();
            break;
        case 'certificates':
            loadCertificates();
            break;
        case 'orders':
            loadOrders();
            break;
    }
}


/**
 * Fetches the profile summary and updates the sidebar display.
 * @returns {Promise<void>}
 */
async function loadProfileSummary() {
    try {
        const response = await fetch('../../api/profile.php?action=summary');
        const result = await response.json();
        
        if (result.success) {
            const { user, gamification } = result.summary;
            

            const profileName = document.querySelector('.profile-name');
            const profileEmail = document.querySelector('.profile-email');
            const profileJoined = document.querySelector('.profile-joined');
            
            if (profileName) profileName.textContent = user.name;
            if (profileEmail) profileEmail.textContent = user.email;
            if (profileJoined) profileJoined.textContent = `Member since ${user.member_since}`;
        }
    } catch (error) {
        console.error('Error loading profile summary:', error);
    }
}


/**
 * Loads gamification stats and renders the level progress display.
 * @returns {Promise<void>}
 */
async function loadGamificationData() {
    try {

        let points = { TotalPoints: 0 };
        if (window.progressSync) {
            points = await window.progressSync.getUserPoints(true);
        }
        
        const response = await fetch('../../api/profile.php?action=gamification');
        const result = await response.json();
        
        if (result.success) {

            result.stats.TotalPoints = points.TotalPoints || result.stats.TotalPoints || 0;
            renderGamificationStats(result.stats);
            renderLevelProgress(result.stats, result.levels);
        }
    } catch (error) {
        console.error('Error loading gamification data:', error);
    }
}

/**
 * Renders gamification stat values (points, certs, streak) into the DOM.
 * @param {Object} stats - The gamification statistics object.
 */
function renderGamificationStats(stats) {
    const pointsValue = document.getElementById('stat-points');
    const certsValue = document.getElementById('stat-certificates');
    const streakValue = document.getElementById('stat-streak');
    
    if (pointsValue) {
        pointsValue.textContent = formatNumber(stats.TotalPoints || 0);
        pointsValue.classList.add('user-points-display');
    }
    if (certsValue) certsValue.textContent = stats.CertificatesCount || 0;
    if (streakValue) streakValue.textContent = `${stats.CurrentStreak || 0} Days`;
}

/**
 * Renders the level progress bar and level grid.
 * @param {Object} stats - The gamification statistics.
 * @param {Array<Object>} levels - The available level definitions.
 */
function renderLevelProgress(stats, levels) {
    const levelTitle = document.getElementById('current-level-title');
    const levelDesc = document.getElementById('current-level-desc');
    const progressFill = document.getElementById('level-progress-fill');
    const progressText = document.getElementById('level-progress-text');
    const levelsGrid = document.getElementById('levels-grid');
    
    if (levelTitle) levelTitle.textContent = stats.LevelName || 'Course Hunter';
    if (levelDesc) levelDesc.textContent = `Level ${stats.Level || 1} - ${stats.LevelDescription || 'Keep learning!'}`;
    

    let progressPercent = stats.ProgressPercentage || 0;
    

    if (progressPercent === 0 && stats.TotalPoints > 0 && stats.NextLevel) {
        const currentMin = stats.MinPoints || 0;
        const nextMin = stats.NextLevel.MinPoints || 0;
        const range = nextMin - currentMin;
        if (range > 0) {
            progressPercent = Math.min(100, Math.round(((stats.TotalPoints - currentMin) / range) * 100));
        }
    }
    
    console.log('Level Progress:', { points: stats.TotalPoints, progressPercent, stats });
    
    if (progressFill) {
        progressFill.style.width = `${progressPercent}%`;
    }
    
    if (progressText) {
        if (stats.NextLevel) {
            progressText.textContent = `${formatNumber(stats.TotalPoints)} / ${formatNumber(stats.NextLevel.MinPoints)} points to next level`;
        } else {
            progressText.textContent = `${formatNumber(stats.TotalPoints)} points - Max level reached!`;
        }
    }
    

    if (levelsGrid && levels) {
        levelsGrid.innerHTML = levels.map(level => {
            let statusClass = 'locked';
            if (level.LevelNumber < stats.Level) statusClass = 'completed';
            if (level.LevelNumber === stats.Level) statusClass = 'current';
            
            return `
                <div class="level-item ${statusClass}">
                    <div class="level-item-badge">
                        <span class="material-symbols-outlined">${level.BadgeIcon || 'emoji_events'}</span>
                    </div>
                    <div class="level-item-info">
                        <h5 class="level-item-name">${level.LevelName}</h5>
                        <p class="level-item-points">${formatNumber(level.MinPoints)} - ${level.MaxPoints ? formatNumber(level.MaxPoints) : '∞'} points</p>
                    </div>
                </div>
            `;
        }).join('');
    }
}


/**
 * Loads personal info from the API.
 * @returns {Promise<void>}
 */
async function loadPersonalInfo() {
    try {
        const response = await fetch('../../api/profile.php?action=user-info');
        const result = await response.json();
        
        if (result.success) {
            renderPersonalInfo(result.user);
        }
    } catch (error) {
        console.error('Error loading personal info:', error);
    }
}

/**
 * Populates the personal info display and edit form fields.
 * @param {Object} user - The user data object.
 */
function renderPersonalInfo(user) {
    document.getElementById('info-fullname').textContent = `${user.FirstName} ${user.LastName}`;
    document.getElementById('info-email').textContent = user.Email;
    document.getElementById('info-phone').textContent = user.PhoneNumber || 'Not provided';
    document.getElementById('info-dob').textContent = user.DateOfBirth ? formatDate(user.DateOfBirth) : 'Not provided';
    document.getElementById('info-country').textContent = user.Country || 'Not provided';
    document.getElementById('info-city').textContent = user.City || 'Not provided';
    

    document.getElementById('edit-firstname').value = user.FirstName || '';
    document.getElementById('edit-lastname').value = user.LastName || '';
    document.getElementById('edit-email').value = user.Email || '';
    document.getElementById('edit-phone').value = user.PhoneNumber || '';
    document.getElementById('edit-dob').value = user.DateOfBirth || '';
    document.getElementById('edit-country').value = user.Country || '';
    document.getElementById('edit-city').value = user.City || '';
}

/**
 * Switches the personal info section to edit mode.
 */
function toggleEditMode() {
    document.getElementById('view-mode').style.display = 'none';
    document.getElementById('edit-mode').style.display = 'block';
}

/**
 * Exits edit mode and reverts to view mode.
 */
function cancelEdit() {
    document.getElementById('view-mode').style.display = 'grid';
    document.getElementById('edit-mode').style.display = 'none';
}

/**
 * Validates and submits updated personal info to the API.
 * @param {Event} event - The form submit event.
 * @returns {Promise<void>}
 */
async function savePersonalInfo(event) {
    event.preventDefault();
    
    const data = {
        action: 'update-info',
        first_name: document.getElementById('edit-firstname').value.trim(),
        last_name: document.getElementById('edit-lastname').value.trim(),
        email: document.getElementById('edit-email').value.trim(),
        phone: document.getElementById('edit-phone').value.trim(),
        date_of_birth: document.getElementById('edit-dob').value,
        country: document.getElementById('edit-country').value.trim(),
        city: document.getElementById('edit-city').value.trim()
    };
    
    if (!data.first_name || !data.last_name || !data.email) {
        showToast('Please fill in all required fields', 'error');
        return;
    }
    
    try {
        const response = await fetch('../../api/profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Profile updated successfully!', 'success');
            cancelEdit();
            loadPersonalInfo();
            loadProfileSummary();
        } else {
            showToast(result.message || 'Failed to update profile', 'error');
        }
    } catch (error) {
        console.error('Error saving profile:', error);
        showToast('An error occurred', 'error');
    }
}


/**
 * Fetches and renders enrolled courses using the progress sync module.
 * @returns {Promise<void>}
 */
async function loadEnrolledCourses() {
    const container = document.getElementById('enrolled-courses-container');
    if (!container) return;
    
    container.innerHTML = '<p class="loading-text">Loading your courses...</p>';
    
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
            container.innerHTML = courses.map(course => createCourseItem(course)).join('');
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <span class="material-symbols-outlined">school</span>
                    <h3>No courses yet</h3>
                    <p>Start your learning journey by enrolling in a course.</p>
                    <a href="../explore.html" class="btn btn-primary">Explore Courses</a>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading courses:', error);
        container.innerHTML = '<p class="error-text">Error loading courses. Please try again.</p>';
    }
}

/**
 * Generates the HTML for an enrolled course list item.
 * @param {Object} course - The enrolled course data.
 * @returns {string} The course item HTML markup.
 */
function createCourseItem(course) {
    const placeholder = '../../images/placeholder-course.png';
    const thumbnail = course.ThumbnailUrl || placeholder;
    const progress = Math.round(course.ProgressPercentage || 0);
    const isCompleted = course.CompletionStatus === 'completed';
    const buttonText = isCompleted ? 'Review Course' : 'Continue Learning';
    const progressText = isCompleted ? 'Completed' : `${progress}% Complete`;
    const itemId = course.CourseId || course.WorkshopId;
    const itemType = course.ItemType;
    
    const linkUrl = itemType === 'course' 
        ? `course_player.html?course_id=${itemId}` 
        : `../course_details.html?id=${itemId}&type=workshop`;
    
    return `
        <div class="course-item" data-course-id="${itemId}">
            <img src="${thumbnail}" alt="${course.Title}" class="course-thumbnail" onerror="this.src='${placeholder}'">
            <div class="course-details">
                <h3 class="course-name">${course.Title}</h3>
                <p class="course-instructor">${course.DurationHours ? course.DurationHours + ' hours' : ''}</p>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${progress}%;"></div>
                    </div>
                    <span class="progress-text">${progressText}</span>
                </div>
            </div>
            <a href="${linkUrl}" class="btn btn-outline btn-small">${buttonText}</a>
        </div>
    `;
}


/**
 * Fetches and renders user certificates.
 * @returns {Promise<void>}
 */
async function loadCertificates() {
    const container = document.getElementById('certificates-container');
    if (!container) return;
    
    container.innerHTML = '<p class="loading-text">Loading your certificates...</p>';
    
    try {
        const response = await fetch('../../api/profile.php?action=certificates');
        const result = await response.json();
        
        if (result.success && result.certificates.length > 0) {
            container.innerHTML = result.certificates.map(cert => createCertificateCard(cert)).join('');
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <span class="material-symbols-outlined">workspace_premium</span>
                    <h3>No certificates yet</h3>
                    <p>Complete a course to earn your first certificate.</p>
                    <a href="../explore.html" class="btn btn-primary">Browse Courses</a>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading certificates:', error);
        container.innerHTML = '<p class="error-text">Error loading certificates. Please try again.</p>';
    }
}

/**
 * Generates the HTML for a certificate card.
 * @param {Object} cert - The certificate data object.
 * @returns {string} The certificate card HTML markup.
 */
function createCertificateCard(cert) {
    return `
        <div class="certificate-card">
            <div class="certificate-icon">
                <span class="material-symbols-outlined">workspace_premium</span>
            </div>
            <div class="certificate-info">
                <h3 class="certificate-title">${cert.CourseTitle}</h3>
                <p class="certificate-date">Completed on ${cert.IssueDateFormatted}</p>
                <p class="certificate-code">Certificate ID: ${cert.CertificateCode}</p>
            </div>
            <div class="certificate-actions">
                <button class="btn-icon" onclick="downloadCertificate('${cert.CertificateId}')" title="Download Certificate">
                    <span class="material-symbols-outlined">download</span>
                </button>
            </div>
        </div>
    `;
}

/**
 * Opens the certificate page in a new tab.
 * @param {string} certId - The certificate ID.
 */
function downloadCertificate(certId) {
    window.open(`/taleeq/Taliq/pages/certificate.html?cert_id=${certId}`, '_blank');
}


/**
 * Fetches and renders order history.
 * @returns {Promise<void>}
 */
async function loadOrders() {
    const container = document.getElementById('orders-container');
    if (!container) return;
    
    container.innerHTML = '<p class="loading-text">Loading your orders...</p>';
    
    try {
        const response = await fetch('../../api/profile.php?action=orders');
        const result = await response.json();
        
        if (result.success && result.orders.length > 0) {
            container.innerHTML = result.orders.map(order => createOrderCard(order)).join('');
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <span class="material-symbols-outlined">receipt_long</span>
                    <h3>No orders yet</h3>
                    <p>Your order history will appear here after your first purchase.</p>
                    <a href="../explore.html" class="btn btn-primary">Browse Courses</a>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading orders:', error);
        container.innerHTML = '<p class="error-text">Error loading orders. Please try again.</p>';
    }
}

/**
 * Generates the HTML for an order card.
 * @param {Object} order - The order data object.
 * @returns {string} The order card HTML markup.
 */
function createOrderCard(order) {
    const statusClass = `status-${order.Status}`;
    const statusText = order.Status.charAt(0).toUpperCase() + order.Status.slice(1);
    
    const itemsHtml = order.Items.map(item => `
        <div class="order-item">
            <img src="${item.ThumbnailUrl || '../../images/placeholder-course.png'}" alt="${item.Title}" class="order-item-img">
            <div class="order-item-details">
                <h4 class="order-item-title">${item.Title}</h4>
                <p class="order-item-type">${item.ItemType}</p>
            </div>
            <p class="order-item-price">${item.UnitPrice} SAR</p>
        </div>
    `).join('');
    
    return `
        <div class="order-card">
            <div class="order-header">
                <div>
                    <p class="order-id">Order #${order.OrderNumber}</p>
                    <p class="order-date">${order.OrderDateFormatted}</p>
                </div>
                <div class="order-status ${statusClass}">${statusText}</div>
            </div>
            <div class="order-items">
                ${itemsHtml}
            </div>
            <div class="order-footer">
                <p class="order-total">Total: <strong>${order.TotalFormatted}</strong></p>
            </div>
        </div>
    `;
}


/**
 * Formats a number with comma-separated thousands.
 * @param {number} num - The number to format.
 * @returns {string} The formatted number string.
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Formats a date string into a human-readable format.
 * @param {string} dateStr - The date string to format.
 * @returns {string} The formatted date.
 */
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

/**
 * Displays a temporary toast notification.
 * @param {string} message - The notification message.
 * @param {string} [type='info'] - The type ('success', 'error', 'warning', 'info').
 */
function showToast(message, type = 'info') {
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) existingToast.remove();
    
    const icons = {
        success: 'check_circle',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };
    
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <span class="material-symbols-outlined toast-icon">${icons[type]}</span>
        <span class="toast-message">${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('toast-show'), 10);
    setTimeout(() => {
        toast.classList.remove('toast-show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
