<?php
/**
 * Orders API Endpoint
 *
 * Processes order-related operations for authenticated users. Currently supports
 * completing a purchase by converting cart items into a finalized order.
 *
 * @package    Taliq\Api
 * @subpackage Orders
 * @version    1.0.0
 *
 * @method POST Completes a purchase for the authenticated user's cart.
 */

require_once '../controllers/OrderController.php';

header('Content-Type: application/json');

$orderController = new OrderController();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        json_response(['success' => false, 'message' => 'Invalid data'], 400);
    }

    $action = $data['action'] ?? '';

    if ($action === 'buy') {
        // User must be logged in
        if (!isset($_SESSION['user_id'])) {
            json_response(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $result = $orderController->completePurchase($_SESSION['user_id']);
        json_response($result, $result['success'] ? 200 : 400);

    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }

} else {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}
