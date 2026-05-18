<?php
/**
 * User Profile API Endpoint
 *
 * Manages user profile operations for authenticated users. Supports retrieving
 * dashboard summaries, user information, gamification data, enrolled courses,
 * certificates, order history, and individual order details via GET. Allows
 * updating user information via POST.
 *
 * @package    Taliq\Api
 * @subpackage Profile
 * @version    1.0.0
 *
 * @method GET  Retrieves profile data: summary, user-info, gamification, courses,
 *              certificates, orders, and order-details.
 * @method POST Updates user profile information.
 */

require_once '../controllers/ProfileController.php';

header('Content-Type: application/json');

$profileController = new ProfileController();
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Sends a JSON response with the given HTTP status code and terminates execution.
 *
 * @param array $data The response data to encode as JSON.
 * @param int   $code The HTTP status code (default: 200).
 *
 * @return void
 */
function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

/**
 * Validates that the user is authenticated via session.
 * Responds with a 401 error and redirect URL if not authenticated.
 *
 * @return int The authenticated user's ID.
 */
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        json_response([
            'success' => false,
            'message' => 'Please log in to continue',
            'redirect' => '/taleeq/Taliq/pages/login.html'
        ], 401);
    }
    return $_SESSION['user_id'];
}

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    // Dashboard summary
    if ($action === 'summary') {
        $userId = requireAuth();
        json_response($profileController->getDashboardSummary($userId));
    }

    // User info
    if ($action === 'user-info') {
        $userId = requireAuth();
        json_response($profileController->getUserInfo($userId));
    }

    // Gamification data
    if ($action === 'gamification') {
        $userId = requireAuth();
        json_response($profileController->getGamificationData($userId));
    }

    // Enrolled courses
    if ($action === 'courses') {
        $userId = requireAuth();
        json_response($profileController->getEnrolledCourses($userId));
    }

    // Certificates
    if ($action === 'certificates') {
        $userId = requireAuth();
        json_response($profileController->getCertificates($userId));
    }

    // Orders
    if ($action === 'orders') {
        $userId = requireAuth();
        json_response($profileController->getOrders($userId));
    }

    // Single order details
    if ($action === 'order-details') {
        $userId = requireAuth();
        $orderId = $_GET['order_id'] ?? null;
        if (!$orderId) {
            json_response(['success' => false, 'message' => 'Order ID required'], 400);
        }
        json_response($profileController->getOrderDetails($orderId, $userId));
    }

    json_response(['success' => false, 'message' => 'Invalid action'], 400);
}

if ($method === 'POST') {
    $userId = requireAuth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'update-info') {
        json_response($profileController->updateUserInfo($userId, $input));
    }

    json_response(['success' => false, 'message' => 'Invalid action'], 400);
}

json_response(['success' => false, 'message' => 'Method not allowed'], 405);
