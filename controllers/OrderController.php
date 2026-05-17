<?php
/**
 * Order Controller
 *
 * Handles the purchase workflow by converting cart items into a finalized order.
 * Creates order records, saves individual order items, enrolls users in purchased
 * courses, registers users for purchased workshops, and empties the cart upon
 * successful completion.
 *
 * @package    Taliq\Controllers
 * @subpackage Orders
 * @version    1.0.0
 */

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

    /**
     * Completes the purchase for a user.
     *
     * Retrieves cart items, creates an order record, saves each item as an
     * order line, creates enrollments/registrations for courses and workshops,
     * empties the cart, and returns the purchased items for client-side use.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status, message, order ID, and purchased items.
     */
    public function completePurchase($userId) {
        $cartData = $this->cartController->getCartItems($userId);

        if (!$cartData['success'] || count($cartData['items']) === 0) {
            return [
                'success' => false,
                'message' => 'Your cart is empty'
            ];
        }

        $items = $cartData['items'];
        $total = $cartData['total'];

        $orderId = $this->orderModel->createOrder($userId, $total);

        foreach ($items as $item) {
            $this->orderModel->addOrderItem(
                $orderId,
                $item['CourseId'],
                $item['WorkshopId'],
                $item['Quantity'],
                $item['UnitPrice'],
                $item['Subtotal']
            );
            
            if (!empty($item['CourseId'])) {
                $this->orderModel->createEnrollment($userId, $item['CourseId']);
            }
            if (!empty($item['WorkshopId'])) {
                $this->orderModel->createWorkshopRegistration($userId, $item['WorkshopId']);
            }
        }

        $this->cartController->emptyCart($userId);

        $purchasedItems = [];
        foreach ($items as $item) {
            if (!empty($item['CourseId'])) {
                $purchasedItems[] = ['courseId' => $item['CourseId'], 'workshopId' => null];
            }
            if (!empty($item['WorkshopId'])) {
                $purchasedItems[] = ['courseId' => null, 'workshopId' => $item['WorkshopId']];
            }
        }

        return [
            'success'  => true,
            'message'  => 'Purchase completed successfully!',
            'order_id' => $orderId,
            'purchased_items' => $purchasedItems
        ];
    }
}
