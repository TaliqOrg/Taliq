# Course Player - System Architecture

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER INTERFACE                           │
│                    (course_player.html)                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │ Video Player │  │ Lesson Info  │  │  Curriculum  │          │
│  │   Container  │  │   & Tabs     │  │   Sidebar    │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    JAVASCRIPT LAYER                              │
│                   (course_player.js)                             │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │              CoursePlayer Class                           │  │
│  │  • loadLesson()         • markLessonComplete()           │  │
│  │  • loadCourseContent()  • navigateToNextLesson()         │  │
│  │  • renderLesson()       • startWatchTimeTracking()       │  │
│  │  • renderSidebar()      • updateWatchTime()              │  │
│  └──────────────────────────────────────────────────────────┘  │
└────────────────────────────┬────────────────────────────────────┘
                             │ AJAX Requests
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      API LAYER                                   │
│                 (course_player.php)                              │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Endpoints:                                               │  │
│  │  • GET  /get_lesson           • POST /mark_complete      │  │
│  │  • GET  /get_course_content   • POST /update_watch_time  │  │
│  │  • GET  /get_progress                                    │  │
│  │                                                           │  │
│  │  Security:                                                │  │
│  │  ✓ Session Authentication  ✓ Enrollment Verification     │  │
│  │  ✓ Input Validation        ✓ Error Handling              │  │
│  └──────────────────────────────────────────────────────────┘  │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    BUSINESS LOGIC LAYER                          │
│                      (Models)                                    │
│  ┌──────────────────────┐      ┌──────────────────────┐        │
│  │   Lesson.php         │      │  Enrollment.php      │        │
│  │                      │      │                      │        │
│  │ • getLessonsByCourse │      │ • isUserEnrolled     │        │
│  │ • getLessonById      │      │ • getEnrollment      │        │
│  │ • getNextLesson      │      │ • createEnrollment   │        │
│  │ • getPreviousLesson  │      │ • updateProgress     │        │
│  │ • markAsComplete     │      │ • getUserEnrollments │        │
│  │ • updateWatchTime    │      │                      │        │
│  │ • getCourseProgress  │      │                      │        │
│  └──────────────────────┘      └──────────────────────┘        │
└────────────────────────────┬────────────────────────────────────┘
                             │ SQL Queries
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      DATABASE LAYER                              │
│                      (MySQL/MariaDB)                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │    Course    │  │    Lesson    │  │  Enrollment  │          │
│  │              │  │              │  │              │          │
│  │ • CourseId   │  │ • LessonId   │  │ • UserId     │          │
│  │ • Title      │  │ • CourseId   │  │ • CourseId   │          │
│  │ • Price      │  │ • Title      │  │ • Progress%  │          │
│  │ • ...        │  │ • Content    │  │ • Status     │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │              LessonProgress (NEW)                         │  │
│  │                                                           │  │
│  │ • UserId          • IsCompleted      • WatchTimeSeconds  │  │
│  │ • LessonId        • CompletedAt      • LastAccessedAt    │  │
│  │ • CourseId        • CreatedAt                            │  │
│  │                                                           │  │
│  │ UNIQUE(UserId, LessonId)                                 │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │         Database Trigger (AUTOMATIC)                      │  │
│  │                                                           │  │
│  │  ON UPDATE LessonProgress (IsCompleted = TRUE)           │  │
│  │  → Calculate total lessons                               │  │
│  │  → Calculate completed lessons                           │  │
│  │  → Update Enrollment.ProgressPercentage                  │  │
│  │  → Update Enrollment.CompletionStatus                    │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Data Flow Diagrams

### 1. Loading a Lesson

```
User clicks lesson in sidebar
         │
         ▼
JavaScript: loadLesson(lessonId)
         │
         ▼
AJAX GET: /api/course_player.php?action=get_lesson&lesson_id=X
         │
         ▼
API: Check authentication & enrollment
         │
         ▼
Lesson Model: getLessonById(lessonId, userId)
         │
         ▼
Database: SELECT lesson with progress
         │
         ▼
API: Return JSON response
         │
         ▼
JavaScript: renderLesson(data)
         │
         ▼
DOM: Update video, title, description, buttons
         │
         ▼
User sees lesson content
```

### 2. Marking Lesson Complete

```
User clicks "Mark as Complete" button
         │
         ▼
JavaScript: markLessonComplete()
         │
         ▼
AJAX POST: /api/course_player.php?action=mark_complete
         │
         ▼
API: Validate user & enrollment
         │
         ▼
Lesson Model: markAsComplete(userId, lessonId, courseId)
         │
         ▼
Database: INSERT/UPDATE LessonProgress (IsCompleted = TRUE)
         │
         ▼
Database Trigger: FIRES AUTOMATICALLY
         │
         ├─→ Count total lessons
         ├─→ Count completed lessons
         ├─→ Calculate progress %
         └─→ UPDATE Enrollment table
         │
         ▼
Lesson Model: getCourseProgress(userId, courseId)
         │
         ▼
API: Return success + updated progress
         │
         ▼
JavaScript: Update UI
         │
         ├─→ Show success notification
         ├─→ Update progress bar
         ├─→ Mark lesson with checkmark
         └─→ Update button state
         │
         ▼
User sees completion confirmation
```

### 3. Watch Time Tracking

```
User loads lesson
         │
         ▼
JavaScript: startWatchTimeTracking()
         │
         ▼
setInterval (every 5 seconds)
         │
         ▼
JavaScript: updateWatchTime()
         │
         ▼
AJAX POST: /api/course_player.php?action=update_watch_time
         │
         ▼
Lesson Model: updateWatchTime(userId, lessonId, courseId, seconds)
         │
         ▼
Database: INSERT/UPDATE LessonProgress (WatchTimeSeconds)
         │
         ▼
API: Return success
         │
         ▼
(Silent - no UI update)
         │
         ▼
Repeat every 5 seconds
```

---

## 🗂️ File Organization

```
Taliq/
│
├── api/
│   ├── course_player.php          ← Main API endpoints
│   ├── test_enrollment.php        ← Testing utility
│   └── course_player_test.php     ← Integration tests
│
├── models/
│   ├── Lesson.php                 ← Lesson business logic
│   ├── Enrollment.php             ← Enrollment business logic
│   └── Course.php                 ← Course business logic (existing)
│
├── js/
│   └── course_player.js           ← Frontend logic
│
├── css/
│   └── course_player.css          ← Styling
│
├── pages/user/
│   └── course_player.html         ← Main UI page
│
├── database/
│   ├── schema.sql                 ← Main schema (existing)
│   ├── course_player_tables.sql   ← LessonProgress table + trigger
│   └── course_player_seed.sql     ← Sample lessons & enrollments
│
└── docs/
    ├── COURSE_PLAYER_SETUP.md               ← Detailed setup guide
    ├── COURSE_PLAYER_QUICK_START.md         ← Quick start guide
    ├── COURSE_PLAYER_IMPLEMENTATION_SUMMARY.md  ← Implementation summary
    └── COURSE_PLAYER_ARCHITECTURE.md        ← This file
```

---

## 🔐 Security Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Security Layers                           │
└─────────────────────────────────────────────────────────────┘

Layer 1: Authentication
┌─────────────────────────────────────────────────────────────┐
│  • Session-based authentication                              │
│  • Check $_SESSION['user_id'] on every API call             │
│  • Return 401 Unauthorized if not logged in                 │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
Layer 2: Authorization
┌─────────────────────────────────────────────────────────────┐
│  • Verify user is enrolled in course                        │
│  • Check Enrollment table before allowing access            │
│  • Return 403 Forbidden if not enrolled                     │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
Layer 3: Input Validation
┌─────────────────────────────────────────────────────────────┐
│  • Validate all input parameters                            │
│  • Check for required fields                                │
│  • Validate data types                                      │
│  • Return 400 Bad Request if invalid                        │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
Layer 4: SQL Injection Prevention
┌─────────────────────────────────────────────────────────────┐
│  • Use PDO prepared statements                              │
│  • Never concatenate user input in queries                  │
│  • Parameterize all queries                                 │
└─────────────────────────────────────────────────────────────┘
                         │
                         ▼
Layer 5: XSS Prevention
┌─────────────────────────────────────────────────────────────┐
│  • Escape output in frontend                                │
│  • Use textContent instead of innerHTML where possible      │
│  • Sanitize user-generated content                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 Database Relationships

```
┌──────────────┐
│     User     │
│              │
│ • UserId (PK)│
│ • Email      │
│ • Role       │
└──────┬───────┘
       │
       │ 1:N
       │
       ▼
┌──────────────────┐         ┌──────────────┐
│   Enrollment     │   N:1   │    Course    │
│                  │◄────────│              │
│ • UserId (FK)    │         │ • CourseId   │
│ • CourseId (FK)  │         │ • Title      │
│ • Progress%      │         │ • Price      │
│ • Status         │         └──────┬───────┘
└──────┬───────────┘                │
       │                            │ 1:N
       │                            │
       │ 1:N                        ▼
       │                     ┌──────────────┐
       │                     │    Lesson    │
       │                     │              │
       │                     │ • LessonId   │
       │                     │ • CourseId   │
       │                     │ • Title      │
       │                     │ • Content    │
       │                     │ • SortOrder  │
       │                     └──────┬───────┘
       │                            │
       │                            │ 1:N
       │                            │
       ▼                            ▼
┌─────────────────────────────────────────┐
│         LessonProgress (NEW)            │
│                                         │
│ • UserId (FK) ────────────┐            │
│ • LessonId (FK) ──────────┼────────┐   │
│ • CourseId (FK)           │        │   │
│ • IsCompleted             │        │   │
│ • CompletedAt             │        │   │
│ • WatchTimeSeconds        │        │   │
│                           │        │   │
│ UNIQUE(UserId, LessonId)  │        │   │
└───────────────────────────┼────────┼───┘
                            │        │
                            │        │
                    References User  │
                                     │
                            References Lesson
```

---

## ⚡ Performance Considerations

### Database Optimization

```sql
-- Indexes on LessonProgress
INDEX idx_user_course (UserId, CourseId)     -- For progress queries
INDEX idx_completed (IsCompleted)             -- For completion stats
INDEX idx_last_accessed (LastAccessedAt)      -- For recent activity

-- Indexes on Lesson
INDEX idx_course (CourseId)                   -- For course lessons
INDEX idx_sort (SortOrder)                    -- For ordered retrieval

-- Indexes on Enrollment
INDEX idx_user (UserId)                       -- For user enrollments
INDEX idx_course (CourseId)                   -- For course enrollments
INDEX idx_status (CompletionStatus)           -- For status filtering
```

### Caching Strategy

```
Frontend Caching:
• Course content cached after first load
• Lessons cached in memory
• Only reload on completion or refresh

Backend Optimization:
• Use prepared statements (reusable)
• Minimize database queries
• Batch operations where possible

Future Enhancements:
• Redis for session storage
• CDN for video content
• Database query caching
```

---

## 🔄 State Management

```
Application State:
┌─────────────────────────────────────────┐
│  CoursePlayer Instance                  │
│                                         │
│  • currentLesson: Object                │
│  • courseId: Number                     │
│  • lessonId: Number                     │
│  • watchTimeSeconds: Number             │
│  • watchTimeInterval: IntervalID        │
└─────────────────────────────────────────┘

URL State:
┌─────────────────────────────────────────┐
│  ?course_id=1&lesson_id=3               │
│                                         │
│  • Shareable                            │
│  • Bookmarkable                         │
│  • Browser history                      │
└─────────────────────────────────────────┘

Database State:
┌─────────────────────────────────────────┐
│  LessonProgress Table                   │
│                                         │
│  • Persistent                           │
│  • Source of truth                      │
│  • Synchronized via API                 │
└─────────────────────────────────────────┘
```

---

## 🎯 API Response Patterns

### Success Response
```json
{
  "success": true,
  "data": { ... },
  "message": "Optional success message"
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description"
}
```

### HTTP Status Codes
```
200 OK              - Successful request
400 Bad Request     - Invalid parameters
401 Unauthorized    - Not logged in
403 Forbidden       - Not enrolled
404 Not Found       - Resource doesn't exist
405 Method Not Allowed - Wrong HTTP method
500 Internal Error  - Server error
```

---

## 🧩 Component Interactions

```
┌─────────────────────────────────────────────────────────────┐
│                    User Interface                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐ │
│  │ Video Player │    │ Lesson Info  │    │  Curriculum  │ │
│  │              │    │              │    │   Sidebar    │ │
│  │ • Play/Pause │    │ • Title      │    │ • Sections   │ │
│  │ • Progress   │    │ • Tabs       │    │ • Lessons    │ │
│  │ • Controls   │    │ • Complete   │    │ • Progress   │ │
│  └──────┬───────┘    └──────┬───────┘    └──────┬───────┘ │
│         │                   │                   │          │
└─────────┼───────────────────┼───────────────────┼──────────┘
          │                   │                   │
          │                   │                   │
          ▼                   ▼                   ▼
     loadLesson()      markComplete()      loadCourseContent()
          │                   │                   │
          └───────────────────┴───────────────────┘
                              │
                              ▼
                      CoursePlayer Class
                              │
                              ▼
                         API Layer
                              │
                              ▼
                        Model Layer
                              │
                              ▼
                          Database
```

---

## 📱 Responsive Design

```
Desktop (> 1024px):
┌─────────────────────────────────────────────┐
│  Header                                      │
├──────────────────────────┬──────────────────┤
│                          │                  │
│   Video Player           │   Curriculum     │
│   (Large)                │   Sidebar        │
│                          │   (400px)        │
│   Lesson Info            │                  │
│   Tabs                   │   Sections       │
│   Navigation             │   Lessons        │
│                          │   Progress       │
└──────────────────────────┴──────────────────┘

Tablet (768px - 1024px):
┌─────────────────────────────────────────────┐
│  Header                                      │
├──────────────────────────┬──────────────────┤
│                          │                  │
│   Video Player           │   Curriculum     │
│   (Medium)               │   (300px)        │
│                          │                  │
│   Lesson Info            │   Collapsed      │
│   Tabs                   │   Sections       │
└──────────────────────────┴──────────────────┘

Mobile (< 768px):
┌─────────────────────────────────────────────┐
│  Header                                      │
├─────────────────────────────────────────────┤
│                                              │
│   Video Player (Full Width)                 │
│                                              │
├─────────────────────────────────────────────┤
│   Lesson Info                                │
│   Tabs                                       │
├─────────────────────────────────────────────┤
│   Curriculum (Collapsible)                  │
│   Toggle Button                              │
└─────────────────────────────────────────────┘
```

---

## 🚀 Deployment Checklist

- [ ] Database tables created
- [ ] Database trigger verified
- [ ] Sample data seeded (optional)
- [ ] File permissions set correctly
- [ ] PHP error logging enabled
- [ ] Session configuration verified
- [ ] Database connection tested
- [ ] API endpoints accessible
- [ ] CORS configured (if needed)
- [ ] HTTPS enabled (production)
- [ ] Error pages configured
- [ ] Backup strategy in place
- [ ] Monitoring tools set up
- [ ] Performance testing done
- [ ] Security audit completed

---

## 📈 Monitoring & Analytics

### Key Metrics to Track

```
User Engagement:
• Lessons viewed per session
• Average watch time per lesson
• Completion rate per course
• Time to complete course
• Drop-off points

System Performance:
• API response times
• Database query performance
• Page load times
• Error rates
• Concurrent users

Business Metrics:
• Course enrollment rate
• Course completion rate
• User retention
• Most popular courses
• Revenue per course
```

---

This architecture provides a solid foundation for a scalable, maintainable course player system with room for future enhancements and optimizations.
