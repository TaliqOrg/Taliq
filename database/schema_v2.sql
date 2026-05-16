-- ============================================================================
-- TALEEQ DATABASE SCHEMA v2.0 (Refactored)
-- Created: 2026-05-14
-- Description: Clean, optimized database structure with unified points system
-- ============================================================================

-- Drop existing database and recreate
DROP DATABASE IF EXISTS taleeq_db;
CREATE DATABASE taleeq_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE taleeq_db;

-- ============================================================================
-- 1. USER TABLE (with integrated points)
-- ============================================================================
CREATE TABLE User (
    UserId INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    PhoneNumber VARCHAR(20),
    DateOfBirth DATE,
    Country VARCHAR(100),
    City VARCHAR(100),
    ProfileImageUrl VARCHAR(500),
    Role ENUM('admin', 'user', 'instructor') NOT NULL DEFAULT 'user',
    Points INT DEFAULT 0,
    CurrentStreak INT DEFAULT 0,
    LongestStreak INT DEFAULT 0,
    LastActivityDate DATE,
    IsActive BOOLEAN DEFAULT TRUE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (Email),
    INDEX idx_role (Role),
    INDEX idx_points (Points)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. LEVEL DEFINITION TABLE (Static levels)
-- ============================================================================
CREATE TABLE Level (
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

-- ============================================================================
-- 3. CATEGORY TABLE
-- ============================================================================
CREATE TABLE Category (
    CategoryId INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL UNIQUE,
    Description TEXT,
    IconName VARCHAR(50),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (Name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. COURSE TABLE
-- ============================================================================
CREATE TABLE Course (
    CourseId INT AUTO_INCREMENT PRIMARY KEY,
    CategoryId INT NOT NULL,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    LearningOutcomes TEXT,
    Requirements TEXT,
    Price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    DurationHours DECIMAL(5, 2),
    Level ENUM('beginner', 'intermediate', 'advanced') NOT NULL DEFAULT 'beginner',
    Language VARCHAR(50) DEFAULT 'English',
    HasCertificate BOOLEAN DEFAULT TRUE,
    AverageRating DECIMAL(3, 2) DEFAULT 0.00,
    RatingCount INT DEFAULT 0,
    EnrollmentCount INT DEFAULT 0,
    ThumbnailUrl VARCHAR(500),
    IsPublished BOOLEAN DEFAULT FALSE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (CategoryId) REFERENCES Category(CategoryId) ON DELETE CASCADE,
    INDEX idx_category (CategoryId),
    INDEX idx_published (IsPublished),
    INDEX idx_level (Level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. WORKSHOP TABLE
-- ============================================================================
CREATE TABLE Workshop (
    WorkshopId INT AUTO_INCREMENT PRIMARY KEY,
    CategoryId INT NOT NULL,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    LearningOutcomes TEXT,
    Requirements TEXT,
    Price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    Location VARCHAR(255),
    DurationHours DECIMAL(5, 2),
    Level ENUM('beginner', 'intermediate', 'advanced') NOT NULL DEFAULT 'beginner',
    Capacity INT NOT NULL DEFAULT 0,
    Language VARCHAR(50) DEFAULT 'English',
    HasCertificate BOOLEAN DEFAULT TRUE,
    AverageRating DECIMAL(3, 2) DEFAULT 0.00,
    RatingCount INT DEFAULT 0,
    EnrollmentCount INT DEFAULT 0,
    ThumbnailUrl VARCHAR(500),
    IsPublished BOOLEAN DEFAULT FALSE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (CategoryId) REFERENCES Category(CategoryId) ON DELETE CASCADE,
    INDEX idx_category (CategoryId),
    INDEX idx_published (IsPublished)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. LESSON TABLE
-- ============================================================================
CREATE TABLE Lesson (
    LessonId INT AUTO_INCREMENT PRIMARY KEY,
    CourseId INT NOT NULL,
    SectionTitle VARCHAR(255),
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    ContentType ENUM('video', 'document', 'quiz', 'assignment') NOT NULL DEFAULT 'video',
    ContentUrl VARCHAR(500),
    Duration INT DEFAULT 0,
    SortOrder INT NOT NULL DEFAULT 0,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CourseId) REFERENCES Course(CourseId) ON DELETE CASCADE,
    INDEX idx_course (CourseId),
    INDEX idx_sort (SortOrder)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. WORKSHOP SESSION TABLE
-- ============================================================================
CREATE TABLE WorkshopSession (
    SessionId INT AUTO_INCREMENT PRIMARY KEY,
    WorkshopId INT NOT NULL,
    SessionDate DATE NOT NULL,
    StartTime TIME NOT NULL,
    EndTime TIME NOT NULL,
    Location VARCHAR(255),
    AvailableSeats INT NOT NULL DEFAULT 0,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (WorkshopId) REFERENCES Workshop(WorkshopId) ON DELETE CASCADE,
    INDEX idx_workshop (WorkshopId),
    INDEX idx_date (SessionDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. ENROLLMENT TABLE (Course enrollments)
-- ============================================================================
CREATE TABLE Enrollment (
    EnrollmentId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    CourseId INT NOT NULL,
    EnrollmentDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ProgressPercentage DECIMAL(5, 2) DEFAULT 0.00,
    CompletionStatus ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    CompletedAt TIMESTAMP NULL,
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    FOREIGN KEY (CourseId) REFERENCES Course(CourseId) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (UserId, CourseId),
    INDEX idx_user (UserId),
    INDEX idx_course (CourseId),
    INDEX idx_status (CompletionStatus)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 9. LESSON PROGRESS TABLE
-- ============================================================================
CREATE TABLE LessonProgress (
    ProgressId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    LessonId INT NOT NULL,
    CourseId INT NOT NULL,
    IsCompleted BOOLEAN DEFAULT FALSE,
    CompletedAt TIMESTAMP NULL,
    WatchTimeSeconds INT DEFAULT 0,
    PointsAwarded BOOLEAN DEFAULT FALSE,
    LastAccessedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    FOREIGN KEY (LessonId) REFERENCES Lesson(LessonId) ON DELETE CASCADE,
    FOREIGN KEY (CourseId) REFERENCES Course(CourseId) ON DELETE CASCADE,
    UNIQUE KEY unique_user_lesson (UserId, LessonId),
    INDEX idx_user_course (UserId, CourseId),
    INDEX idx_completed (IsCompleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 10. WORKSHOP REGISTRATION TABLE
-- ============================================================================
CREATE TABLE WorkshopRegistration (
    RegistrationId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    WorkshopId INT NOT NULL,
    SessionId INT NULL,
    RegistrationDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    AttendanceStatus ENUM('registered', 'attended', 'absent', 'cancelled') DEFAULT 'registered',
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    FOREIGN KEY (WorkshopId) REFERENCES Workshop(WorkshopId) ON DELETE CASCADE,
    FOREIGN KEY (SessionId) REFERENCES WorkshopSession(SessionId) ON DELETE SET NULL,
    UNIQUE KEY unique_registration (UserId, WorkshopId),
    INDEX idx_user (UserId),
    INDEX idx_workshop (WorkshopId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 11. CART TABLE
-- ============================================================================
CREATE TABLE Cart (
    CartId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    Status ENUM('active', 'abandoned', 'converted') DEFAULT 'active',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    INDEX idx_user (UserId),
    INDEX idx_status (Status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 12. CART ITEM TABLE
-- ============================================================================
CREATE TABLE CartItem (
    CartItemId INT AUTO_INCREMENT PRIMARY KEY,
    CartId INT NOT NULL,
    CourseId INT NULL,
    WorkshopId INT NULL,
    UnitPrice DECIMAL(10, 2) NOT NULL,
    AddedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CartId) REFERENCES Cart(CartId) ON DELETE CASCADE,
    FOREIGN KEY (CourseId) REFERENCES Course(CourseId) ON DELETE CASCADE,
    FOREIGN KEY (WorkshopId) REFERENCES Workshop(WorkshopId) ON DELETE CASCADE,
    INDEX idx_cart (CartId),
    CHECK (CourseId IS NOT NULL OR WorkshopId IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 13. DISCOUNT TABLE
-- ============================================================================
CREATE TABLE Discount (
    DiscountId INT AUTO_INCREMENT PRIMARY KEY,
    Code VARCHAR(50) NOT NULL UNIQUE,
    Description TEXT,
    DiscountType ENUM('percentage', 'fixed') NOT NULL,
    DiscountValue DECIMAL(10, 2) NOT NULL,
    StartDate DATE NOT NULL,
    EndDate DATE NOT NULL,
    IsActive BOOLEAN DEFAULT TRUE,
    INDEX idx_code (Code),
    INDEX idx_active (IsActive)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 14. ORDER TABLE
-- ============================================================================
CREATE TABLE `Order` (
    OrderId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    DiscountId INT NULL,
    OrderDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    TotalAmount DECIMAL(10, 2) NOT NULL,
    Status ENUM('pending', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    FOREIGN KEY (DiscountId) REFERENCES Discount(DiscountId) ON DELETE SET NULL,
    INDEX idx_user (UserId),
    INDEX idx_status (Status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 15. ORDER ITEM TABLE
-- ============================================================================
CREATE TABLE OrderItem (
    OrderItemId INT AUTO_INCREMENT PRIMARY KEY,
    OrderId INT NOT NULL,
    CourseId INT NULL,
    WorkshopId INT NULL,
    UnitPrice DECIMAL(10, 2) NOT NULL,
    Subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (OrderId) REFERENCES `Order`(OrderId) ON DELETE CASCADE,
    FOREIGN KEY (CourseId) REFERENCES Course(CourseId) ON DELETE SET NULL,
    FOREIGN KEY (WorkshopId) REFERENCES Workshop(WorkshopId) ON DELETE SET NULL,
    INDEX idx_order (OrderId),
    CHECK (CourseId IS NOT NULL OR WorkshopId IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 16. PAYMENT METHOD TABLE
-- ============================================================================
CREATE TABLE PaymentMethod (
    PaymentMethodId INT AUTO_INCREMENT PRIMARY KEY,
    MethodName VARCHAR(100) NOT NULL UNIQUE,
    Description TEXT,
    IsActive BOOLEAN DEFAULT TRUE,
    INDEX idx_active (IsActive)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 17. PAYMENT TABLE
-- ============================================================================
CREATE TABLE Payment (
    PaymentId INT AUTO_INCREMENT PRIMARY KEY,
    OrderId INT NOT NULL,
    PaymentMethodId INT NOT NULL,
    Amount DECIMAL(10, 2) NOT NULL,
    PaymentDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    TransactionReference VARCHAR(255),
    FOREIGN KEY (OrderId) REFERENCES `Order`(OrderId) ON DELETE CASCADE,
    FOREIGN KEY (PaymentMethodId) REFERENCES PaymentMethod(PaymentMethodId),
    INDEX idx_order (OrderId),
    INDEX idx_status (Status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 18. CERTIFICATE TABLE
-- ============================================================================
CREATE TABLE Certificate (
    CertificateId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    CourseId INT NULL,
    WorkshopId INT NULL,
    IssueDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CertificateCode VARCHAR(100) NOT NULL UNIQUE,
    CertificateUrl VARCHAR(500),
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    FOREIGN KEY (CourseId) REFERENCES Course(CourseId) ON DELETE CASCADE,
    FOREIGN KEY (WorkshopId) REFERENCES Workshop(WorkshopId) ON DELETE CASCADE,
    INDEX idx_user (UserId),
    INDEX idx_code (CertificateCode),
    CHECK (CourseId IS NOT NULL OR WorkshopId IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 19. WISHLIST TABLE
-- ============================================================================
CREATE TABLE Wishlist (
    WishlistId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    CourseId INT NULL,
    WorkshopId INT NULL,
    AddedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    FOREIGN KEY (CourseId) REFERENCES Course(CourseId) ON DELETE CASCADE,
    FOREIGN KEY (WorkshopId) REFERENCES Workshop(WorkshopId) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_course (UserId, CourseId),
    UNIQUE KEY unique_wishlist_workshop (UserId, WorkshopId),
    INDEX idx_user (UserId),
    CHECK (CourseId IS NOT NULL OR WorkshopId IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 20. REVIEW TABLE
-- ============================================================================
CREATE TABLE Review (
    ReviewId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    CourseId INT NULL,
    WorkshopId INT NULL,
    Rating INT NOT NULL CHECK (Rating BETWEEN 1 AND 5),
    Comment TEXT,
    ReviewDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    FOREIGN KEY (CourseId) REFERENCES Course(CourseId) ON DELETE CASCADE,
    FOREIGN KEY (WorkshopId) REFERENCES Workshop(WorkshopId) ON DELETE CASCADE,
    UNIQUE KEY unique_review_course (UserId, CourseId),
    UNIQUE KEY unique_review_workshop (UserId, WorkshopId),
    INDEX idx_user (UserId),
    INDEX idx_rating (Rating),
    CHECK (CourseId IS NOT NULL OR WorkshopId IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TRIGGERS
-- ============================================================================

-- Trigger: Update enrollment progress and award points on lesson completion
DELIMITER //

CREATE TRIGGER after_lesson_complete
AFTER UPDATE ON LessonProgress
FOR EACH ROW
BEGIN
    DECLARE total_lessons INT;
    DECLARE completed_lessons INT;
    DECLARE new_progress DECIMAL(5,2);
    DECLARE new_status ENUM('not_started', 'in_progress', 'completed');
    DECLARE was_completed BOOLEAN;
    
    -- Only process when lesson is newly completed
    IF NEW.IsCompleted = TRUE AND (OLD.IsCompleted = FALSE OR OLD.IsCompleted IS NULL) THEN
        
        -- Award 50 points if not already awarded
        IF NEW.PointsAwarded = FALSE OR NEW.PointsAwarded IS NULL THEN
            UPDATE User SET Points = Points + 50 WHERE UserId = NEW.UserId;
            UPDATE LessonProgress SET PointsAwarded = TRUE WHERE ProgressId = NEW.ProgressId;
        END IF;
        
        -- Count total and completed lessons
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
        IF new_progress = 0 THEN
            SET new_status = 'not_started';
        ELSEIF new_progress >= 100 THEN
            SET new_status = 'completed';
        ELSE
            SET new_status = 'in_progress';
        END IF;
        
        -- Check if course was already completed
        SELECT CompletionStatus = 'completed' INTO was_completed 
        FROM Enrollment WHERE UserId = NEW.UserId AND CourseId = NEW.CourseId;
        
        -- Update enrollment
        UPDATE Enrollment SET 
            ProgressPercentage = new_progress,
            CompletionStatus = new_status,
            CompletedAt = IF(new_status = 'completed' AND was_completed = FALSE, NOW(), CompletedAt)
        WHERE UserId = NEW.UserId AND CourseId = NEW.CourseId;
        
        -- Award bonus points for course completion (only once)
        IF new_status = 'completed' AND was_completed = FALSE THEN
            UPDATE User SET Points = Points + 50 WHERE UserId = NEW.UserId;
        END IF;
    END IF;
END//

DELIMITER ;

-- Trigger: Award points on lesson insert (first completion)
DELIMITER //

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
        UPDATE LessonProgress SET PointsAwarded = TRUE WHERE ProgressId = NEW.ProgressId;
        
        -- Update enrollment progress
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
        
        -- Award bonus for course completion
        IF new_status = 'completed' THEN
            UPDATE User SET Points = Points + 50 WHERE UserId = NEW.UserId;
        END IF;
    END IF;
END//

DELIMITER ;

-- ============================================================================
-- SEED DATA: Levels
-- ============================================================================
INSERT INTO Level (LevelNumber, LevelName, MinPoints, MaxPoints, BadgeIcon, Description) VALUES
(1, 'Course Hunter', 0, 499, 'search', 'Just getting started on your learning journey'),
(2, 'Knowledge Seeker', 500, 1499, 'menu_book', 'Building a foundation of knowledge'),
(3, 'Skill Builder', 1500, 2999, 'construction', 'Developing practical skills'),
(4, 'Learning Pro', 3000, 4999, 'school', 'Committed to continuous learning'),
(5, 'Expert Learner', 5000, 9999, 'psychology', 'Mastering multiple domains'),
(6, 'Knowledge Master', 10000, NULL, 'workspace_premium', 'A true master of learning');

-- ============================================================================
-- SEED DATA: Categories
-- ============================================================================
INSERT INTO Category (Name, Description, IconName) VALUES
('Technology', 'Programming, software development, and IT skills', 'computer'),
('Business', 'Business management, entrepreneurship, and leadership', 'business'),
('Design', 'Graphic design, UI/UX, and creative skills', 'palette'),
('Marketing', 'Digital marketing, SEO, and social media', 'campaign'),
('Data Science', 'Data analysis, machine learning, and AI', 'analytics'),
('Personal Development', 'Soft skills, productivity, and self-improvement', 'psychology');

-- ============================================================================
-- SEED DATA: Payment Methods
-- ============================================================================
INSERT INTO PaymentMethod (MethodName, Description, IsActive) VALUES
('Credit Card', 'Pay with Visa, Mastercard, or American Express', TRUE),
('Mada', 'Pay with Mada debit card', TRUE),
('Apple Pay', 'Pay with Apple Pay', TRUE),
('Bank Transfer', 'Direct bank transfer', TRUE);

-- ============================================================================
-- SEED DATA: Sample Courses
-- ============================================================================
INSERT INTO Course (CategoryId, Title, Description, LearningOutcomes, Requirements, Price, DurationHours, Level, HasCertificate, ThumbnailUrl, IsPublished) VALUES
(1, 'Complete Web Development Bootcamp', 'Learn HTML, CSS, JavaScript, React, Node.js and more to become a full-stack web developer.', 'Build responsive websites,Create web applications,Deploy to production', 'Basic computer skills,No prior coding experience needed', 499.00, 60.00, 'beginner', TRUE, '/images/courses/web-dev.jpg', TRUE),
(1, 'Python for Data Science', 'Master Python programming for data analysis, visualization, and machine learning.', 'Python programming,Data manipulation with Pandas,Machine learning basics', 'Basic math knowledge,Computer with internet', 399.00, 40.00, 'intermediate', TRUE, '/images/courses/python-ds.jpg', TRUE),
(5, 'Machine Learning A-Z', 'Comprehensive machine learning course covering supervised and unsupervised learning.', 'Build ML models,Understand algorithms,Apply to real problems', 'Python basics,Statistics fundamentals', 599.00, 50.00, 'advanced', TRUE, '/images/courses/ml-az.jpg', TRUE),
(2, 'Business Strategy Masterclass', 'Learn strategic thinking and business planning from industry experts.', 'Develop business strategies,Analyze markets,Create business plans', 'Business interest,No prerequisites', 299.00, 20.00, 'beginner', TRUE, '/images/courses/business.jpg', TRUE),
(3, 'UI/UX Design Fundamentals', 'Master the principles of user interface and user experience design.', 'Design user interfaces,Create wireframes,Conduct user research', 'Basic design interest,Computer with design software', 349.00, 30.00, 'beginner', TRUE, '/images/courses/uiux.jpg', TRUE);

-- ============================================================================
-- SEED DATA: Sample Lessons for Course 1
-- ============================================================================
INSERT INTO Lesson (CourseId, SectionTitle, Title, Description, ContentType, Duration, SortOrder) VALUES
(1, 'Getting Started', 'Introduction to Web Development', 'Overview of web development and what you will learn', 'video', 15, 1),
(1, 'Getting Started', 'Setting Up Your Environment', 'Install the tools you need to start coding', 'video', 20, 2),
(1, 'HTML Basics', 'HTML Structure and Tags', 'Learn the building blocks of web pages', 'video', 25, 3),
(1, 'HTML Basics', 'Forms and Input Elements', 'Create interactive forms', 'video', 30, 4),
(1, 'CSS Fundamentals', 'CSS Selectors and Properties', 'Style your web pages', 'video', 25, 5),
(1, 'CSS Fundamentals', 'Flexbox and Grid Layout', 'Modern CSS layout techniques', 'video', 35, 6),
(1, 'JavaScript Essentials', 'JavaScript Basics', 'Variables, functions, and control flow', 'video', 40, 7),
(1, 'JavaScript Essentials', 'DOM Manipulation', 'Make your pages interactive', 'video', 35, 8),
(1, 'React Framework', 'Introduction to React', 'Build modern user interfaces', 'video', 45, 9),
(1, 'React Framework', 'React Components and State', 'Component-based architecture', 'video', 50, 10);

-- ============================================================================
-- SEED DATA: Sample Workshops
-- ============================================================================
INSERT INTO Workshop (CategoryId, Title, Description, LearningOutcomes, Requirements, Price, Location, DurationHours, Level, Capacity, HasCertificate, ThumbnailUrl, IsPublished) VALUES
(2, 'Leadership Workshop', 'Intensive hands-on leadership training for managers and team leads.', 'Lead teams effectively,Communicate with impact,Handle conflicts', 'Management experience preferred', 799.00, 'Riyadh, Saudi Arabia', 8.00, 'intermediate', 30, TRUE, '/images/workshops/leadership.jpg', TRUE),
(4, 'Digital Marketing Bootcamp', 'One-day intensive workshop on digital marketing strategies.', 'Create marketing campaigns,Use analytics tools,Social media mastery', 'Basic marketing knowledge', 599.00, 'Jeddah, Saudi Arabia', 8.00, 'beginner', 25, TRUE, '/images/workshops/marketing.jpg', TRUE);

-- ============================================================================
-- SEED DATA: Sample User (for testing)
-- ============================================================================
INSERT INTO User (FirstName, LastName, Email, PasswordHash, Role, Points) VALUES
('Ahmed', 'Ali', 'ahmed@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 0),
('Admin', 'User', 'admin@taleeq.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0);

-- ============================================================================
-- SEED DATA: Sample Enrollment (for testing)
-- ============================================================================
INSERT INTO Enrollment (UserId, CourseId, ProgressPercentage, CompletionStatus) VALUES
(1, 1, 0.00, 'not_started');
