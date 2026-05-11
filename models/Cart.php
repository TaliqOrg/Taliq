<?php

class Cart {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    // Get the user's active cart ID from DB, or create one if none exists
    public function getOrCreateCart($userId) {
        $sql = "SELECT CartId FROM Cart WHERE UserId = :user_id AND Status = 'active' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $cart = $stmt->fetch();

        if ($cart) {
            return $cart['CartId'];
        }

        // No active cart — create one
        $sql = "INSERT INTO Cart (UserId, Status) VALUES (:user_id, 'active')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $this->db->lastInsertId();
    }

    // Add or update an item in the DB
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
            // Item already in cart — increase quantity (max 10)
            $newQty = $existingItem['Quantity'] + $quantity;
            if ($newQty > 10) $newQty = 10;

            $sql = "UPDATE CartItem SET Quantity = :qty WHERE CartItemId = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':qty' => $newQty, ':id' => $existingItem['CartItemId']]);
        } else {
            // New item — insert it
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

    // Delete ALL items from the user's cart in DB
    public function emptyCart($userId) {
        $cartId = $this->getOrCreateCart($userId);
        $sql = "DELETE FROM CartItem WHERE CartId = :cart_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':cart_id' => $cartId]);
    }

    // Delete one item from the cart in DB — only if it belongs to this user's cart
    public function deleteItem($cartItemId, $userId) {
        $cartId = $this->getOrCreateCart($userId);
        $sql = "DELETE FROM CartItem WHERE CartItemId = :id AND CartId = :cart_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $cartItemId, ':cart_id' => $cartId]);
    }

    // Update the quantity of a specific cart item in DB — only if it belongs to this user's cart
    public function updateQuantity($cartItemId, $quantity, $userId) {
        $cartId = $this->getOrCreateCart($userId);
        $sql = "UPDATE CartItem SET Quantity = :qty WHERE CartItemId = :id AND CartId = :cart_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':qty' => $quantity, ':id' => $cartItemId, ':cart_id' => $cartId]);
    }

    // Fetch the real price of an item from the DB — never trust the frontend
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

    // Get all cart items from DB with course/workshop info
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
