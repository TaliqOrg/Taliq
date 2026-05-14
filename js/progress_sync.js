/**
 * Progress Sync Module v2.0
 * Centralized progress and points synchronization across the platform
 * Points are now stored directly in User table
 */

class ProgressSync {
    constructor() {
        this.progressCache = new Map();
        this.pointsCache = null;
        this.listeners = new Map();
    }

    /**
     * Get course progress with caching
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
                
                // Also update points cache
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
     * Get all user enrollments with progress
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
     * Get user points with caching (from User table)
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
     * Refresh all data
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
     * Refresh specific course progress
     */
    async refreshCourseProgress(courseId) {
        const progress = await this.getCourseProgress(courseId, true);
        const points = await this.getUserPoints(true);
        
        // Also refresh all enrollments to keep them in sync
        await this.getAllEnrollments(true);

        return { progress, points };
    }

    /**
     * Subscribe to progress/points updates
     */
    on(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }
        this.listeners.get(event).push(callback);
    }

    /**
     * Unsubscribe from updates
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
     * Notify all listeners of an event
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
     * Clear all caches
     */
    clearCache() {
        this.progressCache.clear();
        this.pointsCache = null;
    }

    /**
     * Update progress display elements
     */
    updateProgressDisplay(courseId, progress) {
        // Update progress bars
        const progressBars = document.querySelectorAll(`[data-course-id="${courseId}"] .progress-fill`);
        progressBars.forEach(bar => {
            bar.style.width = `${progress.progress_percentage}%`;
        });

        // Update progress text
        const progressTexts = document.querySelectorAll(`[data-course-id="${courseId}"] .progress-text`);
        progressTexts.forEach(text => {
            text.textContent = `${Math.round(progress.progress_percentage)}% Complete`;
        });

        // Update lesson count
        const lessonCounts = document.querySelectorAll(`[data-course-id="${courseId}"] .lesson-count`);
        lessonCounts.forEach(count => {
            count.textContent = `${progress.completed_lessons} of ${progress.total_lessons} lessons completed`;
        });
    }

    /**
     * Update points display elements
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

// Create global instance
window.progressSync = new ProgressSync();

// Auto-refresh on page visibility change (when user returns to tab)
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        window.progressSync.refreshAll();
    }
});

// Auto-refresh every 5 minutes (optional, can be disabled)
setInterval(() => {
    window.progressSync.refreshAll();
}, 5 * 60 * 1000);
