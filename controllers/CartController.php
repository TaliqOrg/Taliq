<?php
/**
 * Cart Controller
 *
 * Manages shopping cart business logic including adding items with server-side
 * price validation, updating quantities, deleting items, emptying the cart,
 * and synchronizing cart state between the database and the session.
 *
 * @package    Taliq\Controllers
 * @subpackage Cart
 * @version    1.0.0
 */

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../models/Cart.php';
require_once '../includes/functions.php';

class CartController {
    
    private $cartModel;

    public function __construct() {
        $this->cartModel = new Cart();
    }

    /**
     * Adds an item to the user's cart.
     *
     * Fetches the authoritative price from the database, performs duplicate
     * checks for workshops, enforces quantity limits (1–10), and syncs
     * the session after persisting.
     *
     * @param int      $userId     The authenticated user's ID.
     * @param int|null $courseId   The course ID to add, or null.
     * @param int|null $workshopId The workshop ID to add, or null.
     * @param float    $price      The price (overridden by DB lookup).
     * @param int      $quantity   The desired quantity (default 1).
     *
     * @return array Associative array with success status, message, and updated count.
     */
    public function addToCart($userId, $courseId, $workshopId, $price, $quantity) {
        if (!$courseId && !$workshopId) {
            return ['success' => false, 'message' => 'No item specified'];
        }

        $price = $this->cartModel->getPriceFromDB($courseId, $workshopId);
        if (!$price) {
            return ['success' => false, 'message' => 'Item not found'];
        }

        if ($workshopId) {
            if ($this->cartModel->isWorkshopAlreadyPurchased($userId, $workshopId)) {
                return ['success' => false, 'message' => 'You are already enrolled in this workshop'];
            }
            if ($this->cartModel->isWorkshopInCart($userId, $workshopId)) {
                return ['success' => false, 'message' => 'This workshop is already in your cart'];
            }
            $quantity = 1;
        }

        if ($quantity < 1) $quantity = 1;
        if ($quantity > 10) $quantity = 10;

        $this->cartModel->addItem($userId, $courseId, $workshopId, $price, $quantity);
        $this->syncToSession($userId);

        return [
            'success' => true,
            'message' => 'Item added to cart!',
            'count'   => $this->getCount()
        ];
    }

    /**
     * Loads the user's cart items from the database into the session.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return void
     */
    public function syncToSession($userId) {
        $items = $this->cartModel->getItems($userId);
        $_SESSION['cart'] = $items;
    }

    /**
     * Empties the user's entire cart and clears the session cart data.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status and empty cart data.
     */
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

    /**
     * Deletes a single item from the cart and syncs the session.
     *
     * @param int $userId     The authenticated user's ID.
     * @param int $cartItemId The cart item ID to remove.
     *
     * @return array The updated cart items response from getCartItems().
     */
    public function deleteItem($userId, $cartItemId) {
        $this->cartModel->deleteItem($cartItemId, $userId);
        $this->syncToSession($userId);
        return $this->getCartItems($userId);
    }

    /**
     * Updates the quantity of a cart item and syncs the session.
     *
     * Enforces quantity limits between 1 and 10.
     *
     * @param int $userId     The authenticated user's ID.
     * @param int $cartItemId The cart item ID to update.
     * @param int $quantity   The new quantity.
     *
     * @return array The updated cart items response from getCartItems().
     */
    public function updateItem($userId, $cartItemId, $quantity) {
        if ($quantity < 1) $quantity = 1;
        if ($quantity > 10) $quantity = 10;

        $this->cartModel->updateQuantity($cartItemId, $quantity, $userId);
        $this->syncToSession($userId);

        return $this->getCartItems($userId);
    }

    /**
     * Retrieves all cart items with calculated subtotals and grand total.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status, items, total, and count.
     */
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

    /**
     * Returns the total number of items in the session cart.
     *
     * @return int The sum of all item quantities.
     */
    public function getCount() {
        if (!isset($_SESSION['cart'])) return 0;
        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['Quantity'];
        }
        return $count;
    }
}
