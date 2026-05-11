<?php

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../models/Cart.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$cart = new Cart();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'count') {
        // Return how many items are in the cart (used for the header badge)
        json_response(['success' => true, 'count' => $cart->getCount()]);

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

        $userId    = $_SESSION['user_id'];
        $courseId  = $data['course_id']   ?? null;
        $workshopId= $data['workshop_id'] ?? null;
        $price     = $data['price']       ?? 0;
        $quantity  = (int)($data['quantity'] ?? 1);

        // Must have either a course or a workshop
        if (!$courseId && !$workshopId) {
            json_response(['success' => false, 'message' => 'No item specified'], 400);
        }

        if ($price <= 0) {
            json_response(['success' => false, 'message' => 'Invalid price'], 400);
        }

        if ($quantity < 1) $quantity = 1;
        if ($quantity > 10) $quantity = 10;

        $cart->addItem($userId, $courseId, $workshopId, $price, $quantity);

        json_response([
            'success' => true,
            'message' => 'Item added to cart!',
            'count'   => $cart->getCount()
        ]);

    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }

} else {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}
