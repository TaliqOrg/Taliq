class CoursePlayer {
    constructor() {
        this.currentLesson = null;
        this.courseId = null;
        this.lessonId = null;
        this.watchTimeInterval = null;
        this.watchTimeSeconds = 0;
        this.init();
    }

    init() {
        const urlParams = new URLSearchParams(window.location.search);
        this.courseId = urlParams.get('course_id');
        this.lessonId = urlParams.get('lesson_id');

        if (!this.courseId) {
            this.showError('Course ID is missing');
            return;
        }

        this.loadCourseContent();
        
        if (this.lessonId) {
            this.loadLesson(this.lessonId);
        } else {
            this.loadFirstLesson();
        }

        this.setupEventListeners();
    }

    setupEventListeners() {
        const markCompleteBtn = document.getElementById('markCompleteBtn');
        if (markCompleteBtn) {
            markCompleteBtn.addEventListener('click', () => this.markLessonComplete());
        }

        const nextLessonBtn = document.getElementById('nextLessonBtn');
        if (nextLessonBtn) {
            nextLessonBtn.addEventListener('click', () => this.navigateToNextLesson());
        }

        const prevLessonBtn = document.getElementById('prevLessonBtn');
        if (prevLessonBtn) {
            prevLessonBtn.addEventListener('click', () => this.navigateToPreviousLesson());
        }

        this.loadUserPoints();
    }

    async loadUserPoints() {
        try {
            const response = await fetch('../../api/points.php?action=get_user_points');
            const data = await response.json();
            
            if (data.success && data.points) {
                this.updatePointsDisplay(data.points.TotalPoints || 0);
            }
        } catch (error) {
            console.error('Error loading user points:', error);
        }
    }

    updatePointsDisplay(points) {
        const pointsDisplay = document.getElementById('userPointsDisplay');
        if (pointsDisplay) {
            pointsDisplay.textContent = points.toLocaleString();
        }
    }

    async loadCourseContent() {
        try {
            const response = await fetch(`../../api/course_player.php?action=get_course_content&course_id=${this.courseId}`);
            const data = await response.json();

            if (data.success) {
                this.renderCourseSidebar(data.course, data.sections, data.progress);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            console.error('Error loading course content:', error);
            this.showError('Failed to load course content');
        }
    }

    async loadLesson(lessonId) {
        try {
            const response = await fetch(`../../api/course_player.php?action=get_lesson&lesson_id=${lessonId}`);
            const data = await response.json();

            if (data.success) {
                this.currentLesson = data.lesson;
                this.renderLesson(data.lesson, data.next_lesson, data.previous_lesson);
                this.startWatchTimeTracking();
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            console.error('Error loading lesson:', error);
            this.showError('Failed to load lesson');
        }
    }

    async loadFirstLesson() {
        try {
            const response = await fetch(`../../api/course_player.php?action=get_course_content&course_id=${this.courseId}`);
            const data = await response.json();

            if (data.success && data.sections.length > 0 && data.sections[0].lessons.length > 0) {
                const firstLesson = data.sections[0].lessons[0];
                this.loadLesson(firstLesson.LessonId);
            }
        } catch (error) {
            console.error('Error loading first lesson:', error);
        }
    }

    renderLesson(lesson, nextLesson, previousLesson) {
        document.title = `${lesson.Title} | Taliq`;
        
        const breadcrumb = document.querySelector('.course-breadcrumb');
        if (breadcrumb) {
            breadcrumb.textContent = lesson.CourseTitle;
        }

        const lessonTitle = document.querySelector('.lesson-title');
        if (lessonTitle) {
            lessonTitle.textContent = lesson.Title;
        }

        const lessonMeta = document.querySelector('.lesson-meta');
        if (lessonMeta) {
            const duration = lesson.Duration ? `${lesson.Duration} minutes` : '';
            lessonMeta.textContent = duration;
        }

        this.renderVideoPlayer(lesson);
        this.renderLessonContent(lesson);
        this.updateNavigationButtons(nextLesson, previousLesson);
        this.updateCompleteButton(lesson.IsCompleted);
        
        this.updateURL(lesson.LessonId);
    }

    renderVideoPlayer(lesson) {
        const videoContainer = document.querySelector('.video-container');
        if (!videoContainer) return;

        if (lesson.ContentType === 'video' && lesson.ContentUrl) {
            videoContainer.innerHTML = `
                <video controls class="video-player" id="videoPlayer">
                    <source src="${lesson.ContentUrl}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            `;
        } else if (lesson.ContentType === 'document' && lesson.ContentUrl) {
            videoContainer.innerHTML = `
                <iframe src="${lesson.ContentUrl}" class="document-viewer" frameborder="0"></iframe>
            `;
        } else {
            videoContainer.innerHTML = `
                <div class="video-placeholder">
                    <span class="material-symbols-outlined video-icon">play_circle</span>
                    <p>Content Player</p>
                    <p class="video-duration">${lesson.Duration ? lesson.Duration + ' min' : ''}</p>
                </div>
            `;
        }
    }

    renderLessonContent(lesson) {
        const descriptionContainer = document.getElementById('lessonDescription');
        if (!descriptionContainer) return;

        const description = lesson.Description || 'No description available for this lesson.';
        descriptionContainer.innerHTML = `<p class="content-text">${description}</p>`;
    }

    renderCourseSidebar(course, sections, progress) {
        const sidebarTitle = document.querySelector('.sidebar-title');
        if (sidebarTitle) {
            sidebarTitle.textContent = 'Course Content';
        }

        const sidebarProgress = document.querySelector('.sidebar-progress');
        if (sidebarProgress && progress) {
            sidebarProgress.textContent = `${progress.completed_lessons || 0} of ${progress.total_lessons || 0} lessons completed`;
        }

        const progressFill = document.querySelector('.progress-bar-fill');
        const progressPercentage = document.querySelector('.progress-percentage');
        if (progressFill && progressPercentage && progress) {
            const percentage = progress.progress_percentage || 0;
            progressFill.style.width = `${percentage}%`;
            progressPercentage.textContent = `${Math.round(percentage)}%`;
        }

        const curriculumList = document.querySelector('.curriculum-list');
        if (!curriculumList) return;

        let sectionsHTML = '';
        sections.forEach(section => {
            const totalDuration = section.lessons.reduce((sum, lesson) => sum + (parseInt(lesson.Duration) || 0), 0);
            const hours = Math.floor(totalDuration / 60);
            const minutes = totalDuration % 60;
            const durationText = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;

            sectionsHTML += `
                <details class="curriculum-section">
                    <summary class="section-header-curriculum">
                        <span class="material-symbols-outlined">expand_more</span>
                        <div>
                            <h3 class="section-title-curriculum">${section.section_title}</h3>
                            <p class="section-meta">${section.lessons.length} lessons • ${durationText}</p>
                        </div>
                    </summary>
                    <div class="section-lessons">
                        ${this.renderLessonItems(section.lessons)}
                    </div>
                </details>
            `;
        });

        curriculumList.innerHTML = sectionsHTML;
        this.attachLessonClickHandlers();
    }

    renderLessonItems(lessons) {
        return lessons.map(lesson => {
            const isActive = this.lessonId == lesson.LessonId ? 'active' : '';
            const isCompleted = lesson.IsCompleted ? 'completed' : '';
            const completedIcon = lesson.IsCompleted ? 
                '<span class="material-symbols-outlined lesson-complete-icon">check_circle</span>' : '';

            return `
                <a href="#" class="lesson-item ${isActive} ${isCompleted}" data-lesson-id="${lesson.LessonId}">
                    <span class="material-symbols-outlined">play_circle</span>
                    <div class="lesson-details-sidebar">
                        <p class="lesson-name">${lesson.Title}</p>
                        <p class="lesson-duration">${lesson.Duration ? lesson.Duration + ' min' : ''}</p>
                    </div>
                    ${completedIcon}
                </a>
            `;
        }).join('');
    }

    attachLessonClickHandlers() {
        const lessonItems = document.querySelectorAll('.lesson-item');
        lessonItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const lessonId = item.getAttribute('data-lesson-id');
                if (lessonId) {
                    this.loadLesson(lessonId);
                    this.lessonId = lessonId;
                }
            });
        });
    }

    updateNavigationButtons(nextLesson, previousLesson) {
        const nextBtn = document.getElementById('nextLessonBtn');
        const prevBtn = document.getElementById('prevLessonBtn');

        if (nextBtn) {
            if (nextLesson) {
                nextBtn.disabled = false;
                nextBtn.setAttribute('data-lesson-id', nextLesson.LessonId);
            } else {
                nextBtn.disabled = true;
            }
        }

        if (prevBtn) {
            if (previousLesson) {
                prevBtn.disabled = false;
                prevBtn.setAttribute('data-lesson-id', previousLesson.LessonId);
            } else {
                prevBtn.disabled = true;
            }
        }
    }

    updateCompleteButton(isCompleted) {
        const markCompleteBtn = document.getElementById('markCompleteBtn');
        if (markCompleteBtn) {
            if (isCompleted) {
                markCompleteBtn.innerHTML = '<span class="material-symbols-outlined">check_circle</span>';
                markCompleteBtn.classList.add('completed');
                markCompleteBtn.title = 'Completed';
                markCompleteBtn.disabled = true;
                markCompleteBtn.style.cursor = 'not-allowed';
                markCompleteBtn.style.opacity = '0.6';
            } else {
                markCompleteBtn.innerHTML = '<span class="material-symbols-outlined">check_circle</span>';
                markCompleteBtn.classList.remove('completed');
                markCompleteBtn.title = 'Mark as Complete';
                markCompleteBtn.disabled = false;
                markCompleteBtn.style.cursor = 'pointer';
                markCompleteBtn.style.opacity = '1';
            }
        }
    }

    async markLessonComplete() {
        if (!this.currentLesson || this.currentLesson.IsCompleted) {
            return;
        }

        try {
            const response = await fetch('../../api/course_player.php?action=mark_complete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    lesson_id: this.currentLesson.LessonId,
                    course_id: this.courseId
                })
            });

            const data = await response.json();

            if (data.success) {
                this.currentLesson.IsCompleted = true;
                this.updateCompleteButton(true);
                
                const pointsAwarded = data.points_awarded || 50;
                this.showSuccessNotification(`Lesson Complete! +${pointsAwarded} points`);
                
                // Refresh progress and points using sync module
                if (window.progressSync) {
                    await window.progressSync.refreshCourseProgress(this.courseId);
                }
                
                this.loadCourseContent();
                this.loadUserPoints();
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            console.error('Error marking lesson complete:', error);
            this.showError('Failed to mark lesson as complete');
        }
    }

    navigateToNextLesson() {
        const nextBtn = document.getElementById('nextLessonBtn');
        if (nextBtn && !nextBtn.disabled) {
            const nextLessonId = nextBtn.getAttribute('data-lesson-id');
            if (nextLessonId) {
                this.loadLesson(nextLessonId);
                this.lessonId = nextLessonId;
            }
        }
    }

    navigateToPreviousLesson() {
        const prevBtn = document.getElementById('prevLessonBtn');
        if (prevBtn && !prevBtn.disabled) {
            const prevLessonId = prevBtn.getAttribute('data-lesson-id');
            if (prevLessonId) {
                this.loadLesson(prevLessonId);
                this.lessonId = prevLessonId;
            }
        }
    }

    startWatchTimeTracking() {
        this.stopWatchTimeTracking();
        
        this.watchTimeInterval = setInterval(() => {
            this.watchTimeSeconds += 5;
            this.updateWatchTime();
        }, 5000);
    }

    stopWatchTimeTracking() {
        if (this.watchTimeInterval) {
            clearInterval(this.watchTimeInterval);
            this.watchTimeInterval = null;
        }
    }

    async updateWatchTime() {
        if (!this.currentLesson) return;

        try {
            await fetch('../../api/course_player.php?action=update_watch_time', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    lesson_id: this.currentLesson.LessonId,
                    course_id: this.courseId,
                    watch_time: this.watchTimeSeconds
                })
            });
        } catch (error) {
            console.error('Error updating watch time:', error);
        }
    }

    updateURL(lessonId) {
        const newURL = `${window.location.pathname}?course_id=${this.courseId}&lesson_id=${lessonId}`;
        window.history.pushState({ lessonId }, '', newURL);
    }

    showSuccessNotification(message) {
        const notification = document.getElementById('pointsNotification');
        if (notification) {
            const messageEl = notification.querySelector('.points-message');
            if (messageEl) {
                messageEl.innerHTML = `<strong>${message}</strong>`;
            }
            notification.style.display = 'flex';
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
        }
    }

    showError(message) {
        alert(message);
    }
}

function closePointsNotification() {
    const notification = document.getElementById('pointsNotification');
    if (notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.style.display = 'none';
        }, 300);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new CoursePlayer();
});
