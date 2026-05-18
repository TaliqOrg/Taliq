<?php
/*
 * Task 7:  Buy (Order Processing)
 * Author:  Abdullah Al Tamh
 */

class Order {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    // Create a new order and return the new OrderId
    public function createOrder($userId, $totalAmount) {
        $sql = "INSERT INTO `Order` (UserId, TotalAmount, Status)
                VALUES (:user_id, :total, 'completed')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':total'   => $totalAmount
        ]);
        return $this->db->lastInsertId();
    }

    // Add one item to an order
    public function addOrderItem($orderId, $courseId, $workshopId, $quantity, $unitPrice, $subtotal) {
        $sql = "INSERT INTO OrderItem (OrderId, CourseId, WorkshopId, Quantity, UnitPrice, Subtotal)
                VALUES (:order_id, :course_id, :workshop_id, :qty, :unit_price, :subtotal)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':order_id'   => $orderId,
            ':course_id'  => $courseId,
            ':workshop_id'=> $workshopId,
            ':qty'        => $quantity,
            ':unit_price' => $unitPrice,
            ':subtotal'   => $subtotal
        ]);
    }

    // Create enrollment for a course
    public function createEnrollment($userId, $courseId) {
        // Check if already enrolled
        $sql = "SELECT EnrollmentId FROM Enrollment WHERE UserId = :user_id AND CourseId = :course_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':course_id' => $courseId]);
        
        if ($stmt->fetch()) {
            return false; // Already enrolled
        }
        
        $sql = "INSERT INTO Enrollment (UserId, CourseId, CompletionStatus)
                VALUES (:user_id, :course_id, 'not_started')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':course_id' => $courseId]);
        return true;
    }

    // Create workshop registration
    public function createWorkshopRegistration($userId, $workshopId) {
        // Check if already registered
        $sql = "SELECT WorkshopRegistrationId FROM WorkshopRegistration WHERE UserId = :user_id AND WorkshopId = :workshop_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':workshop_id' => $workshopId]);

        if ($stmt->fetch()) {
            return false; // Already registered
        }

        $sql = "INSERT INTO WorkshopRegistration (UserId, WorkshopId, AttendanceStatus)
                VALUES (:user_id, :workshop_id, 'registered')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':workshop_id' => $workshopId]);

        // Decrement available seats — GREATEST keeps it from going below 0
        $this->db->prepare(
            "UPDATE WorkshopSession
             SET AvailableSeats = GREATEST(AvailableSeats - 1, 0)
             WHERE WorkshopId = :workshop_id AND AvailableSeats > 0"
        )->execute([':workshop_id' => $workshopId]);

        return true;
    }

    // Get all orders for a user (for past purchases)
    public function getOrdersByUser($userId) {
        $sql = "SELECT o.OrderId, o.TotalAmount, o.Status, o.OrderDate,
                       oi.CourseId, oi.WorkshopId, oi.Quantity, oi.UnitPrice, oi.Subtotal,
                       COALESCE(c.Title, w.Title) AS Title,
                       COALESCE(c.ThumbnailUrl, w.ThumbnailUrl) AS ThumbnailUrl
                FROM `Order` o
                JOIN OrderItem oi ON o.OrderId = oi.OrderId
                LEFT JOIN Course c ON oi.CourseId = c.CourseId
                LEFT JOIN Workshop w ON oi.WorkshopId = w.WorkshopId
                WHERE o.UserId = :user_id
                ORDER BY o.OrderDate DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
