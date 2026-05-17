<?php
/**
 * Session Verification API Endpoint
 *
 * Checks whether the current user has an active authenticated session.
 * Returns the authentication status along with basic user details
 * (ID, name, email, role) if authenticated.
 *
 * @package    Taliq\Api
 * @subpackage Session
 * @version    1.0.0
 *
 * @method GET Returns authentication status and user data if logged in.
 */

require_once '../config/constants.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    json_response([
        'authenticated' => false,
        'user' => null
    ]);
}

json_response([
    'authenticated' => true,
    'user' => [
        'id' => $_SESSION['user_id'],
        'first_name' => $_SESSION['first_name'],
        'last_name' => $_SESSION['last_name'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ]
]);
