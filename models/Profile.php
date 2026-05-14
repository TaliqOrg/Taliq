<?php
/**
 * Profile Model v2.0 (Refactored)
 * Simplified model with unified points system - Points stored in User table
 */

require_once __DIR__ . '/../config/database.php';

class Profile {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // USER INFO
    // ══════════════════════════════════════════════════════════════════════════
    
    public function getUserInfo($userId) {
        $sql = "SELECT UserId, FirstName, LastName, Email, PhoneNumber, 
                       DateOfBirth, Country, City, ProfileImageUrl, 
                       Points, CurrentStreak, LongestStreak, CreatedAt
                FROM User WHERE UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

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

    // ══════════════════════════════════════════════════════════════════════════
    // POINTS & GAMIFICATION (Simplified - Points in User table)
    // ══════════════════════════════════════════════════════════════════════════

    public function getPoints($userId) {
        $sql = "SELECT Points FROM User WHERE UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function addPoints($userId, $points = 50) {
        $sql = "UPDATE User SET Points = Points + :points WHERE UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':points' => $points, ':user_id' => $userId]);
    }

    public function getUserLevel($userId) {
        $points = $this->getPoints($userId);
        
        // Try Level table first, fall back to LevelDefinition for compatibility
        $sql = "SELECT * FROM Level WHERE MinPoints <= :points ORDER BY MinPoints DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([':points' => $points]);
            $level = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Try old table name
            $sql = "SELECT * FROM LevelDefinition WHERE MinPoints <= :points ORDER BY MinPoints DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':points' => $points]);
            $level = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if (!$level) {
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

    public function getGamificationStats($userId) {
        $user = $this->getUserInfo($userId);
        $points = (int)($user['Points'] ?? 0);
        $level = $this->getUserLevel($userId);
        $nextLevel = $this->getNextLevel($level['LevelNumber'] ?? 1);
        
        // Calculate progress to next level
        $progressPercentage = 0;
        $currentLevelMin = (int)($level['MinPoints'] ?? 0);
        
        if ($nextLevel) {
            $nextLevelMin = (int)($nextLevel['MinPoints'] ?? 0);
            $levelRange = $nextLevelMin - $currentLevelMin;
            $pointsInLevel = $points - $currentLevelMin;
            
            if ($levelRange > 0) {
                $progressPercentage = min(100, round(($pointsInLevel / $levelRange) * 100));
            }
        } else {
            // Max level reached
            $progressPercentage = 100;
        }
        
        return [
            'TotalPoints' => $points,
            'Level' => $level['LevelNumber'] ?? 1,
            'LevelName' => $level['LevelName'] ?? 'Course Hunter',
            'BadgeIcon' => $level['BadgeIcon'] ?? 'search',
            'MinPoints' => $currentLevelMin,
            'MaxPoints' => $level['MaxPoints'] ?? 499,
            'CurrentStreak' => (int)($user['CurrentStreak'] ?? 0),
            'LongestStreak' => (int)($user['LongestStreak'] ?? 0),
            'ProgressPercentage' => $progressPercentage,
            'NextLevel' => $nextLevel,
            'CertificatesCount' => $this->getCertificatesCount($userId)
        ];
    }

    private function getNextLevel($currentLevelNumber) {
        // Try Level table first
        $sql = "SELECT * FROM Level WHERE LevelNumber = :level";
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([':level' => $currentLevelNumber + 1]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Try old table
            $sql = "SELECT * FROM LevelDefinition WHERE LevelNumber = :level";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':level' => $currentLevelNumber + 1]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    public function getAllLevels() {
        // Try Level table first
        try {
            $sql = "SELECT * FROM Level ORDER BY LevelNumber ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Fall back to old table
            $sql = "SELECT * FROM LevelDefinition ORDER BY LevelNumber ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function updateStreak($userId) {
        $sql = "SELECT LastActivityDate, CurrentStreak, LongestStreak FROM User WHERE UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) return 0;
        
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

    // ══════════════════════════════════════════════════════════════════════════
    // ENROLLED COURSES
    // ══════════════════════════════════════════════════════════════════════════

    public function getEnrolledCourses($userId) {
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
                    'course' as ItemType
                FROM Enrollment e
                JOIN Course c ON e.CourseId = c.CourseId
                WHERE e.UserId = :user_id
                ORDER BY e.EnrollmentDate DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Also get workshop registrations
        $sql = "SELECT 
                    wr.WorkshopRegistrationId as EnrollmentId,
                    CASE WHEN wr.AttendanceStatus = 'attended' THEN 100 ELSE 0 END as ProgressPercentage,
                    CASE WHEN wr.AttendanceStatus = 'attended' THEN 'completed' ELSE 'registered' END as CompletionStatus,
                    wr.RegistrationDate as EnrollmentDate,
                    NULL as CompletedAt,
                    w.WorkshopId,
                    w.Title,
                    w.ThumbnailUrl,
                    w.DurationHours,
                    'workshop' as ItemType
                FROM WorkshopRegistration wr
                JOIN Workshop w ON wr.WorkshopId = w.WorkshopId
                WHERE wr.UserId = :user_id
                ORDER BY wr.RegistrationDate DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $workshops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_merge($courses, $workshops);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // CERTIFICATES
    // ══════════════════════════════════════════════════════════════════════════

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

    public function getCertificatesCount($userId) {
        $sql = "SELECT COUNT(*) FROM Certificate WHERE UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ORDER HISTORY
    // ══════════════════════════════════════════════════════════════════════════

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
        
        // Get items for each order
        foreach ($orders as &$order) {
            $order['Items'] = $this->getOrderItems($order['OrderId']);
        }
        
        return $orders;
    }

    private function getOrderItems($orderId) {
        $sql = "SELECT 
                    oi.OrderItemId,
                    oi.Quantity,
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
