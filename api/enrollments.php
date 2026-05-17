<?php
/**
 * Enrollments API Endpoint
 *
 * Manages user enrollment operations for authenticated users. Provides actions
 * to retrieve all enrolled courses with progress data, get detailed progress
 * for a specific course, and check enrollment status.
 *
 * @package    Taliq\Api
 * @subpackage Enrollments
 * @version    1.0.0
 *
 * @method GET Retrieves user enrollments, course progress, and enrollment status.
 */

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
            $enrollments = $enrollmentModel->getUserEnrollments($userId);
            
            if ($enrollments) {
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
            $courseId = $_GET['course_id'] ?? null;
            
            if (!$courseId) {
                http_response_code(400);
                json_response(['success' => false, 'message' => 'Course ID is required']);
                exit;
            }
            
            $isEnrolled = $enrollmentModel->isUserEnrolled($userId, $courseId);
            
            if (!$isEnrolled) {
                http_response_code(403);
                json_response(['success' => false, 'message' => 'Not enrolled in this course']);
                exit;
            }
            
            $enrollment = $enrollmentModel->getEnrollment($userId, $courseId);
            $progress = $lessonModel->getCourseProgress($userId, $courseId);
            
            json_response([
                'success' => true,
                'enrollment' => $enrollment,
                'progress' => $progress
            ]);
            break;

        case 'check_enrollment':
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
