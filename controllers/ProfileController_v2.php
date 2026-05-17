<?php
/**
 * Profile Controller (Version 2)
 *
 * Simplified profile controller with a unified points system. Provides user
 * information management, gamification data with streak tracking, enrolled
 * courses listing, certificates, order history, and a consolidated dashboard
 * summary view.
 *
 * @package    Taliq\Controllers
 * @subpackage Profile
 * @version    2.0.0
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Profile.php';

class ProfileController {
    private $profileModel;

    public function __construct() {
        $this->profileModel = new Profile();
    }

    /**
     * Retrieves the authenticated user's profile information.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status and user data.
     */
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

    /**
     * Updates the user's profile information.
     *
     * @param int   $userId The authenticated user's ID.
     * @param array $data   Associative array with first_name, last_name, and email.
     *
     * @return array Associative array with success status and message.
     */
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

    /**
     * Retrieves gamification data including points, streaks, and level definitions.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status, stats, and levels.
     */
    public function getGamificationData($userId) {
        $this->profileModel->updateStreak($userId);
        
        $stats = $this->profileModel->getGamificationStats($userId);
        $levels = $this->profileModel->getAllLevels();
        
        return [
            'success' => true,
            'stats' => $stats,
            'levels' => $levels
        ];
    }

    /**
     * Retrieves the user's current points and level.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status, points, and level.
     */
    public function getPoints($userId) {
        $points = $this->profileModel->getPoints($userId);
        $level = $this->profileModel->getUserLevel($userId);
        
        return [
            'success' => true,
            'points' => $points,
            'level' => $level
        ];
    }

    /**
     * Retrieves all courses the user is enrolled in.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status, courses, and count.
     */
    public function getEnrolledCourses($userId) {
        $courses = $this->profileModel->getEnrolledCourses($userId);
        
        return [
            'success' => true,
            'courses' => $courses,
            'count' => count($courses)
        ];
    }

    /**
     * Retrieves all certificates earned by the user.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status, certificates, and count.
     */
    public function getCertificates($userId) {
        $certificates = $this->profileModel->getCertificates($userId);
        
        return [
            'success' => true,
            'certificates' => $certificates,
            'count' => count($certificates)
        ];
    }

    /**
     * Retrieves the user's order history.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status, orders, and count.
     */
    public function getOrders($userId) {
        $orders = $this->profileModel->getOrders($userId);
        
        return [
            'success' => true,
            'orders' => $orders,
            'count' => count($orders)
        ];
    }

    /**
     * Retrieves the details of a specific order.
     *
     * @param int $orderId The order ID to retrieve.
     * @param int $userId  The authenticated user's ID (for ownership verification).
     *
     * @return array Associative array with success status and order data.
     */
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

    /**
     * Retrieves a consolidated dashboard summary for the user.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status and summary data.
     */
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
