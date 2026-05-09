-- Taleeq Database Schema
-- Created: 2026-05-09
-- Database: taleeq_db

CREATE DATABASE IF NOT EXISTS taleeq_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE taleeq_db;

-- ============================================
-- 1. User Table
-- ============================================
CREATE TABLE User (
    UserId INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    PhoneNumber VARCHAR(20),
    Role ENUM('admin', 'user', 'instructor') NOT NULL DEFAULT 'user',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    IsActive BOOLEAN DEFAULT TRUE,
    INDEX idx_email (Email),
    INDEX idx_role (Role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. Category Table
-- ============================================
CREATE TABLE Category (
    CategoryId INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL UNIQUE,
    Description TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (Name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. Course Table
-- ============================================
CREATE TABLE Course (
    CourseId INT AUTO_INCREMENT PRIMARY KEY,
    CategoryId INT NOT NULL,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    Price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    DurationHours DECIMAL(5, 2),
    Level ENUM('beginner', 'intermediate', 'advanced') NOT NULL DEFAULT 'beginner',
    Language VARCHAR(50) DEFAULT 'English',
    ThumbnailUrl VARCHAR(500),
    IsPublished BOOLEAN DEFAULT FALSE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (CategoryId) REFERENCES Category(CategoryId) ON DELETE CASCADE,
    INDEX idx_category (CategoryId),
    INDEX idx_published (IsPublished),
    INDEX idx_level (Level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. Workshop Table
-- ============================================
CREATE TABLE Workshop (
    WorkshopId INT AUTO_INCREMENT PRIMARY KEY,
    CategoryId INT NOT NULL,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    Price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    Location VARCHAR(255),
    Capacity INT NOT NULL DEFAULT 0,
    Language VARCHAR(50) DEFAULT 'English',
    IsPublished BOOLEAN DEFAULT FALSE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (CategoryId) REFERENCES Category(CategoryId) ON DELETE CASCADE,
    INDEX idx_category (CategoryId),
    INDEX idx_published (IsPublished)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. Lesson Table
-- ============================================
CREATE TABLE Lesson (
    LessonId INT AUTO_INCREMENT PRIMARY KEY,
    CourseId INT NOT NULL,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    ContentType ENUM('video', 'document', 'quiz', 'assignment') NOT NULL,
    ContentUrl VARCHAR(500),
    OrderNumber INT NOT NULL,
    DurationMinutes INT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CourseId) REFERENCES Course(CourseId) ON DELETE CASCADE,
    INDEX idx_course (CourseId),
    INDEX idx_order (OrderNumber)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. WorkshopSession Table
-- ============================================
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

-- ============================================
-- 7. Cart Table
-- ============================================
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

-- ============================================
-- 8. CartItem Table
-- ============================================
CREATE TABLE CartItem (
    CartItemId INT AUTO_INCREMENT PRIMARY KEY,
    CartId INT NOT NULL,
    CourseId INT NULL,
    WorkshopId INT NULL,
    Quantity INT NOT NULL DEFAULT 1,
    UnitPrice DECIMAL(10, 2) NOT NULL,
    AddedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CartId) REFERENCES Cart(CartId) ON DELETE CASCADE,
    FOREIGN KEY (CourseId) REFERENCES Course(CourseId) ON DELETE CASCADE,
    FOREIGN KEY (WorkshopId) REFERENCES Workshop(WorkshopId) ON DELETE CASCADE,
    INDEX idx_cart (CartId),
    CHECK (CourseId IS NOT NULL OR WorkshopId IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. Discount Table
-- ============================================
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
    INDEX idx_active (IsActive),
    INDEX idx_dates (StartDate, EndDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. Order Table
-- ============================================
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
    INDEX idx_status (Status),
    INDEX idx_order_date (OrderDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. OrderItem Table
-- ============================================
CREATE TABLE OrderItem (
    OrderItemId INT AUTO_INCREMENT PRIMARY KEY,
    OrderId INT NOT NULL,
    CourseId INT NULL,
    WorkshopId INT NULL,
    Quantity INT NOT NULL DEFAULT 1,
    UnitPrice DECIMAL(10, 2) NOT NULL,
    Subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (OrderId) REFERENCES `Order`(OrderId) ON DELETE CASCADE,
    FOREIGN KEY (CourseId) REFERENCES Course(CourseId) ON DELETE SET NULL,
    FOREIGN KEY (WorkshopId) REFERENCES Workshop(WorkshopId) ON DELETE SET NULL,
    INDEX idx_order (OrderId),
    CHECK (CourseId IS NOT NULL OR WorkshopId IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. PaymentMethod Table
-- ============================================
CREATE TABLE PaymentMethod (
    PaymentMethodId INT AUTO_INCREMENT PRIMARY KEY,
    MethodName VARCHAR(100) NOT NULL UNIQUE,
    Description TEXT,
    IsActive BOOLEAN DEFAULT TRUE,
    INDEX idx_active (IsActive)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 13. Payment Table
-- ============================================
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
    INDEX idx_status (Status),
    INDEX idx_transaction (TransactionReference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 14. Enrollment Table
-- ============================================
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

-- ============================================
-- 15. WorkshopRegistration Table
-- ============================================
CREATE TABLE WorkshopRegistration (
    WorkshopRegistrationId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    WorkshopId INT NOT NULL,
    SessionId INT NULL,
    RegistrationDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    AttendanceStatus ENUM('registered', 'attended', 'absent', 'cancelled') DEFAULT 'registered',
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    FOREIGN KEY (WorkshopId) REFERENCES Workshop(WorkshopId) ON DELETE CASCADE,
    FOREIGN KEY (SessionId) REFERENCES WorkshopSession(SessionId) ON DELETE SET NULL,
    INDEX idx_user (UserId),
    INDEX idx_workshop (WorkshopId),
    INDEX idx_session (SessionId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 16. Certificate Table
-- ============================================
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

-- ============================================
-- 17. Wishlist Table
-- ============================================
CREATE TABLE Wishlist (
    WishlistId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    CourseId INT NULL,
    WorkshopId INT NULL,
    AddedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    FOREIGN KEY (CourseId) REFERENCES Course(CourseId) ON DELETE CASCADE,
    FOREIGN KEY (WorkshopId) REFERENCES Workshop(WorkshopId) ON DELETE CASCADE,
    INDEX idx_user (UserId),
    CHECK (CourseId IS NOT NULL OR WorkshopId IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 18. Review Table
-- ============================================
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
    INDEX idx_user (UserId),
    INDEX idx_course (CourseId),
    INDEX idx_workshop (WorkshopId),
    INDEX idx_rating (Rating),
    CHECK (CourseId IS NOT NULL OR WorkshopId IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
