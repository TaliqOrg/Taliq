<?php
/**
 * Course Player API v2.0 (Refactored)
 * Simplified API with unified points system
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../models/Lesson.php';
require_once '../models/Enrollment.php';
require_once '../models/Course.php';

$lessonModel = new Lesson();
$enrollmentModel = new Enrollment();
$courseModel = new Course();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$userId = $_SESSION['user_id'];

switch ($action) {
    case 'get_lesson':
        $lessonId = $_GET['lesson_id'] ?? null;
        
        if (!$lessonId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Lesson ID is required']);
            exit;
        }
        
        $lesson = $lessonModel->getLessonById($lessonId);
        
        if (!$lesson) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Lesson not found']);
            exit;
        }
        
        // Check enrollment
        $isEnrolled = $enrollmentModel->isUserEnrolled($userId, $lesson['CourseId']);
        
        if (!$isEnrolled) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You are not enrolled in this course']);
            exit;
        }
        
        // Get progress
        $progress = $lessonModel->getLessonProgress($userId, $lessonId);
        $lesson['IsCompleted'] = $progress ? (bool)$progress['IsCompleted'] : false;
        $lesson['WatchTimeSeconds'] = $progress ? $progress['WatchTimeSeconds'] : 0;
        
        // Get navigation
        $nextLesson = $lessonModel->getNextLesson($lesson['CourseId'], $lesson['SortOrder']);
        $previousLesson = $lessonModel->getPreviousLesson($lesson['CourseId'], $lesson['SortOrder']);
        
        echo json_encode([
            'success' => true,
            'lesson' => $lesson,
            'next_lesson' => $nextLesson,
            'previous_lesson' => $previousLesson
        ]);
        break;

    case 'get_course_content':
        $courseId = $_GET['course_id'] ?? null;
        
        if (!$courseId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Course ID is required']);
            exit;
        }
        
        $isEnrolled = $enrollmentModel->isUserEnrolled($userId, $courseId);
        
        if (!$isEnrolled) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You are not enrolled in this course']);
            exit;
        }
        
        $course = $courseModel->getById($courseId, 'course');
        $sections = $lessonModel->getLessonsWithProgressGrouped($userId, $courseId);
        $progress = $lessonModel->getCourseProgress($userId, $courseId);
        
        // Get user points
        global $pdo;
        $stmt = $pdo->prepare("SELECT Points FROM User WHERE UserId = ?");
        $stmt->execute([$userId]);
        $userPoints = (int)$stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'course' => $course,
            'sections' => $sections,
            'progress' => $progress,
            'user_points' => $userPoints
        ]);
        break;

    case 'mark_complete':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $lessonId = $data['lesson_id'] ?? null;
        $courseId = $data['course_id'] ?? null;
        
        if (!$lessonId || !$courseId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Lesson ID and Course ID are required']);
            exit;
        }
        
        $isEnrolled = $enrollmentModel->isUserEnrolled($userId, $courseId);
        
        if (!$isEnrolled) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You are not enrolled in this course']);
            exit;
        }
        
        // Check if already completed
        $existingProgress = $lessonModel->getLessonProgress($userId, $lessonId);
        $alreadyCompleted = $existingProgress && $existingProgress['IsCompleted'];
        
        $result = $lessonModel->markAsComplete($userId, $lessonId, $courseId);
        
        if ($result) {
            $progress = $lessonModel->getCourseProgress($userId, $courseId);
            
            // Get updated points
            global $pdo;
            $stmt = $pdo->prepare("SELECT Points FROM User WHERE UserId = ?");
            $stmt->execute([$userId]);
            $userPoints = (int)$stmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'message' => 'Lesson marked as complete',
                'progress' => $progress,
                'points_awarded' => $alreadyCompleted ? 0 : 50,
                'user_points' => $userPoints
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to mark lesson as complete']);
        }
        break;

    case 'update_watch_time':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $lessonId = $data['lesson_id'] ?? null;
        $courseId = $data['course_id'] ?? null;
        $watchTime = $data['watch_time'] ?? 0;
        
        if (!$lessonId || !$courseId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Lesson ID and Course ID are required']);
            exit;
        }
        
        $result = $lessonModel->updateWatchTime($userId, $lessonId, $courseId, $watchTime);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Watch time updated' : 'Failed to update watch time'
        ]);
        break;

    case 'get_progress':
        $courseId = $_GET['course_id'] ?? null;
        
        if (!$courseId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Course ID is required']);
            exit;
        }
        
        $progress = $lessonModel->getCourseProgress($userId, $courseId);
        
        // Get user points
        global $pdo;
        $stmt = $pdo->prepare("SELECT Points FROM User WHERE UserId = ?");
        $stmt->execute([$userId]);
        $userPoints = (int)$stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'progress' => $progress,
            'user_points' => $userPoints
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
