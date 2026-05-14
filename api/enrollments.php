<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../models/Enrollment.php';
require_once '../models/Lesson.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$enrollmentModel = new Enrollment();
$lessonModel = new Lesson();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    json_response(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$userId = $_SESSION['user_id'];

if ($method === 'GET') {
    switch ($action) {
        case 'my_courses':
            // Get all user enrollments with progress
            $enrollments = $enrollmentModel->getUserEnrollments($userId);
            
            if ($enrollments) {
                // Enrich with progress data
                foreach ($enrollments as &$enrollment) {
                    $courseId = $enrollment['CourseId'];
                    $progress = $lessonModel->getCourseProgress($userId, $courseId);
                    
                    if ($progress) {
                        $enrollment['ProgressPercentage'] = $progress['progress_percentage'];
                        $enrollment['CompletedLessons'] = $progress['completed_lessons'];
                        $enrollment['TotalLessons'] = $progress['total_lessons'];
                    }
                }
                
                json_response([
                    'success' => true,
                    'enrollments' => $enrollments
                ]);
            } else {
                json_response([
                    'success' => true,
                    'enrollments' => []
                ]);
            }
            break;

        case 'course_progress':
            // Get progress for a specific course
            $courseId = $_GET['course_id'] ?? null;
            
            if (!$courseId) {
                http_response_code(400);
                json_response(['success' => false, 'message' => 'Course ID is required']);
                exit;
            }
            
            // Check enrollment
            $isEnrolled = $enrollmentModel->isUserEnrolled($userId, $courseId);
            
            if (!$isEnrolled) {
                http_response_code(403);
                json_response(['success' => false, 'message' => 'Not enrolled in this course']);
                exit;
            }
            
            // Get enrollment details
            $enrollment = $enrollmentModel->getEnrollment($userId, $courseId);
            
            // Get detailed progress
            $progress = $lessonModel->getCourseProgress($userId, $courseId);
            
            json_response([
                'success' => true,
                'enrollment' => $enrollment,
                'progress' => $progress
            ]);
            break;

        case 'check_enrollment':
            // Check if user is enrolled in a course
            $courseId = $_GET['course_id'] ?? null;
            
            if (!$courseId) {
                http_response_code(400);
                json_response(['success' => false, 'message' => 'Course ID is required']);
                exit;
            }
            
            $isEnrolled = $enrollmentModel->isUserEnrolled($userId, $courseId);
            
            json_response([
                'success' => true,
                'is_enrolled' => $isEnrolled
            ]);
            break;

        default:
            http_response_code(400);
            json_response(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} else {
    http_response_code(405);
    json_response(['success' => false, 'message' => 'Method not allowed']);
}
