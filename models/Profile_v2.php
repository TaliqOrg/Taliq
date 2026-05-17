<?php
/**
 * Profile Model (Version 2)
 *
 * Simplified profile data layer with unified points system. Provides user info
 * management, gamification with level progression, streak tracking, enrolled
 * courses with accurate progress, certificates, and order history.
 *
 * @package    Taliq\Models
 * @subpackage Profile
 * @version    2.0.0
 */

require_once __DIR__ . '/../config/database.php';

class Profile {
    /** @var PDO */
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /** @return array|false */
    public function getUserInfo($userId) {
        $sql = "SELECT UserId, FirstName, LastName, Email, PhoneNumber, 
                       DateOfBirth, Country, City, ProfileImageUrl, 
                       Points, CurrentStreak, LongestStreak, CreatedAt
                FROM User WHERE UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** @return bool */
    public function updateUserInfo($userId, $data) {
        $sql = "UPDATE User SET 
                    FirstName = :first_name,
                    LastName = :last_name,
                    Email = :email,
                    PhoneNumber = :phone,
                    DateOfBirth = :dob,
                    Country = :country,
                    City = :city
                WHERE UserId = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'],
            ':phone' => $data['phone'] ?? null,
            ':dob' => $data['date_of_birth'] ?? null,
            ':country' => $data['country'] ?? null,
            ':city' => $data['city'] ?? null,
            ':user_id' => $userId
        ]);
    }

    /** @return int */
    public function getPoints($userId) {
        $sql = "SELECT Points FROM User WHERE UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /** @return bool */
    public function addPoints($userId, $points = 50) {
        $sql = "UPDATE User SET Points = Points + :points WHERE UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':points' => $points, ':user_id' => $userId]);
    }

    /** @return array */
    public function getUserLevel($userId) {
        $points = $this->getPoints($userId);
        
        $sql = "SELECT * FROM Level 
                WHERE MinPoints <= :points 
                ORDER BY MinPoints DESC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':points' => $points]);
        $level = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$level) {
            // Return default level
            return [
                'LevelNumber' => 1,
                'LevelName' => 'Course Hunter',
                'MinPoints' => 0,
                'MaxPoints' => 499,
                'BadgeIcon' => 'search'
            ];
        }
        
        return $level;
    }

    /** @return array */
    public function getGamificationStats($userId) {
        $user = $this->getUserInfo($userId);
        $level = $this->getUserLevel($userId);
        $nextLevel = $this->getNextLevel($level['LevelNumber']);
        $progressPercentage = 0;
        if ($nextLevel && $level['MaxPoints']) {
            $pointsInLevel = $user['Points'] - $level['MinPoints'];
            $levelRange = $level['MaxPoints'] - $level['MinPoints'];
            $progressPercentage = min(100, round(($pointsInLevel / $levelRange) * 100));
        } else {
            $progressPercentage = 100; // Max level
        }
        
        return [
            'TotalPoints' => $user['Points'],
            'Level' => $level['LevelNumber'],
            'LevelName' => $level['LevelName'],
            'BadgeIcon' => $level['BadgeIcon'],
            'MinPoints' => $level['MinPoints'],
            'MaxPoints' => $level['MaxPoints'],
            'CurrentStreak' => $user['CurrentStreak'],
            'LongestStreak' => $user['LongestStreak'],
            'ProgressPercentage' => $progressPercentage,
            'NextLevel' => $nextLevel,
            'CertificatesCount' => $this->getCertificatesCount($userId)
        ];
    }

    /** @return array|false */
    private function getNextLevel($currentLevelNumber) {
        $sql = "SELECT * FROM Level WHERE LevelNumber = :level";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':level' => $currentLevelNumber + 1]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** @return array */
    public function getAllLevels() {
        $sql = "SELECT * FROM Level ORDER BY LevelNumber ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return int */
    public function updateStreak($userId) {
        $sql = "SELECT LastActivityDate, CurrentStreak, LongestStreak FROM User WHERE UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $today = date('Y-m-d');
        $lastActivity = $data['LastActivityDate'];
        $currentStreak = $data['CurrentStreak'] ?? 0;
        $longestStreak = $data['LongestStreak'] ?? 0;
        
        if ($lastActivity === $today) {
            return $currentStreak;
        }
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        if ($lastActivity === $yesterday) {
            $currentStreak++;
        } else {
            $currentStreak = 1;
        }
        
        $longestStreak = max($longestStreak, $currentStreak);
        
        $sql = "UPDATE User SET 
                    CurrentStreak = :streak, 
                    LongestStreak = :longest,
                    LastActivityDate = :today 
                WHERE UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':streak' => $currentStreak,
            ':longest' => $longestStreak,
            ':today' => $today,
            ':user_id' => $userId
        ]);
        
        return $currentStreak;
    }

    /** @return array */
    public function getEnrolledCourses($userId) {
        // Get course enrollments with accurate progress
        $sql = "SELECT 
                    e.EnrollmentId,
                    e.ProgressPercentage,
                    e.CompletionStatus,
                    e.EnrollmentDate,
                    e.CompletedAt,
                    c.CourseId,
                    c.Title,
                    c.ThumbnailUrl,
                    c.DurationHours,
                    'course' as ItemType,
                    (SELECT COUNT(*) FROM Lesson WHERE CourseId = c.CourseId) as TotalLessons,
                    (SELECT COUNT(*) FROM LessonProgress lp 
                     WHERE lp.UserId = e.UserId AND lp.CourseId = c.CourseId AND lp.IsCompleted = TRUE) as CompletedLessons
                FROM Enrollment e
                JOIN Course c ON e.CourseId = c.CourseId
                WHERE e.UserId = :user_id
                ORDER BY e.EnrollmentDate DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($courses as &$course) {
            if ($course['TotalLessons'] > 0) {
                $course['ProgressPercentage'] = round(($course['CompletedLessons'] / $course['TotalLessons']) * 100, 2);
            }
        }
        
        // Get workshop registrations
        $sql = "SELECT 
                    wr.RegistrationId as EnrollmentId,
                    CASE WHEN wr.AttendanceStatus = 'attended' THEN 100 ELSE 0 END as ProgressPercentage,
                    CASE WHEN wr.AttendanceStatus = 'attended' THEN 'completed' ELSE 'registered' END as CompletionStatus,
                    wr.RegistrationDate as EnrollmentDate,
                    NULL as CompletedAt,
                    w.WorkshopId,
                    w.Title,
                    w.ThumbnailUrl,
                    w.DurationHours,
                    'workshop' as ItemType,
                    0 as TotalLessons,
                    0 as CompletedLessons
                FROM WorkshopRegistration wr
                JOIN Workshop w ON wr.WorkshopId = w.WorkshopId
                WHERE wr.UserId = :user_id
                ORDER BY wr.RegistrationDate DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $workshops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_merge($courses, $workshops);
    }

    /** @return array */
    public function getCertificates($userId) {
        $sql = "SELECT 
                    cert.CertificateId,
                    cert.CertificateCode,
                    cert.CertificateUrl,
                    cert.IssueDate,
                    COALESCE(c.Title, w.Title) as CourseTitle,
                    CASE WHEN cert.CourseId IS NOT NULL THEN 'course' ELSE 'workshop' END as ItemType
                FROM Certificate cert
                LEFT JOIN Course c ON cert.CourseId = c.CourseId
                LEFT JOIN Workshop w ON cert.WorkshopId = w.WorkshopId
                WHERE cert.UserId = :user_id
                ORDER BY cert.IssueDate DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return int */
    public function getCertificatesCount($userId) {
        $sql = "SELECT COUNT(*) FROM Certificate WHERE UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /** @return array */
    public function getOrders($userId) {
        $sql = "SELECT 
                    o.OrderId,
                    o.OrderDate,
                    o.TotalAmount,
                    o.Status
                FROM `Order` o
                WHERE o.UserId = :user_id
                ORDER BY o.OrderDate DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($orders as &$order) {
            $order['Items'] = $this->getOrderItems($order['OrderId']);
        }
        
        return $orders;
    }

    private function getOrderItems($orderId) {
        $sql = "SELECT 
                    oi.OrderItemId,
                    oi.UnitPrice,
                    oi.Subtotal,
                    COALESCE(c.Title, w.Title) as Title,
                    COALESCE(c.ThumbnailUrl, w.ThumbnailUrl) as ThumbnailUrl,
                    CASE WHEN oi.CourseId IS NOT NULL THEN 'Online Course' ELSE 'On-site Workshop' END as ItemType
                FROM OrderItem oi
                LEFT JOIN Course c ON oi.CourseId = c.CourseId
                LEFT JOIN Workshop w ON oi.WorkshopId = w.WorkshopId
                WHERE oi.OrderId = :order_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array|false */
    public function getOrderById($orderId, $userId) {
        $sql = "SELECT * FROM `Order` WHERE OrderId = :order_id AND UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':order_id' => $orderId, ':user_id' => $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $order['Items'] = $this->getOrderItems($orderId);
        }
        
        return $order;
    }
}
