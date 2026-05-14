# Course Player Refactor - Setup Guide

## 🚀 Quick Setup (5 Minutes)

### Step 1: Run Database Migration

```bash
# Navigate to MySQL
mysql -u root -p

# Run the points system schema
source /path/to/taleeq/Taliq/database/points_rewards_system.sql
```

Or using command line:
```bash
mysql -u root -p taleeq_db < database/points_rewards_system.sql
```

### Step 2: Verify Database Setup

```sql
USE taleeq_db;

-- Check tables created
SHOW TABLES LIKE '%Points%';
-- Should show: UserPoints, PointsTransaction, PointsConfig

-- Check triggers created
SHOW TRIGGERS LIKE 'award_points%';
-- Should show: award_points_on_lesson_complete, award_points_on_course_complete

-- Check points configuration
SELECT * FROM PointsConfig;
-- Should show 6 rows with different action types
```

### Step 3: Test the Features

1. **Login** to your account
2. **Navigate** to a course you're enrolled in
3. **Open Course Player** - verify tabs are removed
4. **Mark a lesson complete** - verify:
   - Success notification shows points
   - Button becomes disabled
   - Points display updates
5. **Refresh page** - verify button stays disabled
6. **Check Course Details** - verify curriculum shows only lesson titles

---

## ✅ What Was Changed

### 1. Course Player Simplified
- **Removed:** Resources, Notes, Q&A tabs
- **Kept:** Overview section only
- **File:** `pages/user/course_player.html`

### 2. Completion Button Enhanced
- **Disabled** when lesson already completed
- **Visual feedback** (opacity, cursor)
- **Persistent** across refreshes
- **File:** `js/course_player.js`

### 3. Points System Added
- **Auto-awards** points on lesson completion (50 points)
- **Auto-awards** points on course completion (500 points)
- **Displays** points in real-time
- **Files:** `database/points_rewards_system.sql`, `models/Points.php`, `api/points.php`

### 4. Workshop Handling
- **Courses** → Open Course Player
- **Workshops** → Open Course Details page
- **Files:** `js/user_home.js`, `js/profile.js`

### 5. Course Details Enhanced
- **Shows** lesson titles only (no full content)
- **Grouped** by sections
- **Displays** duration per lesson
- **File:** `js/CourseDetails.js`

---

## 🧪 Testing Checklist

### Course Player
- [ ] Only "About This Lesson" section visible
- [ ] No Resources, Notes, Q&A tabs
- [ ] Lesson description loads dynamically
- [ ] Mark complete button works
- [ ] Button disables after completion
- [ ] Success notification shows points
- [ ] Points display updates

### Points System
- [ ] Points awarded on lesson completion
- [ ] Points awarded on course completion
- [ ] UserPoints table updated
- [ ] PointsTransaction records created
- [ ] Points display in UI

### Navigation
- [ ] Courses open in Course Player
- [ ] Workshops open in Course Details
- [ ] Links work from all pages

### Course Details
- [ ] Curriculum section loads
- [ ] Only lesson titles shown
- [ ] Sections properly grouped
- [ ] Duration displayed correctly

---

## 🔧 Configuration

### Adjust Point Values

```sql
-- Change lesson completion points
UPDATE PointsConfig 
SET PointsAwarded = 100 
WHERE ActionType = 'lesson_completion';

-- Change course completion points
UPDATE PointsConfig 
SET PointsAwarded = 1000 
WHERE ActionType = 'course_completion';
```

### Disable Points for Specific Actions

```sql
UPDATE PointsConfig 
SET IsActive = FALSE 
WHERE ActionType = 'lesson_completion';
```

---

## 📊 Monitoring

### Check User Points
```sql
SELECT u.Email, up.TotalPoints, up.LifetimePoints
FROM User u
LEFT JOIN UserPoints up ON u.UserId = up.UserId
ORDER BY up.TotalPoints DESC
LIMIT 10;
```

### Check Recent Transactions
```sql
SELECT 
    u.Email,
    pt.Points,
    pt.Source,
    pt.Description,
    pt.CreatedAt
FROM PointsTransaction pt
INNER JOIN User u ON pt.UserId = u.UserId
ORDER BY pt.CreatedAt DESC
LIMIT 20;
```

---

## 🐛 Troubleshooting

### Issue: Points not awarded
**Solution:**
1. Check triggers exist: `SHOW TRIGGERS LIKE 'award_points%';`
2. Check PointsConfig is active: `SELECT * FROM PointsConfig WHERE IsActive = TRUE;`
3. Check error logs

### Issue: Button not disabling
**Solution:**
1. Check `IsCompleted` field in LessonProgress table
2. Verify JavaScript console for errors
3. Clear browser cache

### Issue: Curriculum not loading
**Solution:**
1. Check API endpoint: `/api/courses.php?action=curriculum&course_id=1`
2. Verify Lesson model has `getLessonsBySectionGrouped()` method
3. Check browser console for errors

---

## 📁 File Structure

```
Taliq/
├── database/
│   ├── points_rewards_system.sql      # NEW - Points system
│   ├── course_player_tables.sql       # Existing
│   └── course_player_seed.sql         # Existing
│
├── models/
│   ├── Points.php                     # NEW - Points model
│   ├── Lesson.php                     # Existing
│   └── Enrollment.php                 # Existing
│
├── api/
│   ├── points.php                     # NEW - Points API
│   ├── course_player.php              # MODIFIED - Points integration
│   └── courses.php                    # MODIFIED - Curriculum endpoint
│
├── js/
│   ├── course_player.js               # MODIFIED - Points, completion
│   ├── user_home.js                   # MODIFIED - Workshop handling
│   ├── profile.js                     # MODIFIED - Workshop handling
│   └── CourseDetails.js               # MODIFIED - Curriculum display
│
└── pages/user/
    └── course_player.html             # MODIFIED - Simplified UI
```

---

## 🎯 Key Features

### Points System
- ✅ Automatic points on lesson completion
- ✅ Automatic points on course completion
- ✅ Manual points on purchase
- ✅ Transaction history
- ✅ Configurable values
- ✅ Real-time UI updates

### Course Player
- ✅ Simplified UI (Overview only)
- ✅ Disabled button for completed lessons
- ✅ Points notifications
- ✅ Persistent state

### Course Details
- ✅ Lesson titles only
- ✅ Grouped by sections
- ✅ Duration display

### Navigation
- ✅ Courses → Player
- ✅ Workshops → Details

---

## 📞 Support

If you encounter issues:
1. Check database migrations ran successfully
2. Verify all files uploaded correctly
3. Check browser console for JavaScript errors
4. Check PHP error logs for backend issues
5. Review the detailed summary: `COURSE_PLAYER_REFACTOR_SUMMARY.md`

---

## ✅ Deployment Checklist

- [ ] Database migration completed
- [ ] Tables created (UserPoints, PointsTransaction, PointsConfig)
- [ ] Triggers created (award_points_on_lesson_complete, award_points_on_course_complete)
- [ ] Points.php model uploaded
- [ ] points.php API uploaded
- [ ] All modified files uploaded
- [ ] Tested lesson completion
- [ ] Tested points awarding
- [ ] Tested button disable logic
- [ ] Tested workshop navigation
- [ ] Tested curriculum display
- [ ] Verified no regressions

---

## 🎉 You're Done!

All features are now implemented and ready to use. The system will automatically:
- Award points when lessons are completed
- Award points when courses are completed
- Disable completion buttons for completed lessons
- Show only lesson titles in course details
- Route workshops to the correct page

Enjoy your enhanced Course Player! 🚀
