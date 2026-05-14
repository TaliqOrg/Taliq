# Course Player - Quick Start Guide

## 🚀 Quick Setup (5 Minutes)

### Step 1: Run Database Migrations
```bash
# Navigate to your MySQL
mysql -u root -p

# Run the following commands:
source /path/to/taleeq/Taliq/database/course_player_tables.sql
source /path/to/taleeq/Taliq/database/course_player_seed.sql
```

Or using command line directly:
```bash
mysql -u root -p taleeq_db < database/course_player_tables.sql
mysql -u root -p taleeq_db < database/course_player_seed.sql
```

### Step 2: Test Login
1. Navigate to your login page
2. Login with test credentials:
   - **Email:** `ahmed@example.com`
   - **Password:** `password123`

### Step 3: Access Course Player
Navigate to:
```
http://localhost/Taliq/pages/user/course_player.html?course_id=1
```

## ✅ What You Can Do

### 1. **View Course Content**
- See all lessons organized by sections
- View lesson details and descriptions
- Track your overall progress

### 2. **Watch Lessons**
- Click any lesson in the sidebar to load it
- Video player displays the lesson content
- Watch time is automatically tracked

### 3. **Mark Lessons Complete**
- Click the ✓ button next to the lesson title
- Lesson will be marked as completed
- Progress bar updates automatically
- Success notification appears

### 4. **Navigate Between Lessons**
- Use "Next Lesson" button to proceed
- Use "Previous Lesson" button to go back
- Click any lesson in the sidebar to jump to it

### 5. **Track Progress**
- Progress bar shows completion percentage
- Completed lessons show a checkmark
- See "X of Y lessons completed" counter

## 📋 Testing Checklist

- [ ] Database tables created successfully
- [ ] Can log in with test account
- [ ] Course player page loads
- [ ] Lessons appear in sidebar
- [ ] Can click and load different lessons
- [ ] "Mark as Complete" button works
- [ ] Progress bar updates after completion
- [ ] Next/Previous buttons work
- [ ] Completed lessons show checkmark
- [ ] Page refresh maintains progress

## 🔧 Troubleshooting

### Problem: "You are not enrolled in this course"
**Solution:** Run the enrollment test script:
```
http://localhost/Taliq/api/test_enrollment.php?course_id=1
```

### Problem: Lessons not showing
**Solution:** 
1. Check that seed data was inserted: `SELECT COUNT(*) FROM Lesson WHERE CourseId = 1;`
2. Verify course exists: `SELECT * FROM Course WHERE CourseId = 1;`

### Problem: Progress not updating
**Solution:**
1. Check trigger exists: `SHOW TRIGGERS LIKE 'update_enrollment_progress%';`
2. Verify LessonProgress table: `DESCRIBE LessonProgress;`

### Problem: Not logged in
**Solution:**
1. Navigate to login page
2. Use credentials: `ahmed@example.com` / `password123`
3. Check session is active: `var_dump($_SESSION);`

## 📊 Database Quick Checks

```sql
-- Check if user is enrolled
SELECT * FROM Enrollment WHERE UserId = 2 AND CourseId = 1;

-- Check lesson progress
SELECT * FROM LessonProgress WHERE UserId = 2 AND CourseId = 1;

-- Check course lessons
SELECT LessonId, Title, SectionTitle, SortOrder 
FROM Lesson 
WHERE CourseId = 1 
ORDER BY SortOrder;

-- Check overall progress
SELECT 
    e.ProgressPercentage,
    e.CompletionStatus,
    COUNT(l.LessonId) as TotalLessons,
    SUM(CASE WHEN lp.IsCompleted = 1 THEN 1 ELSE 0 END) as CompletedLessons
FROM Enrollment e
INNER JOIN Lesson l ON e.CourseId = l.CourseId
LEFT JOIN LessonProgress lp ON l.LessonId = lp.LessonId AND lp.UserId = e.UserId
WHERE e.UserId = 2 AND e.CourseId = 1
GROUP BY e.EnrollmentId;
```

## 🎯 Key Features Implemented

✅ **Database Layer**
- LessonProgress table for tracking completion
- Automatic trigger for enrollment progress updates
- Sample data with multiple courses and lessons

✅ **Backend API**
- Get lesson details with progress
- Get course content with sections
- Mark lesson as complete
- Update watch time
- Get progress statistics

✅ **Frontend**
- Dynamic lesson loading
- Section-based curriculum sidebar
- Video/content player
- Progress tracking UI
- Mark as complete button
- Next/Previous navigation
- Success notifications
- URL state management

✅ **Security**
- Session-based authentication
- Enrollment verification
- SQL injection prevention
- XSS protection

## 📁 File Structure

```
Taliq/
├── api/
│   ├── course_player.php          # Main API endpoints
│   └── test_enrollment.php        # Testing utility
├── models/
│   ├── Lesson.php                 # Lesson model
│   └── Enrollment.php             # Enrollment model
├── js/
│   └── course_player.js           # Frontend logic
├── css/
│   └── course_player.css          # Styling
├── pages/user/
│   └── course_player.html         # Main page
└── database/
    ├── course_player_tables.sql   # Schema
    └── course_player_seed.sql     # Sample data
```

## 🔗 API Endpoints Reference

| Endpoint | Method | Description |
|----------|--------|-------------|
| `?action=get_lesson&lesson_id=X` | GET | Get lesson details |
| `?action=get_course_content&course_id=X` | GET | Get all course content |
| `?action=mark_complete` | POST | Mark lesson complete |
| `?action=update_watch_time` | POST | Update watch time |
| `?action=get_progress&course_id=X` | GET | Get progress stats |

## 💡 Tips

1. **Test with multiple users** - Create different enrollments to see how progress is tracked per user
2. **Check browser console** - Useful for debugging API calls and JavaScript errors
3. **Use network tab** - Monitor API requests and responses
4. **Test edge cases** - Try accessing without enrollment, invalid IDs, etc.

## 🎓 Next Steps

After basic testing:
1. Add real video content URLs
2. Implement certificate generation on completion
3. Add downloadable resources per lesson
4. Create notes/bookmarks feature
5. Add Q&A section for lessons
6. Implement course reviews

## 📞 Support

If you encounter issues:
1. Check browser console for JavaScript errors
2. Check PHP error logs for backend issues
3. Verify database connections
4. Review the detailed setup guide: `COURSE_PLAYER_SETUP.md`
