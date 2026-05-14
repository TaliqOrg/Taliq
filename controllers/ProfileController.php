<?php
/**
 * ProfileController v2.0 (Refactored)
 * Simplified controller with unified points system
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Profile.php';

class ProfileController {
    private $profileModel;

    public function __construct() {
        $this->profileModel = new Profile();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // USER INFO
    // ══════════════════════════════════════════════════════════════════════════

    public function getUserInfo($userId) {
        $user = $this->profileModel->getUserInfo($userId);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Format the data
        $user['FullName'] = $user['FirstName'] . ' ' . $user['LastName'];
        $user['MemberSince'] = date('F Y', strtotime($user['CreatedAt']));
        
        return [
            'success' => true,
            'user' => $user
        ];
    }

    public function updateUserInfo($userId, $data) {
        // Validate required fields
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
            return ['success' => false, 'message' => 'First name, last name, and email are required'];
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        $result = $this->profileModel->updateUserInfo($userId, $data);
        
        if ($result) {
            return ['success' => true, 'message' => 'Profile updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update profile'];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // GAMIFICATION
    // ══════════════════════════════════════════════════════════════════════════

    public function getGamificationData($userId) {
        // Update streak on each visit
        $this->profileModel->updateStreak($userId);
        
        // Get stats directly from User table
        $stats = $this->profileModel->getGamificationStats($userId);
        $levels = $this->profileModel->getAllLevels();
        
        return [
            'success' => true,
            'stats' => $stats,
            'levels' => $levels
        ];
    }

    public function getPoints($userId) {
        $points = $this->profileModel->getPoints($userId);
        $level = $this->profileModel->getUserLevel($userId);
        
        return [
            'success' => true,
            'points' => $points,
            'level' => $level
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ENROLLED COURSES
    // ══════════════════════════════════════════════════════════════════════════

    public function getEnrolledCourses($userId) {
        require_once __DIR__ . '/../models/Lesson.php';
        $lessonModel = new Lesson();
        
        $courses = $this->profileModel->getEnrolledCourses($userId);
        
        // Enrich with accurate progress data
        foreach ($courses as &$course) {
            if (isset($course['CourseId'])) {
                $progress = $lessonModel->getCourseProgress($userId, $course['CourseId']);
                if ($progress) {
                    $course['ProgressPercentage'] = $progress['progress_percentage'];
                    $course['CompletedLessons'] = $progress['completed_lessons'];
                    $course['TotalLessons'] = $progress['total_lessons'];
                }
            }
        }
        
        return [
            'success' => true,
            'courses' => $courses,
            'count' => count($courses)
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // CERTIFICATES
    // ══════════════════════════════════════════════════════════════════════════

    public function getCertificates($userId) {
        $certificates = $this->profileModel->getCertificates($userId);
        
        // Format dates
        foreach ($certificates as &$cert) {
            $cert['IssueDateFormatted'] = date('F j, Y', strtotime($cert['IssueDate']));
        }
        
        return [
            'success' => true,
            'certificates' => $certificates,
            'count' => count($certificates)
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ORDER HISTORY
    // ══════════════════════════════════════════════════════════════════════════

    public function getOrders($userId) {
        $orders = $this->profileModel->getOrders($userId);
        
        // Format data
        foreach ($orders as &$order) {
            $order['OrderNumber'] = 'TLQ-' . date('Y', strtotime($order['OrderDate'])) . '-' . str_pad($order['OrderId'], 4, '0', STR_PAD_LEFT);
            $order['OrderDateFormatted'] = date('F j, Y', strtotime($order['OrderDate']));
            $order['TotalFormatted'] = number_format($order['TotalAmount'], 2) . ' SAR';
        }
        
        return [
            'success' => true,
            'orders' => $orders,
            'count' => count($orders)
        ];
    }

    public function getOrderDetails($orderId, $userId) {
        $order = $this->profileModel->getOrderById($orderId, $userId);
        
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }
        
        $order['OrderNumber'] = 'TLQ-' . date('Y', strtotime($order['OrderDate'])) . '-' . str_pad($order['OrderId'], 4, '0', STR_PAD_LEFT);
        $order['OrderDateFormatted'] = date('F j, Y', strtotime($order['OrderDate']));
        $order['TotalFormatted'] = number_format($order['TotalAmount'], 2) . ' SAR';
        
        return [
            'success' => true,
            'order' => $order
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // DASHBOARD SUMMARY
    // ══════════════════════════════════════════════════════════════════════════

    public function getDashboardSummary($userId) {
        $user = $this->profileModel->getUserInfo($userId);
        $gamification = $this->profileModel->getGamificationStats($userId);
        $enrolledCount = count($this->profileModel->getEnrolledCourses($userId));
        $certificatesCount = $this->profileModel->getCertificatesCount($userId);
        
        return [
            'success' => true,
            'summary' => [
                'user' => [
                    'name' => $user['FirstName'] . ' ' . $user['LastName'],
                    'email' => $user['Email'],
                    'member_since' => date('F Y', strtotime($user['CreatedAt'])),
                    'profile_image' => $user['ProfileImageUrl'] ?? null
                ],
                'gamification' => [
                    'points' => $user['Points'] ?? 0,
                    'level' => $gamification['LevelName'],
                    'level_number' => $gamification['Level'],
                    'streak' => $gamification['CurrentStreak'],
                    'certificates' => $certificatesCount
                ],
                'enrolled_courses' => $enrolledCount
            ]
        ];
    }
}
