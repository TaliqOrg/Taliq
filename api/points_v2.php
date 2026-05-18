<?php
/**
 * Points API Endpoint (Version 2)
 *
 * Simplified points API with points stored directly in the User table.
 * Provides user points retrieval with streak and level data, as well as
 * a full listing of all level definitions.
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

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

switch ($action) {
    case 'get_user_points':
        $stmt = $pdo->prepare("SELECT Points, CurrentStreak, LongestStreak FROM User WHERE UserId = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Get level info
            $stmt = $pdo->prepare("SELECT * FROM Level WHERE MinPoints <= ? ORDER BY MinPoints DESC LIMIT 1");
            $stmt->execute([$user['Points']]);
            $level = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'points' => [
                    'TotalPoints' => (int)$user['Points'],
                    'CurrentStreak' => (int)$user['CurrentStreak'],
                    'LongestStreak' => (int)$user['LongestStreak']
                ],
                'level' => $level
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'points' => [
                    'TotalPoints' => 0,
                    'CurrentStreak' => 0,
                    'LongestStreak' => 0
                ],
                'level' => null
            ]);
        }
        break;

    case 'get_levels':
        $stmt = $pdo->prepare("SELECT * FROM Level ORDER BY LevelNumber ASC");
        $stmt->execute();
        $levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
