<?php

require_once '../controllers/CartController.php';

header('Content-Type: application/json');

$cartController = new CartController();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'count') {
        // Return how many items are in the cart (used for the header badge)
        json_response(['success' => true, 'count' => $cartController->getCount()]);

    } elseif ($action === 'items') {
        // Return all cart items with subtotals and total
        if (!isset($_SESSION['user_id'])) {
            json_response(['success' => false, 'message' => 'Not authenticated'], 401);
        }
        $result = $cartController->getCartItems($_SESSION['user_id']);
        json_response($result);

    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }

// ── POST ─────────────────────────────────────────────────────────────────────
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        json_response(['success' => false, 'message' => 'Invalid data'], 400);
    }

    $action = $data['action'] ?? '';

    if ($action === 'add') {
        // User must be logged in
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
