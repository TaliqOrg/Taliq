<?php
/**
 * Points API Endpoint
 *
 * Retrieves gamification data for authenticated users. Points are stored
 * directly in the User table. Supports fetching user points with streak
 * data and level information, as well as listing all available levels.
 * Includes fallback support for both Level and LevelDefinition table schemas.
 *
 * @package    Taliq\Api
 * @subpackage Points
 * @version    2.0.0
 *
 * @method GET Retrieves user points/streaks or all level definitions.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once '../config/database.php';

global $pdo;

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$userId = $_SESSION['user_id'];

switch ($action) {
    case 'get_user_points':
        $stmt = $pdo->prepare("SELECT Points, CurrentStreak, LongestStreak FROM User WHERE UserId = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM Level WHERE MinPoints <= ? ORDER BY MinPoints DESC LIMIT 1");
                $stmt->execute([$user['Points']]);
                $level = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $stmt = $pdo->prepare("SELECT * FROM LevelDefinition WHERE MinPoints <= ? ORDER BY MinPoints DESC LIMIT 1");
                $stmt->execute([$user['Points']]);
                $level = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            echo json_encode([
                'success' => true,
                'points' => [
                    'TotalPoints' => (int)$user['Points'],
                    'CurrentStreak' => (int)($user['CurrentStreak'] ?? 0),
                    'LongestStreak' => (int)($user['LongestStreak'] ?? 0)
                ],
                'level' => $level ?: ['LevelNumber' => 1, 'LevelName' => 'Course Hunter']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'points' => ['TotalPoints' => 0, 'CurrentStreak' => 0, 'LongestStreak' => 0],
                'level' => ['LevelNumber' => 1, 'LevelName' => 'Course Hunter']
            ]);
        }
        break;

    case 'get_levels':
        // Get all levels
        try {
            $stmt = $pdo->prepare("SELECT * FROM Level ORDER BY LevelNumber ASC");
            $stmt->execute();
            $levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $stmt = $pdo->prepare("SELECT * FROM LevelDefinition ORDER BY LevelNumber ASC");
            $stmt->execute();
            $levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode([
            'success' => true,
            'levels' => $levels
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
