-- Points and Rewards System
-- Created: 2026-05-13

USE taleeq_db;

-- ============================================
-- UserPoints Table
-- Tracks total points for each user
-- ============================================
CREATE TABLE IF NOT EXISTS UserPoints (
    UserPointsId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL UNIQUE,
    TotalPoints INT DEFAULT 0,
    LifetimePoints INT DEFAULT 0,
    LastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    INDEX idx_user (UserId),
    INDEX idx_total_points (TotalPoints)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PointsTransaction Table
-- Records all point transactions (earned/spent)
-- ============================================
CREATE TABLE IF NOT EXISTS PointsTransaction (
    TransactionId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    Points INT NOT NULL,
    TransactionType ENUM('earned', 'spent', 'bonus', 'refund') NOT NULL DEFAULT 'earned',
    Source ENUM('lesson_completion', 'course_completion', 'purchase', 'review', 'referral', 'bonus', 'admin') NOT NULL,
    SourceId INT NULL,
    Description VARCHAR(255),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    INDEX idx_user (UserId),
    INDEX idx_type (TransactionType),
    INDEX idx_source (Source),
    INDEX idx_created (CreatedAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PointsConfig Table
-- Configuration for points awarded for different actions
-- ============================================
CREATE TABLE IF NOT EXISTS PointsConfig (
    ConfigId INT AUTO_INCREMENT PRIMARY KEY,
    ActionType VARCHAR(50) NOT NULL UNIQUE,
    PointsAwarded INT NOT NULL DEFAULT 0,
    Description TEXT,
    IsActive BOOLEAN DEFAULT TRUE,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_action (ActionType),
    INDEX idx_active (IsActive)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert Default Points Configuration
-- ============================================
INSERT INTO PointsConfig (ActionType, PointsAwarded, Description, IsActive) VALUES
('lesson_completion', 50, 'Points awarded for completing a lesson', TRUE),
('course_completion', 500, 'Points awarded for completing an entire course', TRUE),
('purchase_course', 100, 'Points awarded for purchasing a course', TRUE),
('purchase_workshop', 150, 'Points awarded for purchasing a workshop', TRUE),
('write_review', 25, 'Points awarded for writing a course review', TRUE),
('referral', 200, 'Points awarded for successful referral', TRUE)
ON DUPLICATE KEY UPDATE 
    PointsAwarded = VALUES(PointsAwarded),
    Description = VALUES(Description);

-- ============================================
-- Stored Procedure: Award Points
-- ============================================
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS AwardPoints(
    IN p_user_id INT,
    IN p_points INT,
    IN p_transaction_type VARCHAR(20),
    IN p_source VARCHAR(50),
    IN p_source_id INT,
    IN p_description VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error awarding points';
    END;
    
    START TRANSACTION;
    
    -- Insert transaction record
    INSERT INTO PointsTransaction (UserId, Points, TransactionType, Source, SourceId, Description)
    VALUES (p_user_id, p_points, p_transaction_type, p_source, p_source_id, p_description);
    
    -- Update or create user points record
    INSERT INTO UserPoints (UserId, TotalPoints, LifetimePoints)
    VALUES (p_user_id, p_points, p_points)
    ON DUPLICATE KEY UPDATE
        TotalPoints = TotalPoints + p_points,
        LifetimePoints = LifetimePoints + p_points;
    
    COMMIT;
END//

DELIMITER ;

-- ============================================
-- Trigger: Award Points on Lesson Completion
-- ============================================
DELIMITER //

CREATE TRIGGER IF NOT EXISTS award_points_on_lesson_complete
AFTER UPDATE ON LessonProgress
FOR EACH ROW
BEGIN
    DECLARE points_to_award INT;
    
    -- Only award points when lesson is newly completed
    IF NEW.IsCompleted = TRUE AND (OLD.IsCompleted = FALSE OR OLD.IsCompleted IS NULL) THEN
        -- Get points configuration for lesson completion
        SELECT PointsAwarded INTO points_to_award
        FROM PointsConfig
        WHERE ActionType = 'lesson_completion' AND IsActive = TRUE
        LIMIT 1;
        
        -- Award points if configuration exists
        IF points_to_award IS NOT NULL AND points_to_award > 0 THEN
            CALL AwardPoints(
                NEW.UserId,
                points_to_award,
                'earned',
                'lesson_completion',
                NEW.LessonId,
                CONCAT('Completed lesson ID: ', NEW.LessonId)
            );
        END IF;
    END IF;
END//

DELIMITER ;

-- ============================================
-- Trigger: Award Points on Course Completion
-- ============================================
DELIMITER //

CREATE TRIGGER IF NOT EXISTS award_points_on_course_complete
AFTER UPDATE ON Enrollment
FOR EACH ROW
BEGIN
    DECLARE points_to_award INT;
    
    -- Only award points when course is newly completed
    IF NEW.CompletionStatus = 'completed' AND OLD.CompletionStatus != 'completed' THEN
        -- Get points configuration for course completion
        SELECT PointsAwarded INTO points_to_award
        FROM PointsConfig
        WHERE ActionType = 'course_completion' AND IsActive = TRUE
        LIMIT 1;
        
        -- Award points if configuration exists
        IF points_to_award IS NOT NULL AND points_to_award > 0 THEN
            CALL AwardPoints(
                NEW.UserId,
                points_to_award,
                'earned',
                'course_completion',
                NEW.CourseId,
                CONCAT('Completed course ID: ', NEW.CourseId)
            );
        END IF;
    END IF;
END//

DELIMITER ;
