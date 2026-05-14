<?php
session_start();

require_once '../config/database.php';
require_once '../models/Enrollment.php';

if (!isset($_SESSION['user_id'])) {
    die("Please log in first. Go to login page and log in with: ahmed@example.com / password123");
}

$userId = $_SESSION['user_id'];
$courseId = $_GET['course_id'] ?? 1;

$enrollmentModel = new Enrollment();

$isEnrolled = $enrollmentModel->isUserEnrolled($userId, $courseId);

if ($isEnrolled) {
    echo "<h2>Already Enrolled!</h2>";
    echo "<p>User ID: {$userId} is already enrolled in Course ID: {$courseId}</p>";
    echo "<p><a href='../pages/user/course_player.html?course_id={$courseId}'>Go to Course Player</a></p>";
} else {
    $result = $enrollmentModel->createEnrollment($userId, $courseId);
    
    if ($result) {
        echo "<h2>Enrollment Successful!</h2>";
        echo "<p>User ID: {$userId} has been enrolled in Course ID: {$courseId}</p>";
        echo "<p><a href='../pages/user/course_player.html?course_id={$courseId}'>Go to Course Player</a></p>";
    } else {
        echo "<h2>Enrollment Failed</h2>";
        echo "<p>Could not enroll user. Check database connection and logs.</p>";
    }
}

echo "<hr>";
echo "<h3>Debug Info:</h3>";
echo "<pre>";
echo "Session User ID: " . $userId . "\n";
echo "Course ID: " . $courseId . "\n";

$enrollment = $enrollmentModel->getEnrollment($userId, $courseId);
if ($enrollment) {
    echo "\nEnrollment Details:\n";
    print_r($enrollment);
}

$enrollments = $enrollmentModel->getUserEnrollments($userId);
echo "\nAll User Enrollments:\n";
print_r($enrollments);
echo "</pre>";
