-- Course Player and Progress Tracking Tables
-- Created: 2026-05-12

USE taleeq_db;

-- ============================================
-- LessonProgress Table
-- Tracks individual lesson completion for each user
-- ============================================
CREATE TABLE IF NOT EXISTS LessonProgress (
    ProgressId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    LessonId INT NOT NULL,
    CourseId INT NOT NULL,
    IsCompleted BOOLEAN DEFAULT FALSE,
    CompletedAt TIMESTAMP NULL,
    LastAccessedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    WatchTimeSeconds INT DEFAULT 0,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    FOREIGN KEY (LessonId) REFERENCES Lesson(LessonId) ON DELETE CASCADE,
    FOREIGN KEY (CourseId) REFERENCES Course(CourseId) ON DELETE CASCADE,
    UNIQUE KEY unique_user_lesson (UserId, LessonId),
    INDEX idx_user_course (UserId, CourseId),
    INDEX idx_completed (IsCompleted),
    INDEX idx_last_accessed (LastAccessedAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Trigger to update Enrollment progress when lesson is completed
-- ============================================
DELIMITER //

CREATE TRIGGER IF NOT EXISTS update_enrollment_progress_after_lesson_complete
AFTER UPDATE ON LessonProgress
FOR EACH ROW
BEGIN
    DECLARE total_lessons INT;
    DECLARE completed_lessons INT;
    DECLARE new_progress DECIMAL(5,2);
    DECLARE new_status ENUM('not_started', 'in_progress', 'completed');
    
    IF NEW.IsCompleted = TRUE AND OLD.IsCompleted = FALSE THEN
        -- Count total lessons in the course
        SELECT COUNT(*) INTO total_lessons
        FROM Lesson
        WHERE CourseId = NEW.CourseId;
        
        -- Count completed lessons for this user
        SELECT COUNT(*) INTO completed_lessons
        FROM LessonProgress
        WHERE UserId = NEW.UserId 
        AND CourseId = NEW.CourseId 
        AND IsCompleted = TRUE;
        
        -- Calculate progress percentage
        IF total_lessons > 0 THEN
            SET new_progress = (completed_lessons / total_lessons) * 100;
        ELSE
            SET new_progress = 0;
        END IF;
        
        -- Determine completion status
        IF new_progress = 0 THEN
            SET new_status = 'not_started';
        ELSEIF new_progress = 100 THEN
            SET new_status = 'completed';
        ELSE
            SET new_status = 'in_progress';
        END IF;
        
        -- Update enrollment record
        UPDATE Enrollment
        SET 
            ProgressPercentage = new_progress,
            CompletionStatus = new_status,
            CompletedAt = IF(new_status = 'completed', NOW(), NULL)
        WHERE UserId = NEW.UserId AND CourseId = NEW.CourseId;
    END IF;
END//

DELIMITER ;
