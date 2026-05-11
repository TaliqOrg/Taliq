<?php

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../models/Order.php';
require_once '../controllers/CartController.php';
require_once '../includes/functions.php';

class OrderController {
    private $orderModel;
    private $cartController;

    public function __construct() {
        $this->orderModel     = new Order();
        $this->cartController = new CartController();
    }

    // Complete the purchase: create order in DB, save items, empty the cart
    public function completePurchase($userId) {
        // Get the current cart items
        $cartData = $this->cartController->getCartItems($userId);

        if (!$cartData['success'] || count($cartData['items']) === 0) {
            return [
                'success' => false,
                'message' => 'Your cart is empty'
            ];
        }

        $items = $cartData['items'];
        $total = $cartData['total'];

        // Create the order in DB
        $orderId = $this->orderModel->createOrder($userId, $total);

        // Save each cart item as an order item
        foreach ($items as $item) {
            $this->orderModel->addOrderItem(
                $orderId,
                $item['CourseId'],
                $item['WorkshopId'],
                $item['Quantity'],
                $item['UnitPrice'],
                $item['Subtotal']
            );
        }

        // Empty the cart after successful purchase
        $this->cartController->emptyCart($userId);

        return [
            'success'  => true,
            'message'  => 'Purchase completed successfully!',
            'order_id' => $orderId
        ];
    }
}
