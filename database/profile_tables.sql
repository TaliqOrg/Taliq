-- ============================================
-- User Gamification Table
-- Tracks user points, streaks, and level progress
-- ============================================
CREATE TABLE IF NOT EXISTS UserGamification (
    GamificationId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL UNIQUE,
    TotalPoints INT DEFAULT 0,
    CurrentStreak INT DEFAULT 0,
    LongestStreak INT DEFAULT 0,
    LastActivityDate DATE NULL,
    Level INT DEFAULT 1,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    INDEX idx_user (UserId),
    INDEX idx_points (TotalPoints),
    INDEX idx_level (Level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Point History Table
-- Tracks how users earned points
-- ============================================
CREATE TABLE IF NOT EXISTS PointHistory (
    PointHistoryId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    Points INT NOT NULL,
    Action VARCHAR(100) NOT NULL,
    Description VARCHAR(255),
    CourseId INT NULL,
    WorkshopId INT NULL,
    EarnedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    FOREIGN KEY (CourseId) REFERENCES Course(CourseId) ON DELETE SET NULL,
    FOREIGN KEY (WorkshopId) REFERENCES Workshop(WorkshopId) ON DELETE SET NULL,
    INDEX idx_user (UserId),
    INDEX idx_action (Action),
    INDEX idx_earned (EarnedAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Level Definitions Table
-- Defines level thresholds and names
-- ============================================
CREATE TABLE IF NOT EXISTS LevelDefinition (
    LevelId INT AUTO_INCREMENT PRIMARY KEY,
    LevelNumber INT NOT NULL UNIQUE,
    LevelName VARCHAR(100) NOT NULL,
    MinPoints INT NOT NULL,
    MaxPoints INT NULL,
    BadgeIcon VARCHAR(100) DEFAULT 'emoji_events',
    Description VARCHAR(255),
    INDEX idx_level (LevelNumber),
    INDEX idx_points (MinPoints)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default level definitions
INSERT INTO LevelDefinition (LevelNumber, LevelName, MinPoints, MaxPoints, Description) VALUES
(1, 'Course Hunter', 0, 1000, 'Just getting started on your learning journey'),
(2, 'Knowledge Seeker', 1001, 5000, 'Building a strong foundation of knowledge'),
(3, 'Learning Master', 5001, 10000, 'Mastering multiple subjects'),
(4, 'Elite Scholar', 10001, NULL, 'A true expert and lifelong learner');

-- ============================================
-- Point Actions Configuration
-- Defines how many points each action gives
-- ============================================
CREATE TABLE IF NOT EXISTS PointAction (
    ActionId INT AUTO_INCREMENT PRIMARY KEY,
    ActionName VARCHAR(100) NOT NULL UNIQUE,
    Points INT NOT NULL,
    Description VARCHAR(255),
    IsActive BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default point actions
INSERT INTO PointAction (ActionName, Points, Description) VALUES
('course_complete', 500, 'Complete a course'),
('workshop_attend', 300, 'Attend a workshop'),
('lesson_complete', 50, 'Complete a lesson'),
('daily_login', 10, 'Daily login streak'),
('first_purchase', 100, 'First course purchase'),
('leave_review', 25, 'Leave a course review'),
('certificate_earned', 200, 'Earn a certificate');

-- ============================================
-- Add missing columns to User table if needed
-- ============================================
ALTER TABLE User 
ADD COLUMN IF NOT EXISTS DateOfBirth DATE NULL,
ADD COLUMN IF NOT EXISTS Country VARCHAR(100) NULL,
ADD COLUMN IF NOT EXISTS City VARCHAR(100) NULL,
ADD COLUMN IF NOT EXISTS ProfileImageUrl VARCHAR(500) NULL;
