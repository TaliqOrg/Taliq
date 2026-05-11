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

        // Always fetch price from DB — never trust what the frontend sends
        $price = $this->cartModel->getPriceFromDB($courseId, $workshopId);
        if (!$price) {
            return ['success' => false, 'message' => 'Item not found'];
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

    // Empty the entire cart, then clear the session
    public function emptyCart($userId) {
        $this->cartModel->emptyCart($userId);
        $_SESSION['cart'] = [];

        return [
            'success' => true,
            'items'   => [],
            'total'   => 0,
            'count'   => 0
        ];
    }

    // Delete one item from the cart, then sync the session
    public function deleteItem($userId, $cartItemId) {
        $this->cartModel->deleteItem($cartItemId, $userId);
        $this->syncToSession($userId);
        return $this->getCartItems($userId);
    }

    // Update the quantity of an item, then sync the session
    public function updateItem($userId, $cartItemId, $quantity) {
        if ($quantity < 1) $quantity = 1;
        if ($quantity > 10) $quantity = 10;

        $this->cartModel->updateQuantity($cartItemId, $quantity, $userId);
        $this->syncToSession($userId);

        return $this->getCartItems($userId);
    }

    // Get all cart items with subtotals and grand total
    public function getCartItems($userId) {
        $items = $this->cartModel->getItems($userId);

        $total = 0;
        foreach ($items as &$item) {
            $item['Subtotal'] = $item['UnitPrice'] * $item['Quantity'];
            $total += $item['Subtotal'];
        }

        return [
            'success' => true,
            'items'   => $items,
            'total'   => $total,
            'count'   => $this->getCount()
        ];
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
