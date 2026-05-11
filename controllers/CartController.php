<?php

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../models/Cart.php';
require_once '../includes/functions.php';

class CartController {
    private $cartModel;

    public function __construct() {
        $this->cartModel = new Cart();
    }

    // Add an item to the cart, then sync the session
    public function addToCart($userId, $courseId, $workshopId, $price, $quantity) {
        if (!$courseId && !$workshopId) {
            return ['success' => false, 'message' => 'No item specified'];
        }

        if ($price <= 0) {
            return ['success' => false, 'message' => 'Invalid price'];
        }

        if ($quantity < 1) $quantity = 1;
        if ($quantity > 10) $quantity = 10;

        $this->cartModel->addItem($userId, $courseId, $workshopId, $price, $quantity);

        // After saving to DB, sync to session so it stays up to date
        $this->syncToSession($userId);

        return [
            'success' => true,
            'message' => 'Item added to cart!',
            'count'   => $this->getCount()
        ];
    }

    // Load the user's cart from DB into the session
    public function syncToSession($userId) {
        $items = $this->cartModel->getItems($userId);
        $_SESSION['cart'] = $items;
    }

    // Get total number of items in the cart (reads from session — fast)
    public function getCount() {
        if (!isset($_SESSION['cart'])) return 0;
        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['Quantity'];
        }
        return $count;
    }
}
