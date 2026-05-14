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
        
        $user['FullName'] = $user['FirstName'] . ' ' . $user['LastName'];
        $user['MemberSince'] = date('F Y', strtotime($user['CreatedAt']));
        
        return [
            'success' => true,
            'user' => $user
        ];
    }

    public function updateUserInfo($userId, $data) {
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
            return ['success' => false, 'message' => 'First name, last name, and email are required'];
        }
        
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
    // GAMIFICATION (Simplified)
    // ══════════════════════════════════════════════════════════════════════════

    public function getGamificationData($userId) {
        // Update streak on each visit
        $this->profileModel->updateStreak($userId);
        
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
        $courses = $this->profileModel->getEnrolledCourses($userId);
        
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
        
        return [
            'success' => true,
            'certificates' => $certificates,
            'count' => count($certificates)
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ORDERS
    // ══════════════════════════════════════════════════════════════════════════

    public function getOrders($userId) {
        $orders = $this->profileModel->getOrders($userId);
        
        return [
            'success' => true,
            'orders' => $orders,
            'count' => count($orders)
        ];
    }

    public function getOrderById($orderId, $userId) {
        $order = $this->profileModel->getOrderById($orderId, $userId);
        
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }
        
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
        $stats = $this->profileModel->getGamificationStats($userId);
        $enrolledCount = count($this->profileModel->getEnrolledCourses($userId));
        $certificatesCount = $this->profileModel->getCertificatesCount($userId);
        
        return [
            'success' => true,
            'summary' => [
                'user' => [
                    'name' => $user['FirstName'] . ' ' . $user['LastName'],
                    'email' => $user['Email'],
                    'member_since' => date('F Y', strtotime($user['CreatedAt'])),
                    'profile_image' => $user['ProfileImageUrl']
                ],
                'gamification' => [
                    'points' => $user['Points'],
                    'level' => $stats['LevelName'],
                    'level_number' => $stats['Level'],
                    'streak' => $stats['CurrentStreak'],
                    'certificates' => $certificatesCount
                ],
                'enrolled_courses' => $enrolledCount
            ]
        ];
    }
}
