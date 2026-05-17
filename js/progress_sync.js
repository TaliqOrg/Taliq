/**
 * @file progress_sync.js
 * @description Centralized progress and points synchronization module.
 * Provides caching, event-based updates, and auto-refresh for course progress
 * and user points across the platform. Points are stored in the User table.
 * @version 2.0.0
 */

class ProgressSync {
    constructor() {
        this.progressCache = new Map();
        this.pointsCache = null;
        this.listeners = new Map();
    }

    /**
     * Fetches course progress with caching support.
     * @param {string} courseId - The course ID.
     * @param {boolean} [forceRefresh=false] - Bypass the cache.
     * @returns {Promise<Object|null>} The progress data or null on failure.
     */
    async getCourseProgress(courseId, forceRefresh = false) {
        const cacheKey = `course_${courseId}`;
        
        if (!forceRefresh && this.progressCache.has(cacheKey)) {
            return this.progressCache.get(cacheKey);
        }

        try {
            const response = await fetch(`../../api/course_player.php?action=get_progress&course_id=${courseId}`);
            const data = await response.json();

            if (data.success) {
                this.progressCache.set(cacheKey, data);
                this.notifyListeners('progress_updated', { courseId, data });
                

                if (data.user_points !== undefined) {
                    this.pointsCache = { TotalPoints: data.user_points };
                    this.notifyListeners('points_updated', this.pointsCache);
                }
                
                return data;
            }
            
            return null;
        } catch (error) {
            console.error('Error fetching course progress:', error);
            return null;
        }
    }

    /**
     * Fetches all user enrollments with progress data.
     * @param {boolean} [forceRefresh=false] - Bypass the cache.
     * @returns {Promise<Array<Object>>} The enrolled courses array.
     */
    async getAllEnrollments(forceRefresh = false) {
        const cacheKey = 'all_enrollments';
        
        if (!forceRefresh && this.progressCache.has(cacheKey)) {
            return this.progressCache.get(cacheKey);
        }

        try {
            const response = await fetch('../../api/profile.php?action=courses');
            const data = await response.json();

            if (data.success) {
                this.progressCache.set(cacheKey, data.courses);
                this.notifyListeners('enrollments_updated', data.courses);
                return data.courses;
            }
            
            return [];
        } catch (error) {
            console.error('Error fetching enrollments:', error);
            return [];
        }
    }

    /**
     * Fetches user points from the User table.
     * @param {boolean} [forceRefresh=false] - Bypass the cache.
     * @returns {Promise<Object>} The points data object.
     */
    async getUserPoints(forceRefresh = false) {
        if (!forceRefresh && this.pointsCache !== null) {
            return this.pointsCache;
        }

        try {
            const response = await fetch('../../api/points.php?action=get_user_points');
            const data = await response.json();

            if (data.success && data.points) {
                this.pointsCache = data.points;
                this.notifyListeners('points_updated', data.points);
                return data.points;
            }
            
            return { TotalPoints: 0, CurrentStreak: 0 };
        } catch (error) {
            console.error('Error fetching user points:', error);
            return { TotalPoints: 0, CurrentStreak: 0 };
        }
    }

    /**
     * Clears all caches and re-fetches enrollments and points.
     * @returns {Promise<{enrollments: Array, points: Object}>}
     */
    async refreshAll() {
        this.progressCache.clear();
        this.pointsCache = null;

        const [enrollments, points] = await Promise.all([
            this.getAllEnrollments(true),
            this.getUserPoints(true)
        ]);

        this.notifyListeners('all_refreshed', { enrollments, points });

        return { enrollments, points };
    }

    /**
     * Refreshes progress for a specific course and syncs points.
     * @param {string} courseId - The course ID to refresh.
     * @returns {Promise<{progress: Object, points: Object}>}
     */
    async refreshCourseProgress(courseId) {
        const progress = await this.getCourseProgress(courseId, true);
        const points = await this.getUserPoints(true);
        

        await this.getAllEnrollments(true);

        return { progress, points };
    }

    /**
     * Subscribes a callback to a named event.
     * @param {string} event - The event name.
     * @param {Function} callback - The listener function.
     */
    on(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }
        this.listeners.get(event).push(callback);
    }

    /**
     * Unsubscribes a callback from a named event.
     * @param {string} event - The event name.
     * @param {Function} callback - The listener to remove.
     */
    off(event, callback) {
        if (this.listeners.has(event)) {
            const callbacks = this.listeners.get(event);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        }
    }

    /**
     * Dispatches an event to all registered listeners.
     * @param {string} event - The event name.
     * @param {*} data - The event payload.
     */
    notifyListeners(event, data) {
        if (this.listeners.has(event)) {
            this.listeners.get(event).forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in ${event} listener:`, error);
                }
            });
        }
    }

    /**
     * Clears all cached progress and points data.
     */
    clearCache() {
        this.progressCache.clear();
        this.pointsCache = null;
    }

    /**
     * Updates progress bar, text, and lesson count elements in the DOM.
     * @param {string} courseId - The course ID.
     * @param {Object} progress - The progress data.
     */
    updateProgressDisplay(courseId, progress) {

        const progressBars = document.querySelectorAll(`[data-course-id="${courseId}"] .progress-fill`);
        progressBars.forEach(bar => {
            bar.style.width = `${progress.progress_percentage}%`;
        });


        const progressTexts = document.querySelectorAll(`[data-course-id="${courseId}"] .progress-text`);
        progressTexts.forEach(text => {
            text.textContent = `${Math.round(progress.progress_percentage)}% Complete`;
        });


        const lessonCounts = document.querySelectorAll(`[data-course-id="${courseId}"] .lesson-count`);
        lessonCounts.forEach(count => {
            count.textContent = `${progress.completed_lessons} of ${progress.total_lessons} lessons completed`;
        });
    }

    /**
     * Updates points and lifetime points display elements in the DOM.
     * @param {Object} points - The points data object.
     */
    updatePointsDisplay(points) {
        const pointsDisplays = document.querySelectorAll('.user-points-display, #userPointsDisplay');
        pointsDisplays.forEach(display => {
            display.textContent = points.TotalPoints.toLocaleString();
        });

        const lifetimeDisplays = document.querySelectorAll('.lifetime-points-display');
        lifetimeDisplays.forEach(display => {
            display.textContent = points.LifetimePoints.toLocaleString();
        });
    }
}

/** @type {ProgressSync} Global progress sync instance. */
window.progressSync = new ProgressSync();

/** Triggers a full data refresh when the user returns to the tab. */
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        window.progressSync.refreshAll();
    }
});

/** Auto-refreshes all synced data every 5 minutes. */
setInterval(() => {
    window.progressSync.refreshAll();
}, 5 * 60 * 1000);
