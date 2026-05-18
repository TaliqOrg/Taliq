<?php
/**
 * Profile Controller
 *
 * Provides user profile and dashboard functionality with a unified points system.
 * Handles user information retrieval and updates, gamification data with streak
 * tracking, enrolled courses with progress enrichment, certificates with formatted
 * dates, order history with formatted totals, and a consolidated dashboard summary.
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
     * Appends computed FullName and formatted MemberSince fields.
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
     * Validates required fields and email format before persisting.
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
     * Updates the user's login streak on each invocation.
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
     * Retrieves the user's current points and level information.
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
     * Retrieves all courses the user is enrolled in with progress data.
     *
     * Enriches each enrollment with accurate lesson completion percentages
     * from the Lesson model.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status, courses, and count.
     */
    public function getEnrolledCourses($userId) {
        require_once __DIR__ . '/../models/Lesson.php';
        $lessonModel = new Lesson();
        
        $courses = $this->profileModel->getEnrolledCourses($userId);
        
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

    /**
     * Retrieves all certificates earned by the user.
     *
     * Appends a human-readable formatted issue date to each certificate.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status, certificates, and count.
     */
    public function getCertificates($userId) {
        $certificates = $this->profileModel->getCertificates($userId);
        
        foreach ($certificates as &$cert) {
            $cert['IssueDateFormatted'] = date('F j, Y', strtotime($cert['IssueDate']));
        }
        
        return [
            'success' => true,
            'certificates' => $certificates,
            'count' => count($certificates)
        ];
    }

    /**
     * Retrieves the user's order history with formatted display values.
     *
     * Generates an order number (TLQ-YYYY-XXXX), formatted date, and
     * formatted total for each order.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status, orders, and count.
     */
    public function getOrders($userId) {
        $orders = $this->profileModel->getOrders($userId);
        
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

    /**
     * Retrieves the details of a specific order.
     *
     * @param int $orderId The order ID to retrieve.
     * @param int $userId  The authenticated user's ID (for ownership verification).
     *
     * @return array Associative array with success status and order data.
     */
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

    /**
     * Retrieves a consolidated dashboard summary for the user.
     *
     * Aggregates user info, gamification stats, enrolled course count,
     * and certificate count into a single response.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status and summary data.
     */
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
