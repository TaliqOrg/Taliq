-- Wishlist Table Schema
-- Run this SQL to create the Wishlist table in your database

CREATE TABLE IF NOT EXISTS Wishlist (
    WishlistId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    CourseId INT NULL,
    WorkshopId INT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (UserId) REFERENCES User(UserId) ON DELETE CASCADE,
    FOREIGN KEY (CourseId) REFERENCES Course(CourseId) ON DELETE CASCADE,
    FOREIGN KEY (WorkshopId) REFERENCES Workshop(WorkshopId) ON DELETE CASCADE,
    
    -- Ensure either CourseId or WorkshopId is set, but not both
    CONSTRAINT chk_course_or_workshop CHECK (
        (CourseId IS NOT NULL AND WorkshopId IS NULL) OR 
        (CourseId IS NULL AND WorkshopId IS NOT NULL)
    ),
    
    -- Prevent duplicate wishlist entries for the same user and course/workshop
    UNIQUE KEY unique_user_course (UserId, CourseId),
    UNIQUE KEY unique_user_workshop (UserId, WorkshopId)
);

-- Index for faster lookups
CREATE INDEX idx_wishlist_user ON Wishlist(UserId);
CREATE INDEX idx_wishlist_course ON Wishlist(CourseId);
CREATE INDEX idx_wishlist_workshop ON Wishlist(WorkshopId);
