<?php
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
