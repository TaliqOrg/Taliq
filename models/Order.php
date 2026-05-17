<?php
/**
 * Order Model
 *
 * Handles order persistence and related enrollment/registration creation.
 * Supports creating orders, adding order items, enrolling users in courses,
 * registering users for workshops with seat management, and retrieving
 * order history with item details.
 *
 * @package    Taliq\Models
 * @subpackage Order
 * @version    1.0.0
 */

class Order {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Creates a new order record and returns the generated order ID.
     *
     * @param int   $userId      The user's ID.
     * @param float $totalAmount The order's total amount.
     *
     * @return int The new order ID.
     */
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

    /**
     * Adds a single item to an existing order.
     *
     * @param int      $orderId    The order ID.
     * @param int|null $courseId   The course ID, or null.
     * @param int|null $workshopId The workshop ID, or null.
     * @param int      $quantity   The item quantity.
     * @param float    $unitPrice  The unit price.
     * @param float    $subtotal   The line item subtotal.
     *
     * @return void
     */
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

    /**
     * Creates a course enrollment for a user if not already enrolled.
     *
     * @param int $userId   The user's ID.
     * @param int $courseId The course ID.
     *
     * @return bool True on success, false if already enrolled.
     */
    public function createEnrollment($userId, $courseId) {
        $sql = "SELECT EnrollmentId FROM Enrollment WHERE UserId = :user_id AND CourseId = :course_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':course_id' => $courseId]);
        
        if ($stmt->fetch()) {
            return false;
        }
        
        $sql = "INSERT INTO Enrollment (UserId, CourseId, CompletionStatus)
                VALUES (:user_id, :course_id, 'not_started')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':course_id' => $courseId]);
        return true;
    }

    /**
     * Creates a workshop registration for a user if not already registered.
     *
     * Decrements available seats upon successful registration.
     *
     * @param int $userId     The user's ID.
     * @param int $workshopId The workshop ID.
     *
     * @return bool True on success, false if already registered.
     */
    public function createWorkshopRegistration($userId, $workshopId) {
        $sql = "SELECT WorkshopRegistrationId FROM WorkshopRegistration WHERE UserId = :user_id AND WorkshopId = :workshop_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':workshop_id' => $workshopId]);

        if ($stmt->fetch()) {
            return false;
        }

        $sql = "INSERT INTO WorkshopRegistration (UserId, WorkshopId, AttendanceStatus)
                VALUES (:user_id, :workshop_id, 'registered')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':workshop_id' => $workshopId]);

        $this->db->prepare(
            "UPDATE WorkshopSession
             SET AvailableSeats = GREATEST(AvailableSeats - 1, 0)
             WHERE WorkshopId = :workshop_id AND AvailableSeats > 0"
        )->execute([':workshop_id' => $workshopId]);

        return true;
    }

    /**
     * Retrieves all orders for a user with associated item details.
     *
     * @param int $userId The user's ID.
     *
     * @return array Array of order records with item metadata.
     */
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
