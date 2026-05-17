<?php
/**
 * Wishlist Model
 *
 * Handles all wishlist database operations including adding, removing,
 * checking item presence, retrieving all items with metadata, counting
 * items, fetching item identifiers, and clearing the wishlist.
 *
 * @package    Taliq\Models
 * @subpackage Wishlist
 * @version    1.0.0
 */

class Wishlist {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Adds a course or workshop to the user's wishlist.
     *
     * @param int      $userId     The user's ID.
     * @param int|null $courseId   The course ID, or null.
     * @param int|null $workshopId The workshop ID, or null.
     *
     * @return bool True on success.
     */
    public function add($userId, $courseId, $workshopId) {
        if ($courseId) {
            $sql = "INSERT IGNORE INTO Wishlist (UserId, CourseId) VALUES (:user_id, :course_id)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':user_id' => $userId, ':course_id' => $courseId]);
        } else {
            $sql = "INSERT IGNORE INTO Wishlist (UserId, WorkshopId) VALUES (:user_id, :workshop_id)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':user_id' => $userId, ':workshop_id' => $workshopId]);
        }
    }

    /**
     * Removes a course or workshop from the user's wishlist.
     *
     * @param int      $userId     The user's ID.
     * @param int|null $courseId   The course ID, or null.
     * @param int|null $workshopId The workshop ID, or null.
     *
     * @return bool True on success.
     */
    public function remove($userId, $courseId, $workshopId) {
        if ($courseId) {
            $sql = "DELETE FROM Wishlist WHERE UserId = :user_id AND CourseId = :course_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':user_id' => $userId, ':course_id' => $courseId]);
        } else {
            $sql = "DELETE FROM Wishlist WHERE UserId = :user_id AND WorkshopId = :workshop_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':user_id' => $userId, ':workshop_id' => $workshopId]);
        }
    }

    /**
     * Checks if a specific item exists in the user's wishlist.
     *
     * @param int      $userId     The user's ID.
     * @param int|null $courseId   The course ID, or null.
     * @param int|null $workshopId The workshop ID, or null.
     *
     * @return bool True if the item is in the wishlist.
     */
    public function isInWishlist($userId, $courseId, $workshopId) {
        if ($courseId) {
            $sql = "SELECT WishlistId FROM Wishlist WHERE UserId = :user_id AND CourseId = :course_id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId, ':course_id' => $courseId]);
        } else {
            $sql = "SELECT WishlistId FROM Wishlist WHERE UserId = :user_id AND WorkshopId = :workshop_id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId, ':workshop_id' => $workshopId]);
        }
        return $stmt->fetch() !== false;
    }

    /**
     * Retrieves all wishlist items with full course/workshop metadata.
     *
     * @param int $userId The user's ID.
     *
     * @return array Array of wishlist items with Title, Price, ThumbnailUrl, etc.
     */
    public function getAll($userId) {
        $sql = "SELECT 
                    w.WishlistId,
                    w.CourseId,
                    w.WorkshopId,
                    w.AddedAt,
                    COALESCE(c.Title, ws.Title) AS Title,
                    COALESCE(c.Description, ws.Description) AS Description,
                    COALESCE(c.Price, ws.Price) AS Price,
                    COALESCE(c.ThumbnailUrl, ws.ThumbnailUrl) AS ThumbnailUrl,
                    COALESCE(c.AverageRating, ws.AverageRating) AS AverageRating,
                    COALESCE(c.RatingCount, ws.RatingCount) AS RatingCount,
                    CASE WHEN w.CourseId IS NOT NULL THEN 'course' ELSE 'workshop' END AS CourseType
                FROM Wishlist w
                LEFT JOIN Course c ON w.CourseId = c.CourseId
                LEFT JOIN Workshop ws ON w.WorkshopId = ws.WorkshopId
                WHERE w.UserId = :user_id
                ORDER BY w.AddedAt DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns the total number of items in the user's wishlist.
     *
     * @param int $userId The user's ID.
     *
     * @return int The wishlist item count.
     */
    public function getCount($userId) {
        $sql = "SELECT COUNT(*) as count FROM Wishlist WHERE UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Returns all wishlist item keys in 'type_id' format for client-side lookups.
     *
     * @param int $userId The user's ID.
     *
     * @return array Array of item key strings (e.g., 'course_5', 'workshop_3').
     */
    public function getWishlistIds($userId) {
        $sql = "SELECT 
                    CASE WHEN CourseId IS NOT NULL THEN CONCAT('course_', CourseId) ELSE CONCAT('workshop_', WorkshopId) END AS item_key
                FROM Wishlist 
                WHERE UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $results;
    }

    /**
     * Removes all items from the user's wishlist.
     *
     * @param int $userId The user's ID.
     *
     * @return bool True on success.
     */
    public function clear($userId) {
        $sql = "DELETE FROM Wishlist WHERE UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }
}
