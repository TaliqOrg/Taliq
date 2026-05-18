<?php
/*
 * Task 5:  Seat Inventory (Enrollment Count + MaxStudents Decrement)
 * Task 7:  Buy (Enrollment on Purchase)
 * Author:  Abdullah Al Tamh
 */

class Enrollment {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

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
                $this->db->prepare("UPDATE Course SET EnrollmentCount = EnrollmentCount + 1, MaxStudents = GREATEST(MaxStudents - 1, 0) WHERE CourseId = :course_id")
                    ->execute([':course_id' => $courseId]);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error creating enrollment: " . $e->getMessage());
            return false;
        }
    }

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
