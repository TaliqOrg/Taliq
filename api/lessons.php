<?php
/**
 * Lessons API Endpoint
 *
 * Provides admin-only lesson management operations via POST requests. Supports
 * creating, updating, deleting, and reordering lessons within a course.
 * Requires an active admin session for all actions.
 *
 * @package    Taliq\Api
 * @subpackage Lessons
 * @version    1.0.0
 *
 * @method POST Handles create_lesson, update_lesson, delete_lesson, and reorder_lessons actions.
 */

session_start();

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../models/Lesson.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    json_response(['success' => false, 'message' => 'Unauthorized. Admin access required.'], 401);
}

$lessonModel = new Lesson();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- CREATE LESSON ---
    if ($action === 'create_lesson') {
        $courseId = $_POST['course_id'] ?? null;
        
        if (!$courseId || empty($_POST['Title']) || empty($_POST['ContentUrl'])) {
            json_response(['success' => false, 'message' => 'Please fill in all required fields.'], 400);
        }

        $success = $lessonModel->createLesson($_POST);
        
        if ($success) {
            json_response(['success' => true, 'message' => 'Lesson saved successfully']);
        } else {
            json_response(['success' => false, 'message' => 'Failed to save lesson to database.'], 500);
        }
    } 
    // --- DELETE LESSON ---
    elseif ($action === 'delete_lesson') {
        $lessonId = $_POST['lesson_id'] ?? null;
        
        if (!$lessonId) {
            json_response(['success' => false, 'message' => 'Lesson ID is missing.'], 400);
        }

        $success = $lessonModel->deleteLesson($lessonId);
        
        if ($success) {
            json_response(['success' => true, 'message' => 'Lesson deleted.']);
        } else {
            json_response(['success' => false, 'message' => 'Failed to delete lesson.'], 500);
        }
    }
    // --- UPDATE LESSON ---
    elseif ($action === 'update_lesson') {
        if (empty($_POST['LessonId']) || empty($_POST['Title'])) {
            json_response(['success' => false, 'message' => 'Lesson ID and Title are required.'], 400);
        }

        $success = $lessonModel->updateLesson($_POST);
        
        if ($success) {
            json_response(['success' => true, 'message' => 'Lesson updated successfully']);
        } else {
            json_response(['success' => false, 'message' => 'Failed to update lesson.'], 500);
        }
    }
    // --- REORDER LESSONS ---
    elseif ($action === 'reorder_lessons') {
        $ordersJson = $_POST['orders'] ?? '[]';
        $orders = json_decode($ordersJson, true);

        if (empty($orders)) {
            json_response(['success' => false, 'message' => 'No order data provided.'], 400);
        }

        $success = $lessonModel->updateLessonOrders($orders);
        
        if ($success) {
            json_response(['success' => true, 'message' => 'Lessons reordered successfully']);
        } else {
            json_response(['success' => false, 'message' => 'Failed to reorder lessons.'], 500);
        }
    }
    // --- INVALID ACTION ---
    else {
        json_response(['success' => false, 'message' => 'Unknown action requested.'], 400);
    }
} else {
    json_response(['success' => false, 'message' => 'Invalid Request Method'], 405);
}