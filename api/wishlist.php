<?php
/*
 * Task 5:  Wishlist API Endpoint
 * Author:  Abdullah Al Tamh
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../controllers/WishlistController.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$wishlistController = new WishlistController();
$method = $_SERVER['REQUEST_METHOD'];

// GET 
if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'count') {
        if (!isset($_SESSION['user_id'])) {
            json_response(['success' => true, 'count' => 0]);
        }
        json_response(['success' => true, 'count' => $wishlistController->getCount($_SESSION['user_id'])]);

    } elseif ($action === 'items') {
        if (!isset($_SESSION['user_id'])) {
            json_response(['success' => false, 'message' => 'Not authenticated'], 401);
        }
        $result = $wishlistController->getItems($_SESSION['user_id']);
        json_response($result);

    } elseif ($action === 'ids') {
        if (!isset($_SESSION['user_id'])) {
            json_response(['success' => true, 'ids' => []]);
        }
        $ids = $wishlistController->getWishlistIds($_SESSION['user_id']);
        json_response(['success' => true, 'ids' => $ids]);

    } elseif ($action === 'check') {
        if (!isset($_SESSION['user_id'])) {
            json_response(['success' => true, 'inWishlist' => false]);
        }
        $courseId = $_GET['course_id'] ?? null;
        $workshopId = $_GET['workshop_id'] ?? null;
        $result = $wishlistController->check($_SESSION['user_id'], $courseId, $workshopId);
        json_response($result);

    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }

// POST
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        json_response(['success' => false, 'message' => 'Invalid data'], 400);
    }

    $action = $data['action'] ?? '';

    if ($action === 'toggle') {
        if (!isset($_SESSION['user_id'])) {
            json_response([
                'success' => false,
                'message' => 'Please log in to add items to your wishlist',
                'redirect' => '/taleeq/Taliq/pages/login.html'
            ], 401);
        }

        $userId = $_SESSION['user_id'];
        $courseId = $data['course_id'] ?? null;
        $workshopId = $data['workshop_id'] ?? null;

        $result = $wishlistController->toggle($userId, $courseId, $workshopId);
        json_response($result, $result['success'] ? 200 : 400);

    } elseif ($action === 'add') {
        if (!isset($_SESSION['user_id'])) {
            json_response([
                'success' => false,
                'message' => 'Please log in to add items to your wishlist',
                'redirect' => '/taleeq/Taliq/pages/login.html'
            ], 401);
        }

        $userId = $_SESSION['user_id'];
        $courseId = $data['course_id'] ?? null;
        $workshopId = $data['workshop_id'] ?? null;

        $result = $wishlistController->add($userId, $courseId, $workshopId);
        json_response($result, $result['success'] ? 200 : 400);

    } elseif ($action === 'remove') {
        if (!isset($_SESSION['user_id'])) {
            json_response(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $userId = $_SESSION['user_id'];
        $courseId = $data['course_id'] ?? null;
        $workshopId = $data['workshop_id'] ?? null;

        $result = $wishlistController->remove($userId, $courseId, $workshopId);
        json_response($result);

    } elseif ($action === 'clear') {
        if (!isset($_SESSION['user_id'])) {
            json_response(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $result = $wishlistController->clear($_SESSION['user_id']);
        json_response($result);

    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }

} else {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}
