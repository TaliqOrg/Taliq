<?php
/**
 * Cart Model
 *
 * Handles all shopping cart database operations including cart lifecycle
 * management, item CRUD, duplicate detection for workshops, server-side
 * price validation, and cart item retrieval with course/workshop metadata.
 *
 * @package    Taliq\Models
 * @subpackage Cart
 * @version    1.0.0
 */

class Cart {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Retrieves the user's active cart ID, creating one if none exists.
     *
     * @param int $userId The user's ID.
     *
     * @return int The active cart ID.
     */
    public function getOrCreateCart($userId) {
        $sql = "SELECT CartId FROM Cart WHERE UserId = :user_id AND Status = 'active' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $cart = $stmt->fetch();

        if ($cart) {
            return $cart['CartId'];
        }

        $sql = "INSERT INTO Cart (UserId, Status) VALUES (:user_id, 'active')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $this->db->lastInsertId();
    }

    /**
     * Checks if a user has already purchased and registered for a workshop.
     *
     * @param int $userId     The user's ID.
     * @param int $workshopId The workshop ID.
     *
     * @return bool True if the user is already registered.
     */
    public function isWorkshopAlreadyPurchased($userId, $workshopId) {
        $sql = "SELECT 1 FROM WorkshopRegistration WHERE UserId = :user_id AND WorkshopId = :workshop_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':workshop_id' => $workshopId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Checks if a workshop is already in the user's active cart.
     *
     * @param int $userId     The user's ID.
     * @param int $workshopId The workshop ID.
     *
     * @return bool True if the workshop is already in the cart.
     */
    public function isWorkshopInCart($userId, $workshopId) {
        $cartId = $this->getOrCreateCart($userId);
        $sql = "SELECT 1 FROM CartItem WHERE CartId = :cart_id AND WorkshopId = :workshop_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cart_id' => $cartId, ':workshop_id' => $workshopId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Adds or updates an item in the user's cart.
     *
     * If the item already exists, its quantity is incremented (max 10).
     * Otherwise, a new cart item record is inserted.
     *
     * @param int      $userId     The user's ID.
     * @param int|null $courseId   The course ID, or null.
     * @param int|null $workshopId The workshop ID, or null.
     * @param float    $price      The unit price.
     * @param int      $quantity   The quantity to add.
     *
     * @return bool True on success.
     */
    public function addItem($userId, $courseId, $workshopId, $price, $quantity) {
        $cartId = $this->getOrCreateCart($userId);

        if ($courseId) {
            $sql = "SELECT CartItemId, Quantity FROM CartItem WHERE CartId = :cart_id AND CourseId = :course_id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':cart_id' => $cartId, ':course_id' => $courseId]);
        } else {
            $sql = "SELECT CartItemId, Quantity FROM CartItem WHERE CartId = :cart_id AND WorkshopId = :workshop_id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':cart_id' => $cartId, ':workshop_id' => $workshopId]);
        }

        $existingItem = $stmt->fetch();

        if ($existingItem) {
            $newQty = $existingItem['Quantity'] + $quantity;
            if ($newQty > 10) $newQty = 10;

            $sql = "UPDATE CartItem SET Quantity = :qty WHERE CartItemId = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':qty' => $newQty, ':id' => $existingItem['CartItemId']]);
        } else {
            $sql = "INSERT INTO CartItem (CartId, CourseId, WorkshopId, Quantity, UnitPrice)
                    VALUES (:cart_id, :course_id, :workshop_id, :qty, :price)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cart_id'     => $cartId,
                ':course_id'   => $courseId,
                ':workshop_id' => $workshopId,
                ':qty'         => $quantity,
                ':price'       => $price
            ]);
        }

        return true;
    }

    /**
     * Deletes all items from the user's active cart.
     *
     * @param int $userId The user's ID.
     *
     * @return bool True on success.
     */
    public function emptyCart($userId) {
        $cartId = $this->getOrCreateCart($userId);
        $sql = "DELETE FROM CartItem WHERE CartId = :cart_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':cart_id' => $cartId]);
    }

    /**
     * Deletes a single item from the cart, verified by cart ownership.
     *
     * @param int $cartItemId The cart item ID to delete.
     * @param int $userId     The user's ID.
     *
     * @return bool True on success.
     */
    public function deleteItem($cartItemId, $userId) {
        $cartId = $this->getOrCreateCart($userId);
        $sql = "DELETE FROM CartItem WHERE CartItemId = :id AND CartId = :cart_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $cartItemId, ':cart_id' => $cartId]);
    }

    /**
     * Updates the quantity of a specific cart item, verified by cart ownership.
     *
     * @param int $cartItemId The cart item ID to update.
     * @param int $quantity   The new quantity.
     * @param int $userId     The user's ID.
     *
     * @return bool True on success.
     */
    public function updateQuantity($cartItemId, $quantity, $userId) {
        $cartId = $this->getOrCreateCart($userId);
        $sql = "UPDATE CartItem SET Quantity = :qty WHERE CartItemId = :id AND CartId = :cart_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':qty' => $quantity, ':id' => $cartItemId, ':cart_id' => $cartId]);
    }

    /**
     * Fetches the authoritative price of a course or workshop from the database.
     *
     * @param int|null $courseId   The course ID, or null.
     * @param int|null $workshopId The workshop ID, or null.
     *
     * @return float|null The price, or null if not found.
     */
    public function getPriceFromDB($courseId, $workshopId) {
        if ($courseId) {
            $sql = "SELECT Price FROM Course WHERE CourseId = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $courseId]);
        } else {
            $sql = "SELECT Price FROM Workshop WHERE WorkshopId = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $workshopId]);
        }
        $row = $stmt->fetch();
        return $row ? $row['Price'] : null;
    }

    /**
     * Retrieves all cart items with associated course/workshop metadata.
     *
     * @param int $userId The user's ID.
     *
     * @return array Array of cart items with Title, ThumbnailUrl, and Type.
     */
    public function getItems($userId) {
        $cartId = $this->getOrCreateCart($userId);

        $sql = "SELECT
                    ci.CartItemId,
                    ci.CourseId,
                    ci.WorkshopId,
                    ci.Quantity,
                    ci.UnitPrice,
                    COALESCE(c.Title, w.Title) AS Title,
                    COALESCE(c.ThumbnailUrl, w.ThumbnailUrl) AS ThumbnailUrl,
                    CASE WHEN ci.CourseId IS NOT NULL THEN 'course' ELSE 'workshop' END AS Type
                FROM CartItem ci
                LEFT JOIN Course c ON ci.CourseId = c.CourseId
                LEFT JOIN Workshop w ON ci.WorkshopId = w.WorkshopId
                WHERE ci.CartId = :cart_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cart_id' => $cartId]);
        return $stmt->fetchAll();
    }
}
