<?php

require_once '../controllers/UserController.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$userController = new UserController();

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'profile') {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            json_response(['success' => false, 'message' => 'Not authenticated'], 401);
        }
        
        $result = $userController->getProfile($userId);
        json_response($result, $result['success'] ? 200 : 404);
        
    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }
    
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        json_response(['success' => false, 'message' => 'Invalid JSON data'], 400);
    }
    
    $action = $data['action'] ?? '';
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        json_response(['success' => false, 'message' => 'Not authenticated'], 401);
    }
    
    if ($action === 'update_profile') {
        $firstName = $data['first_name'] ?? '';
        $lastName = $data['last_name'] ?? '';
        $email = $data['email'] ?? '';
        $phoneNumber = $data['phone_number'] ?? null;
        
        $result = $userController->updateProfile($userId, $firstName, $lastName, $email, $phoneNumber);
        json_response($result, $result['success'] ? 200 : 400);
        
    } elseif ($action === 'change_password') {
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        
        $result = $userController->changePassword($userId, $currentPassword, $newPassword);
        json_response($result, $result['success'] ? 200 : 400);
        
    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }
    
} else {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}
