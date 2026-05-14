# Course Player Feature - Setup Guide

## Overview
The Course Player is a fully functional end-to-end feature that allows users to watch course lessons, track their progress, and mark lessons as complete.

## Database Setup

### 1. Run the Database Migration
Execute the following SQL file to create the necessary tables:

```bash
mysql -u your_username -p taleeq_db < database/course_player_tables.sql
```

This creates:
- **LessonProgress** table - Tracks individual lesson completion and watch time
- **Trigger** - Automatically updates enrollment progress when lessons are completed

### 2. Verify Tables
Check that the tables were created successfully:

```sql
USE taleeq_db;
SHOW TABLES LIKE 'LessonProgress';
DESCRIBE LessonProgress;
```

## Backend Components

### Models
1. **Lesson.php** (`models/Lesson.php`)
   - `getLessonsByCourse($courseId, $userId)` - Get all lessons for a course with progress
   - `getLessonById($lessonId, $userId)` - Get single lesson details
   - `getNextLesson($currentLessonId, $courseId)` - Get next lesson in sequence
   - `getPreviousLesson($currentLessonId, $courseId)` - Get previous lesson
   - `markAsComplete($userId, $lessonId, $courseId)` - Mark lesson as completed
   - `updateWatchTime($userId, $lessonId, $courseId, $watchTimeSeconds)` - Track watch time
   - `getCourseProgress($userId, $courseId)` - Get overall course progress
   - `getLessonsBySectionGrouped($courseId, $userId)` - Get lessons grouped by section

2. **Enrollment.php** (`models/Enrollment.php`)
   - `isUserEnrolled($userId, $courseId)` - Check if user is enrolled
   - `getEnrollment($userId, $courseId)` - Get enrollment details
   - `getUserEnrollments($userId)` - Get all user enrollments
   - `createEnrollment($userId, $courseId)` - Create new enrollment
   - `updateProgress($userId, $courseId, $progressPercentage, $status)` - Update progress

### API Endpoints
**File:** `api/course_player.php`

All endpoints require authentication (session-based).

#### 1. Get Lesson Details
```
GET /api/course_player.php?action=get_lesson&lesson_id={id}
```

**Response:**
```json
{
  "success": true,
  "lesson": {
    "LessonId": 1,
    "CourseId": 1,
    "Title": "Introduction to AI",
    "Description": "...",
    "ContentType": "video",
    "ContentUrl": "/content/ml-intro.mp4",
    "Duration": 45,
    "IsCompleted": false,
    "CourseTitle": "Machine Learning"
  },
  "next_lesson": { "LessonId": 2, "Title": "..." },
  "previous_lesson": null
}
```

#### 2. Get Course Content
```
GET /api/course_player.php?action=get_course_content&course_id={id}
```

**Response:**
```json
{
  "success": true,
  "course": { "CourseId": 1, "Title": "...", ... },
  "sections": [
    {
      "section_title": "Getting Started",
      "lessons": [...]
    }
  ],
  "progress": {
    "total_lessons": 10,
    "completed_lessons": 3,
    "progress_percentage": 30.00
  }
}
```

#### 3. Mark Lesson as Complete
```
POST /api/course_player.php?action=mark_complete
Content-Type: application/json

{
  "lesson_id": 1,
  "course_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Lesson marked as complete",
  "progress": {
    "total_lessons": 10,
    "completed_lessons": 4,
    "progress_percentage": 40.00
  }
}
```

#### 4. Update Watch Time
```
POST /api/course_player.php?action=update_watch_time
Content-Type: application/json

{
  "lesson_id": 1,
  "course_id": 1,
  "watch_time": 120
}
```

#### 5. Get Progress
```
GET /api/course_player.php?action=get_progress&course_id={id}
```

## Frontend Components

### HTML Page
**File:** `pages/user/course_player.html`

Access the course player with URL parameters:
```
/pages/user/course_player.html?course_id=1&lesson_id=1
```

- If `lesson_id` is not provided, the first lesson will be loaded automatically
- The page requires user to be logged in and enrolled in the course

### JavaScript
**File:** `js/course_player.js`

**Main Class:** `CoursePlayer`

**Key Features:**
- Dynamic lesson loading
- Progress tracking
- Mark as complete functionality
- Next/Previous lesson navigation
- Watch time tracking (updates every 5 seconds)
- Section-based curriculum display
- URL state management

### CSS
**File:** `css/course_player.css`

Includes styles for:
- Video player container
- Lesson sidebar with sections
- Progress bars
- Completion states
- Navigation buttons
- Success notifications

## Usage Flow

### 1. User Enrollment
Before accessing the course player, users must be enrolled in the course. This typically happens after purchase/checkout.

```php
$enrollmentModel = new Enrollment();
$enrollmentModel->createEnrollment($userId, $courseId);
```

### 2. Accessing Course Player
Users navigate to:
```
/pages/user/course_player.html?course_id=1
```

### 3. Lesson Completion
1. User watches the lesson content
2. Clicks the "Mark as Complete" button
3. System updates `LessonProgress` table
4. Database trigger automatically updates `Enrollment` progress
5. UI updates to show completion status
6. Success notification appears

### 4. Progress Tracking
- **Automatic:** Watch time is tracked every 5 seconds while on the page
- **Manual:** User can mark lessons complete at any time
- **Visual:** Progress bar shows overall course completion percentage

## Testing

### 1. Create Test Data
Ensure you have:
- A user account (logged in)
- A course with lessons in the database
- An enrollment record for the user

```sql
-- Create test enrollment
INSERT INTO Enrollment (UserId, CourseId, EnrollmentDate, CompletionStatus)
VALUES (1, 1, NOW(), 'not_started');
```

### 2. Test Scenarios

**Scenario 1: Load Course Player**
- Navigate to course player with valid course_id
- Verify lessons load in sidebar
- Verify first lesson displays

**Scenario 2: Mark Lesson Complete**
- Click "Mark as Complete" button
- Verify success notification appears
- Verify lesson shows as completed in sidebar
- Verify progress bar updates

**Scenario 3: Navigate Between Lessons**
- Click "Next Lesson" button
- Verify new lesson loads
- Verify URL updates
- Click lesson in sidebar
- Verify lesson loads

**Scenario 4: Progress Persistence**
- Mark several lessons complete
- Refresh the page
- Verify completed lessons remain marked
- Verify progress percentage is correct

## Security Features

1. **Authentication Required:** All API endpoints check for valid session
2. **Enrollment Verification:** Users must be enrolled to access course content
3. **SQL Injection Prevention:** All queries use prepared statements
4. **XSS Protection:** Output is properly escaped in frontend

## Error Handling

The system handles:
- Unauthorized access (401)
- Missing course/lesson (404)
- Not enrolled (403)
- Invalid parameters (400)
- Server errors (500)

## Future Enhancements

Potential additions:
- Video playback tracking (pause/resume position)
- Downloadable resources per lesson
- Notes and bookmarks
- Q&A section
- Certificate generation on course completion
- Course reviews after completion

## Troubleshooting

### Issue: Lessons not loading
- Check user is logged in
- Verify enrollment exists
- Check browser console for errors
- Verify database connection

### Issue: Progress not updating
- Check database trigger was created successfully
- Verify LessonProgress table exists
- Check API response in network tab

### Issue: Mark complete not working
- Verify user is enrolled
- Check session is active
- Review server error logs
