<?php

class Cart {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    // Get the user's active cart from DB, or create one if it doesn't exist
    public function getOrCreateCart($userId) {
        $sql = "SELECT CartId FROM Cart WHERE UserId = :user_id AND Status = 'active' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $cart = $stmt->fetch();

        if ($cart) {
            return $cart['CartId'];
        }

        // No active cart found, create a new one
        $sql = "INSERT INTO Cart (UserId, Status) VALUES (:user_id, 'active')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $this->db->lastInsertId();
    }

    // Add an item to the cart (saves to DB and syncs to session)
    public function addItem($userId, $courseId, $workshopId, $price, $quantity) {
        $cartId = $this->getOrCreateCart($userId);

        // Check if this item is already in the cart
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
            // Already in cart — increase quantity (max 10)
            $newQty = $existingItem['Quantity'] + $quantity;
            if ($newQty > 10) $newQty = 10;

            $sql = "UPDATE CartItem SET Quantity = :qty WHERE CartItemId = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':qty' => $newQty, ':id' => $existingItem['CartItemId']]);
        } else {
            // Not in cart — insert new item
            $sql = "INSERT INTO CartItem (CartId, CourseId, WorkshopId, Quantity, UnitPrice)
                    VALUES (:cart_id, :course_id, :workshop_id, :qty, :price)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cart_id'    => $cartId,
                ':course_id'  => $courseId,
                ':workshop_id'=> $workshopId,
                ':qty'        => $quantity,
                ':price'      => $price
            ]);
        }

        // After saving to DB, sync the session so it stays up to date
        $this->syncToSession($userId);
        return true;
    }

    // Load the cart from DB and save it into the session
    public function syncToSession($userId) {
        $items = $this->getItemsFromDB($userId);
        $_SESSION['cart'] = $items;
    }

    // Get all cart items from the DB (with course/workshop title and thumbnail)
    public function getItemsFromDB($userId) {
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

    // Get the total number of items in the cart (from session — fast)
    public function getCount() {
        if (!isset($_SESSION['cart'])) return 0;
        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['Quantity'];
        }
        return $count;
    }
}
