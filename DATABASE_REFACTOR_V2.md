# Database Refactor v2.0 - Complete Guide

## 🎯 Overview

This refactor simplifies the database structure by:
1. **Removing separate points tables** - Points now stored directly in User table
2. **Unified 50 points** for all actions (lesson completion, course completion)
3. **Single Level table** for static level definitions
4. **Simplified triggers** for automatic progress and points updates
5. **Removed unused tables** and redundant columns

---

## 📊 Schema Changes

### Before (Old Structure)
```
User
├── UserPoints (separate table)
├── PointsTransaction (separate table)
├── PointsConfig (separate table)
├── UserGamification (separate table)
├── PointAction (separate table)
├── PointHistory (separate table)
└── LevelDefinition (separate table)
```

### After (New Structure)
```
User
├── Points (INT) - Direct column
├── CurrentStreak (INT)
├── LongestStreak (INT)
├── LastActivityDate (DATE)
└── Level (single table for definitions)
```

---

## 🗄️ New Database Structure

### User Table (Updated)
```sql
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
    Role ENUM('admin', 'user', 'instructor') DEFAULT 'user',
    Points INT DEFAULT 0,              -- NEW: Direct points storage
    CurrentStreak INT DEFAULT 0,       -- NEW: Streak tracking
    LongestStreak INT DEFAULT 0,       -- NEW: Best streak
    LastActivityDate DATE,             -- NEW: For streak calculation
    IsActive BOOLEAN DEFAULT TRUE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Level Table (Simplified)
```sql
CREATE TABLE Level (
    LevelId INT AUTO_INCREMENT PRIMARY KEY,
    LevelNumber INT NOT NULL UNIQUE,
    LevelName VARCHAR(100) NOT NULL,
    MinPoints INT NOT NULL,
    MaxPoints INT,
    BadgeIcon VARCHAR(100),
    Description TEXT
);
```

### LessonProgress Table (Updated)
```sql
CREATE TABLE LessonProgress (
    ProgressId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    LessonId INT NOT NULL,
    CourseId INT NOT NULL,
    IsCompleted BOOLEAN DEFAULT FALSE,
    CompletedAt TIMESTAMP NULL,
    WatchTimeSeconds INT DEFAULT 0,
    PointsAwarded BOOLEAN DEFAULT FALSE,  -- NEW: Prevent duplicate points
    LastAccessedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🔄 Points System

### Unified Points (50 per action)
- **Lesson Completion**: 50 points
- **Course Completion**: 50 points (bonus)
- **All other actions**: 50 points

### How Points Are Awarded
1. User completes a lesson
2. Database trigger fires
3. Checks `PointsAwarded` flag to prevent duplicates
4. Updates `User.Points` directly
5. Updates enrollment progress

### Trigger Logic
```sql
-- When lesson is completed:
IF NEW.IsCompleted = TRUE AND OLD.IsCompleted = FALSE THEN
    -- Award 50 points if not already awarded
    IF OLD.PointsAwarded = FALSE THEN
        UPDATE User SET Points = Points + 50 WHERE UserId = NEW.UserId;
    END IF;
    
    -- Update enrollment progress
    -- If course completed, award bonus 50 points
END IF;
```

---

## 📁 Files Created/Updated

### New Files (v2 versions)
```
database/
├── schema_v2.sql              # Complete new schema
├── migration_v2.sql           # Migration script

models/
├── Profile_v2.php             # Simplified profile model
├── Lesson_v2.php              # Simplified lesson model

controllers/
├── ProfileController_v2.php   # Simplified controller

api/
├── course_player_v2.php       # Simplified API
├── points_v2.php              # Simplified points API

js/
├── progress_sync.js           # Updated for new structure
```

---

## 🚀 Migration Steps

### Option 1: Fresh Install (Recommended for new projects)
```bash
# Run the new schema
mysql -u root -p < database/schema_v2.sql
```

### Option 2: Migrate Existing Data
```bash
# Run migration script
mysql -u root -p taleeq_db < database/migration_v2.sql
```

### After Migration
1. Replace old model files with v2 versions
2. Replace old controller files with v2 versions
3. Replace old API files with v2 versions
4. Test all functionality

---

## 🔧 API Changes

### Get User Points
**Old:**
```
GET /api/points.php?action=get_user_points
Response: { points: { TotalPoints, LifetimePoints } }
```

**New:**
```
GET /api/points.php?action=get_user_points
Response: { points: { TotalPoints, CurrentStreak, LongestStreak }, level: {...} }
```

### Mark Lesson Complete
**Old:**
```
POST /api/course_player.php?action=mark_complete
Response: { success, progress, points_awarded }
```

**New:**
```
POST /api/course_player.php?action=mark_complete
Response: { success, progress, points_awarded, user_points }
```

---

## 📊 Level Definitions

| Level | Name | Min Points | Max Points | Badge |
|-------|------|------------|------------|-------|
| 1 | Course Hunter | 0 | 499 | search |
| 2 | Knowledge Seeker | 500 | 1,499 | menu_book |
| 3 | Skill Builder | 1,500 | 2,999 | construction |
| 4 | Learning Pro | 3,000 | 4,999 | school |
| 5 | Expert Learner | 5,000 | 9,999 | psychology |
| 6 | Knowledge Master | 10,000+ | - | workspace_premium |

---

## ✅ Benefits

### Performance
- **Fewer JOINs**: Points in User table = no joins needed
- **Simpler queries**: Direct column access
- **Faster reads**: No separate points lookup

### Maintainability
- **Less code**: Fewer models and controllers
- **Clearer logic**: One place for points
- **Easier debugging**: Direct database inspection

### Consistency
- **Single source**: Points always from User table
- **No sync issues**: Triggers handle everything
- **Duplicate prevention**: PointsAwarded flag

---

## 🧪 Testing

### Test Points Award
```sql
-- Check user points
SELECT UserId, FirstName, Points FROM User WHERE UserId = 1;

-- Complete a lesson (should trigger points)
UPDATE LessonProgress 
SET IsCompleted = TRUE 
WHERE UserId = 1 AND LessonId = 1;

-- Check points again (should be +50)
SELECT UserId, FirstName, Points FROM User WHERE UserId = 1;
```

### Test Progress Sync
```sql
-- Check enrollment progress
SELECT * FROM Enrollment WHERE UserId = 1;

-- Should match lesson completion percentage
SELECT 
    COUNT(*) as total,
    SUM(IsCompleted) as completed,
    (SUM(IsCompleted) / COUNT(*)) * 100 as percentage
FROM LessonProgress 
WHERE UserId = 1 AND CourseId = 1;
```

---

## 🔒 Duplicate Prevention

### How It Works
1. `PointsAwarded` column in LessonProgress
2. Trigger checks this flag before awarding
3. Flag set to TRUE after points awarded
4. Subsequent completions don't award again

### SQL Check
```sql
-- Find lessons that awarded points
SELECT * FROM LessonProgress 
WHERE UserId = 1 AND PointsAwarded = TRUE;
```

---

## 📝 Removed Tables

The following tables are no longer needed:
- `UserPoints` - Points now in User table
- `PointsTransaction` - Simplified, no history needed
- `PointsConfig` - Unified 50 points for all
- `UserGamification` - Merged into User table
- `PointAction` - Simplified
- `PointHistory` - Simplified
- `LevelDefinition` - Renamed to `Level`

---

## 🎯 Summary

### What Changed
✅ Points stored directly in User table
✅ Unified 50 points for all actions
✅ Single Level table for definitions
✅ Simplified triggers
✅ Removed 6+ unused tables
✅ Cleaner API responses
✅ Better performance

### What Stayed Same
✅ Enrollment tracking
✅ Lesson progress
✅ Course completion
✅ Level system
✅ Streak tracking

---

## 🚨 Important Notes

1. **Backup first**: Always backup before migration
2. **Test thoroughly**: Verify points and progress after migration
3. **Update all files**: Replace old models/controllers/APIs
4. **Clear cache**: Browser and server cache
5. **Check triggers**: Verify triggers are created

---

## 📞 Troubleshooting

### Points Not Updating
```sql
-- Check trigger exists
SHOW TRIGGERS LIKE 'after_lesson%';

-- Check PointsAwarded flag
SELECT * FROM LessonProgress WHERE UserId = 1;
```

### Progress Not Syncing
```sql
-- Manually recalculate progress
UPDATE Enrollment e
SET ProgressPercentage = (
    SELECT (COUNT(CASE WHEN lp.IsCompleted THEN 1 END) / COUNT(*)) * 100
    FROM Lesson l
    LEFT JOIN LessonProgress lp ON l.LessonId = lp.LessonId AND lp.UserId = e.UserId
    WHERE l.CourseId = e.CourseId
)
WHERE e.UserId = 1;
```

### Level Not Showing
```sql
-- Check Level table has data
SELECT * FROM Level ORDER BY LevelNumber;

-- If empty, insert defaults
INSERT INTO Level (LevelNumber, LevelName, MinPoints, MaxPoints, BadgeIcon) VALUES
(1, 'Course Hunter', 0, 499, 'search'),
(2, 'Knowledge Seeker', 500, 1499, 'menu_book'),
(3, 'Skill Builder', 1500, 2999, 'construction'),
(4, 'Learning Pro', 3000, 4999, 'school'),
(5, 'Expert Learner', 5000, 9999, 'psychology'),
(6, 'Knowledge Master', 10000, NULL, 'workspace_premium');
```

---

## ✨ Done!

The database is now simplified and optimized. All points, progress, and levels work from a unified structure with better performance and maintainability.
