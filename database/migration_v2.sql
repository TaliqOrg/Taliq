-- ============================================================================
-- TALEEQ DATABASE MIGRATION v2.0
-- Run this to migrate from old schema to new simplified structure
-- ============================================================================

USE taleeq_db;

-- ============================================================================
-- STEP 1: Add new columns to User table
-- ============================================================================

-- Add Points column if not exists
ALTER TABLE User ADD COLUMN IF NOT EXISTS Points INT DEFAULT 0;
ALTER TABLE User ADD COLUMN IF NOT EXISTS CurrentStreak INT DEFAULT 0;
ALTER TABLE User ADD COLUMN IF NOT EXISTS LongestStreak INT DEFAULT 0;
ALTER TABLE User ADD COLUMN IF NOT EXISTS LastActivityDate DATE;
ALTER TABLE User ADD COLUMN IF NOT EXISTS DateOfBirth DATE;
ALTER TABLE User ADD COLUMN IF NOT EXISTS Country VARCHAR(100);
ALTER TABLE User ADD COLUMN IF NOT EXISTS City VARCHAR(100);
ALTER TABLE User ADD COLUMN IF NOT EXISTS ProfileImageUrl VARCHAR(500);

-- Add index on Points
ALTER TABLE User ADD INDEX IF NOT EXISTS idx_points (Points);

-- ============================================================================
-- STEP 2: Migrate points from UserPoints to User table
-- ============================================================================

UPDATE User u
LEFT JOIN UserPoints up ON u.UserId = up.UserId
SET u.Points = COALESCE(up.TotalPoints, 0)
WHERE up.UserId IS NOT NULL;

-- Migrate from UserGamification if exists
UPDATE User u
LEFT JOIN UserGamification ug ON u.UserId = ug.UserId
SET 
    u.Points = COALESCE(u.Points, 0) + COALESCE(ug.TotalPoints, 0),
    u.CurrentStreak = COALESCE(ug.CurrentStreak, 0),
    u.LongestStreak = COALESCE(ug.LongestStreak, 0),
    u.LastActivityDate = ug.LastActivityDate
WHERE ug.UserId IS NOT NULL AND u.Points = 0;

-- ============================================================================
-- STEP 3: Create new Level table (replaces LevelDefinition)
-- ============================================================================

CREATE TABLE IF NOT EXISTS Level (
    LevelId INT AUTO_INCREMENT PRIMARY KEY,
    LevelNumber INT NOT NULL UNIQUE,
    LevelName VARCHAR(100) NOT NULL,
    MinPoints INT NOT NULL,
    MaxPoints INT,
    BadgeIcon VARCHAR(100),
    Description TEXT,
    INDEX idx_level_number (LevelNumber),
    INDEX idx_min_points (MinPoints)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate data from LevelDefinition if exists
INSERT IGNORE INTO Level (LevelNumber, LevelName, MinPoints, MaxPoints, BadgeIcon, Description)
SELECT LevelNumber, LevelName, MinPoints, MaxPoints, BadgeIcon, Description
FROM LevelDefinition
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'LevelDefinition');

-- Insert default levels if table is empty
INSERT INTO Level (LevelNumber, LevelName, MinPoints, MaxPoints, BadgeIcon, Description)
SELECT * FROM (
    SELECT 1 as LevelNumber, 'Course Hunter' as LevelName, 0 as MinPoints, 499 as MaxPoints, 'search' as BadgeIcon, 'Just getting started' as Description
    UNION SELECT 2, 'Knowledge Seeker', 500, 1499, 'menu_book', 'Building knowledge'
    UNION SELECT 3, 'Skill Builder', 1500, 2999, 'construction', 'Developing skills'
    UNION SELECT 4, 'Learning Pro', 3000, 4999, 'school', 'Committed learner'
    UNION SELECT 5, 'Expert Learner', 5000, 9999, 'psychology', 'Mastering domains'
    UNION SELECT 6, 'Knowledge Master', 10000, NULL, 'workspace_premium', 'True master'
) AS defaults
WHERE NOT EXISTS (SELECT 1 FROM Level LIMIT 1);

-- ============================================================================
-- STEP 4: Add PointsAwarded column to LessonProgress
-- ============================================================================

ALTER TABLE LessonProgress ADD COLUMN IF NOT EXISTS PointsAwarded BOOLEAN DEFAULT FALSE;

-- Mark existing completed lessons as having points awarded
UPDATE LessonProgress SET PointsAwarded = TRUE WHERE IsCompleted = TRUE;

-- ============================================================================
-- STEP 5: Drop old triggers
-- ============================================================================

DROP TRIGGER IF EXISTS award_points_on_lesson_complete;
DROP TRIGGER IF EXISTS award_points_on_course_complete;
DROP TRIGGER IF EXISTS update_enrollment_progress_after_lesson_complete;

-- ============================================================================
-- STEP 6: Create new unified trigger
-- ============================================================================

DELIMITER //

DROP TRIGGER IF EXISTS after_lesson_complete//

CREATE TRIGGER after_lesson_complete
AFTER UPDATE ON LessonProgress
FOR EACH ROW
BEGIN
    DECLARE total_lessons INT;
    DECLARE completed_lessons INT;
    DECLARE new_progress DECIMAL(5,2);
    DECLARE new_status ENUM('not_started', 'in_progress', 'completed');
    DECLARE was_completed BOOLEAN;
    
    IF NEW.IsCompleted = TRUE AND (OLD.IsCompleted = FALSE OR OLD.IsCompleted IS NULL) THEN
        
        -- Award 50 points if not already awarded
        IF OLD.PointsAwarded = FALSE OR OLD.PointsAwarded IS NULL THEN
            UPDATE User SET Points = Points + 50 WHERE UserId = NEW.UserId;
        END IF;
        
        -- Count lessons
        SELECT COUNT(*) INTO total_lessons FROM Lesson WHERE CourseId = NEW.CourseId;
        SELECT COUNT(*) INTO completed_lessons FROM LessonProgress 
            WHERE UserId = NEW.UserId AND CourseId = NEW.CourseId AND IsCompleted = TRUE;
        
        -- Calculate progress
        IF total_lessons > 0 THEN
            SET new_progress = (completed_lessons / total_lessons) * 100;
        ELSE
            SET new_progress = 0;
        END IF;
        
        -- Determine status
        IF new_progress >= 100 THEN
            SET new_status = 'completed';
        ELSEIF new_progress > 0 THEN
            SET new_status = 'in_progress';
        ELSE
            SET new_status = 'not_started';
        END IF;
        
        -- Check if was already completed
        SELECT CompletionStatus = 'completed' INTO was_completed 
        FROM Enrollment WHERE UserId = NEW.UserId AND CourseId = NEW.CourseId;
        
        -- Update enrollment
        UPDATE Enrollment SET 
            ProgressPercentage = new_progress,
            CompletionStatus = new_status,
            CompletedAt = IF(new_status = 'completed' AND (was_completed = FALSE OR was_completed IS NULL), NOW(), CompletedAt)
        WHERE UserId = NEW.UserId AND CourseId = NEW.CourseId;
        
        -- Award bonus for course completion
        IF new_status = 'completed' AND (was_completed = FALSE OR was_completed IS NULL) THEN
            UPDATE User SET Points = Points + 50 WHERE UserId = NEW.UserId;
        END IF;
    END IF;
END//

DELIMITER ;

-- ============================================================================
-- STEP 7: Create trigger for INSERT
-- ============================================================================

DELIMITER //

DROP TRIGGER IF EXISTS after_lesson_progress_insert//

CREATE TRIGGER after_lesson_progress_insert
AFTER INSERT ON LessonProgress
FOR EACH ROW
BEGIN
    DECLARE total_lessons INT;
    DECLARE completed_lessons INT;
    DECLARE new_progress DECIMAL(5,2);
    DECLARE new_status ENUM('not_started', 'in_progress', 'completed');
    
    IF NEW.IsCompleted = TRUE THEN
        -- Award 50 points
        UPDATE User SET Points = Points + 50 WHERE UserId = NEW.UserId;
        
        -- Update progress
        SELECT COUNT(*) INTO total_lessons FROM Lesson WHERE CourseId = NEW.CourseId;
        SELECT COUNT(*) INTO completed_lessons FROM LessonProgress 
            WHERE UserId = NEW.UserId AND CourseId = NEW.CourseId AND IsCompleted = TRUE;
        
        IF total_lessons > 0 THEN
            SET new_progress = (completed_lessons / total_lessons) * 100;
        ELSE
            SET new_progress = 0;
        END IF;
        
        IF new_progress >= 100 THEN
            SET new_status = 'completed';
            -- Award course completion bonus
            UPDATE User SET Points = Points + 50 WHERE UserId = NEW.UserId;
        ELSEIF new_progress > 0 THEN
            SET new_status = 'in_progress';
        ELSE
            SET new_status = 'not_started';
        END IF;
        
        UPDATE Enrollment SET 
            ProgressPercentage = new_progress,
            CompletionStatus = new_status,
            CompletedAt = IF(new_status = 'completed', NOW(), NULL)
        WHERE UserId = NEW.UserId AND CourseId = NEW.CourseId;
    END IF;
END//

DELIMITER ;

-- ============================================================================
-- STEP 8: Drop old tables (optional - uncomment if you want to remove)
-- ============================================================================

DROP TABLE IF EXISTS UserPoints;
DROP TABLE IF EXISTS PointsTransaction;
DROP TABLE IF EXISTS PointsConfig;
DROP TABLE IF EXISTS UserGamification;
DROP TABLE IF EXISTS PointAction;
DROP TABLE IF EXISTS PointHistory;
DROP TABLE IF EXISTS LevelDefinition;

-- ============================================================================
-- STEP 9: Verify migration
-- ============================================================================

-- Check user points
SELECT UserId, FirstName, LastName, Points, CurrentStreak FROM User LIMIT 10;

-- Check levels
SELECT * FROM Level ORDER BY LevelNumber;

-- Check lesson progress
SELECT COUNT(*) as total_progress_records FROM LessonProgress;

-- Done!
SELECT 'Migration completed successfully!' as Status;
