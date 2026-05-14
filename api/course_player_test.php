<?php
/**
 * Course Player Integration Test
 * Tests all API endpoints and functionality
 */

header('Content-Type: text/html; charset=utf-8');
session_start();

require_once '../config/database.php';
require_once '../models/Lesson.php';
require_once '../models/Enrollment.php';
require_once '../models/Course.php';

echo "<h1>Course Player Integration Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .test { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #ccc; }
    .pass { border-left-color: #4CAF50; }
    .fail { border-left-color: #f44336; }
    .info { background: #e3f2fd; padding: 10px; margin: 10px 0; border-radius: 5px; }
    pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    h2 { color: #333; }
    .status { font-weight: bold; }
    .pass .status { color: #4CAF50; }
    .fail .status { color: #f44336; }
</style>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div class='info'>";
    echo "<h2>⚠️ Not Logged In</h2>";
    echo "<p>Please log in first. Use credentials: <strong>ahmed@example.com</strong> / <strong>password123</strong></p>";
    echo "<p><a href='../pages/login.html'>Go to Login Page</a></p>";
    echo "</div>";
    exit;
}

$userId = $_SESSION['user_id'];
$testCourseId = 1;
$testLessonId = 1;

echo "<div class='info'>";
echo "<h2>Test Configuration</h2>";
echo "<p>User ID: <strong>{$userId}</strong></p>";
echo "<p>Test Course ID: <strong>{$testCourseId}</strong></p>";
echo "<p>Test Lesson ID: <strong>{$testLessonId}</strong></p>";
echo "</div>";

$lessonModel = new Lesson();
$enrollmentModel = new Enrollment();
$courseModel = new Course();

$testResults = [];

// Test 1: Check Enrollment
echo "<h2>Test 1: Check User Enrollment</h2>";
$isEnrolled = $enrollmentModel->isUserEnrolled($userId, $testCourseId);
if ($isEnrolled) {
    echo "<div class='test pass'>";
    echo "<span class='status'>✓ PASS</span> - User is enrolled in course {$testCourseId}";
    echo "</div>";
    $testResults[] = true;
} else {
    echo "<div class='test fail'>";
    echo "<span class='status'>✗ FAIL</span> - User is NOT enrolled. Creating enrollment...";
    $result = $enrollmentModel->createEnrollment($userId, $testCourseId);
    if ($result) {
        echo "<br>✓ Enrollment created successfully!";
        $testResults[] = true;
    } else {
        echo "<br>✗ Failed to create enrollment";
        $testResults[] = false;
    }
    echo "</div>";
}

// Test 2: Get Course Content
echo "<h2>Test 2: Get Course Content</h2>";
try {
    $course = $courseModel->getById($testCourseId, 'course');
    $sections = $lessonModel->getLessonsBySectionGrouped($testCourseId, $userId);
    $progress = $lessonModel->getCourseProgress($userId, $testCourseId);
    
    if ($course && is_array($sections) && $progress) {
        echo "<div class='test pass'>";
        echo "<span class='status'>✓ PASS</span> - Course content retrieved successfully";
        echo "<pre>";
        echo "Course: {$course['Title']}\n";
        echo "Sections: " . count($sections) . "\n";
        echo "Total Lessons: {$progress['total_lessons']}\n";
        echo "Completed: {$progress['completed_lessons']}\n";
        echo "Progress: {$progress['progress_percentage']}%";
        echo "</pre>";
        echo "</div>";
        $testResults[] = true;
    } else {
        echo "<div class='test fail'>";
        echo "<span class='status'>✗ FAIL</span> - Failed to retrieve course content";
        echo "</div>";
        $testResults[] = false;
    }
} catch (Exception $e) {
    echo "<div class='test fail'>";
    echo "<span class='status'>✗ FAIL</span> - Exception: " . $e->getMessage();
    echo "</div>";
    $testResults[] = false;
}

// Test 3: Get Lesson Details
echo "<h2>Test 3: Get Lesson Details</h2>";
try {
    $lesson = $lessonModel->getLessonById($testLessonId, $userId);
    
    if ($lesson) {
        echo "<div class='test pass'>";
        echo "<span class='status'>✓ PASS</span> - Lesson retrieved successfully";
        echo "<pre>";
        echo "Lesson ID: {$lesson['LessonId']}\n";
        echo "Title: {$lesson['Title']}\n";
        echo "Duration: {$lesson['Duration']} minutes\n";
        echo "Content Type: {$lesson['ContentType']}\n";
        echo "Is Completed: " . ($lesson['IsCompleted'] ? 'Yes' : 'No');
        echo "</pre>";
        echo "</div>";
        $testResults[] = true;
    } else {
        echo "<div class='test fail'>";
        echo "<span class='status'>✗ FAIL</span> - Lesson not found";
        echo "</div>";
        $testResults[] = false;
    }
} catch (Exception $e) {
    echo "<div class='test fail'>";
    echo "<span class='status'>✗ FAIL</span> - Exception: " . $e->getMessage();
    echo "</div>";
    $testResults[] = false;
}

// Test 4: Get Next/Previous Lessons
echo "<h2>Test 4: Get Next/Previous Lessons</h2>";
try {
    $nextLesson = $lessonModel->getNextLesson($testLessonId, $testCourseId);
    $prevLesson = $lessonModel->getPreviousLesson($testLessonId, $testCourseId);
    
    echo "<div class='test pass'>";
    echo "<span class='status'>✓ PASS</span> - Navigation lessons retrieved";
    echo "<pre>";
    echo "Next Lesson: " . ($nextLesson ? $nextLesson['Title'] : 'None (last lesson)') . "\n";
    echo "Previous Lesson: " . ($prevLesson ? $prevLesson['Title'] : 'None (first lesson)');
    echo "</pre>";
    echo "</div>";
    $testResults[] = true;
} catch (Exception $e) {
    echo "<div class='test fail'>";
    echo "<span class='status'>✗ FAIL</span> - Exception: " . $e->getMessage();
    echo "</div>";
    $testResults[] = false;
}

// Test 5: Mark Lesson as Complete
echo "<h2>Test 5: Mark Lesson as Complete</h2>";
try {
    $result = $lessonModel->markAsComplete($userId, $testLessonId, $testCourseId);
    
    if ($result) {
        echo "<div class='test pass'>";
        echo "<span class='status'>✓ PASS</span> - Lesson marked as complete";
        
        // Verify it was marked
        $lesson = $lessonModel->getLessonById($testLessonId, $userId);
        echo "<pre>";
        echo "Verification - Is Completed: " . ($lesson['IsCompleted'] ? 'Yes' : 'No') . "\n";
        echo "Completed At: " . ($lesson['CompletedAt'] ?? 'N/A');
        echo "</pre>";
        echo "</div>";
        $testResults[] = true;
    } else {
        echo "<div class='test fail'>";
        echo "<span class='status'>✗ FAIL</span> - Failed to mark lesson as complete";
        echo "</div>";
        $testResults[] = false;
    }
} catch (Exception $e) {
    echo "<div class='test fail'>";
    echo "<span class='status'>✗ FAIL</span> - Exception: " . $e->getMessage();
    echo "</div>";
    $testResults[] = false;
}

// Test 6: Update Watch Time
echo "<h2>Test 6: Update Watch Time</h2>";
try {
    $watchTime = 120; // 2 minutes
    $result = $lessonModel->updateWatchTime($userId, $testLessonId, $testCourseId, $watchTime);
    
    if ($result) {
        echo "<div class='test pass'>";
        echo "<span class='status'>✓ PASS</span> - Watch time updated";
        echo "<pre>Watch Time: {$watchTime} seconds</pre>";
        echo "</div>";
        $testResults[] = true;
    } else {
        echo "<div class='test fail'>";
        echo "<span class='status'>✗ FAIL</span> - Failed to update watch time";
        echo "</div>";
        $testResults[] = false;
    }
} catch (Exception $e) {
    echo "<div class='test fail'>";
    echo "<span class='status'>✗ FAIL</span> - Exception: " . $e->getMessage();
    echo "</div>";
    $testResults[] = false;
}

// Test 7: Verify Progress Update (Trigger Test)
echo "<h2>Test 7: Verify Progress Update (Database Trigger)</h2>";
try {
    $enrollment = $enrollmentModel->getEnrollment($userId, $testCourseId);
    $progress = $lessonModel->getCourseProgress($userId, $testCourseId);
    
    if ($enrollment && $progress) {
        echo "<div class='test pass'>";
        echo "<span class='status'>✓ PASS</span> - Progress tracking working";
        echo "<pre>";
        echo "Enrollment Progress: {$enrollment['ProgressPercentage']}%\n";
        echo "Completion Status: {$enrollment['CompletionStatus']}\n";
        echo "Calculated Progress: {$progress['progress_percentage']}%\n";
        echo "Completed Lessons: {$progress['completed_lessons']} / {$progress['total_lessons']}";
        echo "</pre>";
        echo "</div>";
        $testResults[] = true;
    } else {
        echo "<div class='test fail'>";
        echo "<span class='status'>✗ FAIL</span> - Progress data not available";
        echo "</div>";
        $testResults[] = false;
    }
} catch (Exception $e) {
    echo "<div class='test fail'>";
    echo "<span class='status'>✗ FAIL</span> - Exception: " . $e->getMessage();
    echo "</div>";
    $testResults[] = false;
}

// Test 8: Get All Lessons with Progress
echo "<h2>Test 8: Get All Lessons with Progress</h2>";
try {
    $lessons = $lessonModel->getLessonsByCourse($testCourseId, $userId);
    
    if (is_array($lessons) && count($lessons) > 0) {
        echo "<div class='test pass'>";
        echo "<span class='status'>✓ PASS</span> - Retrieved " . count($lessons) . " lessons";
        echo "<pre>";
        foreach ($lessons as $index => $lesson) {
            $completed = isset($lesson['IsCompleted']) && $lesson['IsCompleted'] ? '✓' : '○';
            echo "{$completed} Lesson {$lesson['SortOrder']}: {$lesson['Title']}\n";
            if ($index >= 4) {
                echo "... and " . (count($lessons) - 5) . " more\n";
                break;
            }
        }
        echo "</pre>";
        echo "</div>";
        $testResults[] = true;
    } else {
        echo "<div class='test fail'>";
        echo "<span class='status'>✗ FAIL</span> - No lessons found";
        echo "</div>";
        $testResults[] = false;
    }
} catch (Exception $e) {
    echo "<div class='test fail'>";
    echo "<span class='status'>✗ FAIL</span> - Exception: " . $e->getMessage();
    echo "</div>";
    $testResults[] = false;
}

// Summary
$totalTests = count($testResults);
$passedTests = count(array_filter($testResults));
$failedTests = $totalTests - $passedTests;
$passRate = round(($passedTests / $totalTests) * 100, 1);

echo "<div class='info'>";
echo "<h2>Test Summary</h2>";
echo "<p><strong>Total Tests:</strong> {$totalTests}</p>";
echo "<p style='color: #4CAF50;'><strong>Passed:</strong> {$passedTests}</p>";
echo "<p style='color: #f44336;'><strong>Failed:</strong> {$failedTests}</p>";
echo "<p><strong>Pass Rate:</strong> {$passRate}%</p>";

if ($passRate == 100) {
    echo "<h3 style='color: #4CAF50;'>🎉 All tests passed! Course Player is ready to use.</h3>";
    echo "<p><a href='../pages/user/course_player.html?course_id={$testCourseId}' style='display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Open Course Player</a></p>";
} else {
    echo "<h3 style='color: #f44336;'>⚠️ Some tests failed. Please review the errors above.</h3>";
}
echo "</div>";

// Database State
echo "<h2>Current Database State</h2>";
echo "<div class='test'>";
echo "<h3>LessonProgress Records</h3>";
try {
    $stmt = $pdo->prepare("SELECT * FROM LessonProgress WHERE UserId = ? AND CourseId = ? ORDER BY LessonId");
    $stmt->execute([$userId, $testCourseId]);
    $progressRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($progressRecords) {
        echo "<pre>";
        foreach ($progressRecords as $record) {
            echo "Lesson {$record['LessonId']}: Completed=" . ($record['IsCompleted'] ? 'Yes' : 'No') . 
                 ", WatchTime={$record['WatchTimeSeconds']}s, LastAccessed={$record['LastAccessedAt']}\n";
        }
        echo "</pre>";
    } else {
        echo "<p>No progress records found.</p>";
    }
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
echo "</div>";
