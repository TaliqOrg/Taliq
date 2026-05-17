<?php
/**
 * Shopping Cart API Endpoint
 *
 * Manages shopping cart operations for authenticated users. Supports retrieving
 * cart item count and details via GET, and adding, updating, deleting, or
 * emptying cart items via POST with JSON payloads.
 *
 * @package    Taliq\Api
 * @subpackage Cart
 * @version    1.0.0
 *
 * @method GET  Retrieves cart count or full cart items list.
 * @method POST Adds, updates, deletes, or empties cart items.
 */

require_once '../controllers/CartController.php';

header('Content-Type: application/json');

$cartController = new CartController();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'count') {
        json_response(['success' => true, 'count' => $cartController->getCount()]);

    } elseif ($action === 'items') {
        if (!isset($_SESSION['user_id'])) {
            json_response(['success' => false, 'message' => 'Not authenticated'], 401);
        }
        $result = $cartController->getCartItems($_SESSION['user_id']);
        json_response($result);

    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        json_response(['success' => false, 'message' => 'Invalid data'], 400);
    }

    $action = $data['action'] ?? '';

    if ($action === 'add') {
        if (!isset($_SESSION['user_id'])) {
            json_response([
                'success'  => false,
                'message'  => 'Please log in to add items to your cart',
                'redirect' => '/taleeq/Taliq/pages/login.html'
            ], 401);
        }

        $userId     = $_SESSION['user_id'];
        $courseId   = $data['course_id']   ?? null;
        $workshopId = $data['workshop_id'] ?? null;
        $price      = $data['price']       ?? 0;
        $quantity   = (int)($data['quantity'] ?? 1);

        $result = $cartController->addToCart($userId, $courseId, $workshopId, $price, $quantity);
        json_response($result, $result['success'] ? 200 : 400);

    } elseif ($action === 'empty') {
        // Empty the entire cart
        if (!isset($_SESSION['user_id'])) {
            json_response(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $result = $cartController->emptyCart($_SESSION['user_id']);
        json_response($result);

    } elseif ($action === 'delete') {
        // Delete one item from the cart
        if (!isset($_SESSION['user_id'])) {
            json_response(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $userId     = $_SESSION['user_id'];
        $cartItemId = $data['cart_item_id'] ?? null;

        if (!$cartItemId) {
            json_response(['success' => false, 'message' => 'No item specified'], 400);
        }

        $result = $cartController->deleteItem($userId, $cartItemId);
        json_response($result);

    } elseif ($action === 'update') {
        // Update quantity of a cart item
        if (!isset($_SESSION['user_id'])) {
            json_response(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $userId     = $_SESSION['user_id'];
        $cartItemId = $data['cart_item_id'] ?? null;
        $quantity   = (int)($data['quantity'] ?? 1);

        if (!$cartItemId) {
            json_response(['success' => false, 'message' => 'No item specified'], 400);
        }

        $result = $cartController->updateItem($userId, $cartItemId, $quantity);
        json_response($result);

    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }

} else {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}
