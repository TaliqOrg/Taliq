<?php

require_once '../controllers/CourseController.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$courseController = new CourseController();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        // Get all courses (with optional limit)
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
        $result = $courseController->getAll($limit);
        json_response($result);

    } elseif ($action === 'details') {
        // Get a specific course by ID and type
        $courseId = $_GET['id'] ?? null;
        $courseType = $_GET['type'] ?? null;

        $result = $courseController->getById($courseId, $courseType);
        json_response($result, $result['success'] ? 200 : 404);

    } elseif ($action === 'lessons') {
        // Get a course with its lessons
        $courseId = $_GET['id'] ?? null;
        $courseType = $_GET['type'] ?? null;

        $result = $courseController->getWithLessons($courseId, $courseType);
        json_response($result, $result['success'] ? 200 : 404);

    } elseif ($action === 'by_ids') {
        // Get courses by IDs (for recent purchases)
        $idsParam = $_GET['ids'] ?? '';
        if (empty($idsParam)) {
            json_response(['success' => false, 'message' => 'No IDs provided'], 400);
        }
        
        // Parse the IDs - format: "c1,c2,w3,w4" (c=course, w=workshop)
        $items = [];
        $ids = explode(',', $idsParam);
        foreach ($ids as $id) {
            $id = trim($id);
            if (strpos($id, 'c') === 0) {
                $items[] = ['courseId' => substr($id, 1), 'workshopId' => null];
            } elseif (strpos($id, 'w') === 0) {
                $items[] = ['courseId' => null, 'workshopId' => substr($id, 1)];
            }
        }
        
        $result = $courseController->getByIds($items);
        json_response($result);

    } elseif ($action === 'curriculum') {
        // Get course curriculum (lessons grouped by sections)
        $courseId = $_GET['course_id'] ?? null;
        
        if (!$courseId) {
            json_response(['success' => false, 'message' => 'Course ID is required'], 400);
        }
        
        require_once '../models/Lesson.php';
        $lessonModel = new Lesson();
        
        $sections = $lessonModel->getLessonsBySectionGrouped($courseId);
        
        if ($sections) {
            json_response([
                'success' => true,
                'sections' => $sections
            ]);
        } else {
            json_response(['success' => false, 'message' => 'No curriculum found'], 404);
        }

    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }

} elseif ($method === 'POST') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        json_response(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = $courseController->addCourse($_POST, $_FILES);
        json_response($result, $result['success'] ? 201 : 400);
    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }

} else {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}
