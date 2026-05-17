/**
 * @file CourseDetails.js
 * @description Renders the course/workshop detail page.
 * Fetches course data by ID and type, populates all detail sections including
 * pricing, curriculum, sessions, learning outcomes, requirements, and enrollment status.
 * @version 1.0.0
 */

document.addEventListener('DOMContentLoaded', function () {
    urlParams = new URLSearchParams(window.location.search)

    populateCourseDetails();

})

/**
 * Fetches and populates all course detail sections from the API.
 */
function populateCourseDetails() {

    const id = urlParams.get('id');
    const type = urlParams.get('type');

    if (id && type) {

        fetch(`/taleeq/Taliq/api/courses.php?action=details&id=${id}&type=${type}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success || !data.course) {
                    console.error("Error:", data.message || "Course not found");
                    return;
                }
                

                const course = data.course;
                const badgeClass = (type === "course") ? 'badge-online' : 'badge-onsite';
                const PlaceOfCourse = (type === "course") ? 'Online' : 'On-Site';
                const placeholder = '/taleeq/Taliq/images/placeholder.png';


                document.getElementById('Course-Title').innerText = course.Title;
                document.getElementById('average-Rating').innerText = parseFloat(course.AverageRating || 0).toFixed(1);
                document.getElementById('Rating-Count').innerText = "(" + (course.RatingCount || 0) + ' ratings)';
                document.getElementById('EnrolledAmount').innerText = (course.EnrollmentCount || 0) + ' students enrolled';
                document.getElementById('Duration Time').innerText = parseFloat(course.DurationHours || 0).toFixed(0) + " hours";
                document.getElementById('Course-Level').innerText = course.Level || 'Beginner';
                document.getElementById('course-hero-img').src = course.ThumbnailUrl || placeholder;
                document.getElementById('course-hero-img').alt = course.Title;
                document.getElementById('CourseType').className = `badge course-badge ${badgeClass}`;
                document.getElementById('CourseType').innerText = PlaceOfCourse;
                document.getElementById('ItemPrice').innerText = course.Price + " SAR";
                document.getElementById('course-description').innerText = course.Description;
                document.getElementById('courseLanguage').innerText = course.Language || 'English';
                document.getElementById('HasCert').innerText = course.HasCertificate ? "Yes" : "No";


                document.getElementById("Course Duration and location").innerText =
                    `${parseFloat(course.DurationHours || 0).toFixed(0)} hours ${type === "course" ? "on-demand video" : "In-Person Training"}`;


                const courseId = type === 'course' ? id : null;
                const workshopId = type === 'workshop' ? id : null;
                const addToCartBtn = document.getElementById('addToCartBtn');
                if (addToCartBtn) {
                    addToCartBtn.innerHTML = '<span class="material-symbols-outlined">add_shopping_cart</span> Add to Cart';
                    addToCartBtn.onclick = function() {
                        addToCart(courseId, workshopId, course.Price, 1);
                    };
                }


                let hasCert = document.getElementById('CertIncluded');
                if (course.HasCertificate) {
                    hasCert.innerHTML = '<span class="material-symbols-outlined">workspace_premium</span>\n' +
                        ' <span>Certificate of completion</span>'
                }


                const learningoutcomesContainer = document.getElementById('learning-outcomes-container');
                const outcomesArray = JSON.parse(course.LearningOutcomes || "[]");
                if (learningoutcomesContainer) {
                    learningoutcomesContainer.innerHTML = '';

                    outcomesArray.forEach(item => {
                        learningoutcomesContainer.innerHTML += `
            <div class="outcome-item">
                <span class="material-symbols-outlined outcome-icon">check_circle</span>
                <span>${item}</span>
            </div>
        `;
                    });
                }


                const RequirementsContainer = document.getElementById('RequirementToLearn');
                const RequirementsArray = JSON.parse(course.Requirements || "[]");

                if(RequirementsArray && RequirementsArray.length > 0){
                    RequirementsContainer.innerHTML = '';

                    let htmlString = '<ul class="requirements-list">';
                    RequirementsArray.forEach(item =>{
                        htmlString += `<li>${item}</li>`;
                    })
                    htmlString += '</ul>';

                    RequirementsContainer.innerHTML = htmlString;
                }
                else if (RequirementsContainer) {
                    RequirementsContainer.innerHTML = '<p>No specific requirements.</p>';
                }


                const breadcrumbTitle = document.getElementById('breadcrumb-title');
                if (breadcrumbTitle) {
                    breadcrumbTitle.innerText = course.Title;
                }


                if (type === 'course') {
                    loadCourseCurriculum(id);
                } else {
                    loadWorkshopSessions(id);
                }


                checkEnrollmentStatus(id, type);

            })
            .catch(error => console.error('Error fetching details:', error));
    }
}



/**
 * Fetches and renders workshop session data.
 * @param {string} workshopId - The workshop ID.
 */
function loadWorkshopSessions(workshopId) {
    const container = document.getElementById('curriculum-container');
    if (!container) return;

    fetch(`/taleeq/Taliq/api/workshops.php?action=sessions&workshop_id=${workshopId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.sessions || data.sessions.length === 0) {
                container.innerHTML = '<p class="content-text">No sessions scheduled yet.</p>';
                return;
            }

            const rows = data.sessions.map(session => {
                const date     = new Date(session.SessionDate).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                const start    = session.StartTime ? session.StartTime.slice(0, 5) : '';
                const end      = session.EndTime   ? session.EndTime.slice(0, 5)   : '';
                const time     = start && end ? `${start} – ${end}` : start || '—';
                const location = session.Location || '—';
                const isFull   = session.AvailableSeats !== null && session.AvailableSeats <= 0;
                const seats    = isFull ? 'Full' : (session.AvailableSeats != null ? session.AvailableSeats + ' seats available' : '');

                return `
                    <div class="curriculum-section">
                        <div class="section-header">
                            <h3 class="section-title">
                                <span class="material-symbols-outlined" style="vertical-align:middle;font-size:1.1rem;">event</span>
                                ${date}
                            </h3>
                            <span class="section-meta">${time}</span>
                        </div>
                        <ul class="lesson-list">
                            <li class="lesson-item">
                                <span class="material-symbols-outlined lesson-icon">location_on</span>
                                <span class="lesson-title">${location}</span>
                                <span class="lesson-duration">${seats}</span>
                            </li>
                        </ul>
                    </div>`;
            }).join('');

            container.innerHTML = rows;


            const allFull = data.sessions.every(s => s.AvailableSeats !== null && s.AvailableSeats <= 0);
            if (allFull) {
                const addToCartBtn = document.getElementById('addToCartBtn');
                if (addToCartBtn) {
                    addToCartBtn.disabled = true;
                    addToCartBtn.innerHTML = '<span class="material-symbols-outlined">block</span> Workshop Full';
                }
            }
        })
        .catch(() => {
            container.innerHTML = '<p class="content-text">Unable to load sessions at this time.</p>';
        });
}



/**
 * Fetches and renders the course curriculum sections and lessons.
 * @param {string} courseId - The course ID.
 */
function loadCourseCurriculum(courseId) {
    fetch(`/taleeq/Taliq/api/courses.php?action=curriculum&course_id=${courseId}`)
        .then(response => response.json())
        .then(data => {
            const curriculumContainer = document.getElementById('curriculum-container');
            
            if (!curriculumContainer) return;
            
            if (!data.success || !data.sections || data.sections.length === 0) {
                curriculumContainer.innerHTML = '<p class="content-text">Course curriculum will be available soon.</p>';
                return;
            }
            
            let curriculumHTML = '';
            
            data.sections.forEach((section, index) => {
                const totalDuration = section.lessons.reduce((sum, lesson) => sum + (parseInt(lesson.Duration) || 0), 0);
                const hours = Math.floor(totalDuration / 60);
                const minutes = totalDuration % 60;
                const durationText = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
                
                curriculumHTML += `
                    <div class="curriculum-section">
                        <div class="section-header">
                            <h3 class="section-title">${section.section_title}</h3>
                            <span class="section-meta">${section.lessons.length} lessons • ${durationText}</span>
                        </div>
                        <ul class="lesson-list">
                            ${section.lessons.map(lesson => `
                                <li class="lesson-item">
                                    <span class="material-symbols-outlined lesson-icon">play_circle</span>
                                    <span class="lesson-title">${lesson.Title}</span>
                                    <span class="lesson-duration">${lesson.Duration ? lesson.Duration + ' min' : ''}</span>
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                `;
            });
            
            curriculumContainer.innerHTML = curriculumHTML;
        })
        .catch(error => {
            console.error('Error loading curriculum:', error);
            const curriculumContainer = document.getElementById('curriculum-container');
            if (curriculumContainer) {
                curriculumContainer.innerHTML = '<p class="content-text">Unable to load curriculum at this time.</p>';
            }
        });
}



/**
 * Copies the current page URL to the clipboard with feedback.
 */
function copyCourseLinkToClipboard() {
    const currentUrl = window.location.href;
    const button = document.getElementById('copyLinkBtn');
    

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(currentUrl)
            .then(() => {
                showCopyFeedback(button, true);
            })
            .catch(err => {
                console.error('Failed to copy:', err);
                showCopyFeedback(button, false);
            });
    } else {

        const textArea = document.createElement('textarea');
        textArea.value = currentUrl;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            showCopyFeedback(button, true);
        } catch (err) {
            console.error('Failed to copy:', err);
            showCopyFeedback(button, false);
        }
        
        document.body.removeChild(textArea);
    }
}

/**
 * Displays visual copy feedback on the share button.
 * @param {HTMLButtonElement} button - The copy link button.
 * @param {boolean} success - Whether the copy succeeded.
 */
function showCopyFeedback(button, success) {
    const originalHTML = button.innerHTML;

    if (success) {
        button.innerHTML = '<span class="material-symbols-outlined">check</span><span>Link Copied!</span>';
        button.style.backgroundColor = 'var(--primary)';
        button.style.color = 'var(--on-primary)';
    } else {
        button.innerHTML = '<span class="material-symbols-outlined">error</span><span>Failed to Copy</span>';
        button.style.backgroundColor = 'var(--error)';
        button.style.color = 'white';
    }

    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.style.backgroundColor = '';
        button.style.color = '';
    }, 2000);
}



/**
 * Checks if the user is enrolled and updates UI buttons accordingly.
 * @param {string} id - The course or workshop ID.
 * @param {string} type - The item type ('course' or 'workshop').
 * @returns {Promise<void>}
 */
async function checkEnrollmentStatus(id, type) {
    if (type !== 'course') return;

    try {
        const response = await fetch(`/taleeq/Taliq/api/enrollments.php?action=check_enrollment&course_id=${id}`);

        if (!response.ok) return;

        const data = await response.json();

        if (data.is_enrolled) {
            const addToCartBtn = document.getElementById('addToCartBtn');
            if (addToCartBtn) {
                addToCartBtn.innerHTML = '<span class="material-symbols-outlined">play_circle</span> Go to Course';
                addToCartBtn.onclick = function () {
                    window.location.href = `/taleeq/Taliq/pages/user/course_player.html?course_id=${id}`;
                };
            }

            const wishlistBtn = document.getElementById('wishlistBtn');
            if (wishlistBtn) wishlistBtn.style.display = 'none';
        }
    } catch (e) {
    }
}
