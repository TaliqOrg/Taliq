<?php
/**
 * Workshops API Endpoint
 *
 * Provides workshop-related data retrieval. Currently supports fetching
 * session schedules for a specific workshop by its ID.
 *
 * @package    Taliq\Api
 * @subpackage Workshops
 * @version    1.0.0
 *
 * @method GET Retrieves workshop sessions by workshop ID.
 */

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../models/Workshop.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$workshopModel = new Workshop();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'sessions') {
        $workshopId = $_GET['workshop_id'] ?? null;
        if (!$workshopId) {
            json_response(['success' => false, 'message' => 'Workshop ID is required'], 400);
        }
        $sessions = $workshopModel->getSessions($workshopId);
        json_response(['success' => true, 'sessions' => $sessions]);
    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }

} else {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}
