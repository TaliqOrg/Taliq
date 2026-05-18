<?php
/**
 * Certificate API Endpoint
 *
 * Retrieves certificate details for authenticated users. Requires a valid
 * session and accepts a certificate ID via GET query parameter.
 *
 * @package    Taliq\Api
 * @subpackage Certificate
 * @version    1.0.0
 *
 * @method GET Retrieves a specific certificate by ID for the logged-in user.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../models/Certificate.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'get') {
    $certId = (int)($_GET['cert_id'] ?? 0);
    if (!$certId) {
        json_response(['success' => false, 'message' => 'Certificate ID required'], 400);
    }

    $certModel = new Certificate();
    $cert = $certModel->getById($certId, $userId);

    if (!$cert) {
        json_response(['success' => false, 'message' => 'Certificate not found'], 404);
    }

    $cert['IssueDateFormatted'] = date('F j, Y', strtotime($cert['IssueDate']));

    json_response(['success' => true, 'certificate' => $cert]);
} else {
    json_response(['success' => false, 'message' => 'Invalid action'], 400);
}
