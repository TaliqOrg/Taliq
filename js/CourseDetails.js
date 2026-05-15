document.addEventListener('DOMContentLoaded', function () {
    urlParams = new URLSearchParams(window.location.search)

    populateCourseDetails();

})

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
                
                // Extract course data from response
                const course = data.course;
                const badgeClass = (type === "course") ? 'badge-online' : 'badge-onsite';
                const PlaceOfCourse = (type === "course") ? 'Online' : 'On-Site';
                const placeholder = '/taleeq/Taliq/images/placeholder.png';

                //put data in HTML elements
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

                //prints out the duration of the cours if its on-demand video or in person training
                document.getElementById("Course Duration and location").innerText =
                    `${parseFloat(course.DurationHours || 0).toFixed(0)} hours ${type === "course" ? "on-demand video" : "In-Person Training"}`;

                // Update Add to Cart button with correct course data
                const courseId = type === 'course' ? id : null;
                const workshopId = type === 'workshop' ? id : null;
                const addToCartBtn = document.getElementById('addToCartBtn');
                if (addToCartBtn) {
                    addToCartBtn.onclick = function() {
                        var qty = parseInt(document.getElementById('quantity').value) || 1;
                        addToCart(courseId, workshopId, course.Price, qty);
                    };
                }

                //whenever this course has a Certificate or not
                let hasCert = document.getElementById('CertIncluded');
                if (course.HasCertificate) {
                    hasCert.innerHTML = '<span class="material-symbols-outlined">workspace_premium</span>\n' +
                        ' <span>Certificate of completion</span>'
                }

                //prints out the learning outcomes of said course or "what you'll learn"
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

                //prints out whenever the course has requirements the user must have
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

                // Update breadcrumb with course title
                const breadcrumbTitle = document.getElementById('breadcrumb-title');
                if (breadcrumbTitle) {
                    breadcrumbTitle.innerText = course.Title;
                }

                // Load curriculum for courses, sessions for workshops
                if (type === 'course') {
                    loadCourseCurriculum(id);
                } else {
                    loadWorkshopSessions(id);
                }

            })
            .catch(error => console.error('Error fetching details:', error));
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// LOAD WORKSHOP SESSIONS
// ══════════════════════════════════════════════════════════════════════════════

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
                const seats    = session.AvailableSeats != null ? session.AvailableSeats + ' seats available' : '';

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
        })
        .catch(() => {
            container.innerHTML = '<p class="content-text">Unable to load sessions at this time.</p>';
        });
}

// ══════════════════════════════════════════════════════════════════════════════
// LOAD COURSE CURRICULUM
// ══════════════════════════════════════════════════════════════════════════════

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

// ══════════════════════════════════════════════════════════════════════════════
// COPY COURSE LINK TO CLIPBOARD
// ══════════════════════════════════════════════════════════════════════════════

function copyCourseLinkToClipboard() {
    const currentUrl = window.location.href;
    const button = document.getElementById('copyLinkBtn');
    
    // Use modern clipboard API
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
        // Fallback for older browsers
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
