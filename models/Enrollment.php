<?php
/**
 * Enrollment Model
 *
 * Handles course enrollment database operations including enrollment checks,
 * retrieval, creation with enrollment count tracking, and progress updates
 * with automatic completion timestamps.
 *
 * @package    Taliq\Models
 * @subpackage Enrollment
 * @version    1.0.0
 */

class Enrollment {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Checks if a user is enrolled in a specific course.
     *
     * @param int $userId   The user's ID.
     * @param int $courseId The course ID.
     *
     * @return bool True if enrolled.
     */
    public function isUserEnrolled($userId, $courseId) {
        $sql = "SELECT EnrollmentId FROM Enrollment 
                WHERE UserId = :user_id AND CourseId = :course_id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':course_id' => $courseId
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /**
     * Retrieves a specific enrollment record.
     *
     * @param int $userId   The user's ID.
     * @param int $courseId The course ID.
     *
     * @return array|false The enrollment record, or false if not found.
     */
    public function getEnrollment($userId, $courseId) {
        $sql = "SELECT * FROM Enrollment 
                WHERE UserId = :user_id AND CourseId = :course_id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':course_id' => $courseId
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all enrollments for a user with course metadata.
     *
     * @param int $userId The user's ID.
     *
     * @return array Array of enrollment records with course details.
     */
    public function getUserEnrollments($userId) {
        $sql = "SELECT 
                    e.EnrollmentId,
                    e.CourseId,
                    e.EnrollmentDate,
                    e.ProgressPercentage,
                    e.CompletionStatus,
                    e.CompletedAt,
                    c.Title,
                    c.ThumbnailUrl,
                    c.DurationHours
                FROM Enrollment e
                INNER JOIN Course c ON e.CourseId = c.CourseId
                WHERE e.UserId = :user_id
                ORDER BY e.EnrollmentDate DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Creates a new enrollment and increments the course enrollment count.
     *
     * @param int $userId   The user's ID.
     * @param int $courseId The course ID.
     *
     * @return bool True on success, false on failure or duplicate.
     */
    public function createEnrollment($userId, $courseId) {
        try {
            $sql = "INSERT INTO Enrollment (UserId, CourseId, EnrollmentDate, CompletionStatus)
                    VALUES (:user_id, :course_id, NOW(), 'not_started')";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':user_id' => $userId,
                ':course_id' => $courseId
            ]);
            
            if ($result) {
                $this->db->prepare("UPDATE Course SET EnrollmentCount = EnrollmentCount + 1 WHERE CourseId = :course_id")
                    ->execute([':course_id' => $courseId]);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error creating enrollment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates the progress and completion status of an enrollment.
     *
     * Automatically sets CompletedAt when status is 'completed'.
     *
     * @param int    $userId              The user's ID.
     * @param int    $courseId            The course ID.
     * @param float  $progressPercentage The new progress percentage (0-100).
     * @param string $status              The completion status.
     *
     * @return bool True on success, false on failure.
     */
    public function updateProgress($userId, $courseId, $progressPercentage, $status) {
        try {
            $sql = "UPDATE Enrollment 
                    SET ProgressPercentage = :progress,
                        CompletionStatus = :status,
                        CompletedAt = IF(:status = 'completed', NOW(), NULL)
                    WHERE UserId = :user_id AND CourseId = :course_id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':progress' => $progressPercentage,
                ':status' => $status,
                ':user_id' => $userId,
                ':course_id' => $courseId
            ]);
        } catch (PDOException $e) {
            error_log("Error updating enrollment progress: " . $e->getMessage());
            return false;
        }
    }
}
