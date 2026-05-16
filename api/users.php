<?php

require_once '../controllers/UserController.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$userController = new UserController();

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'all_users') {

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            json_response(['success' => false, 'message' => 'Unauthorized. Admin access required.'], 401);
        }
        $result = $userController->getAllUsers();
        json_response($result, 200);

    } elseif ($action === 'get_user') {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            json_response(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $userIdToFetch = $_GET['id'] ?? null;
        $result = $userController->getUserForAdmin($userIdToFetch);
        json_response($result, $result['success'] ? 200 : 404);
    } elseif ($action === 'profile') {
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
        
    } elseif ($action === 'delete_user') {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            json_response(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $userIdToDelete = $data['user_id'] ?? null;
        $result = $userController->deleteUser($userIdToDelete);
        json_response($result, $result['success'] ? 200 : 400);

    } elseif ($action === 'admin_update_user') {

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            json_response(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $userIdToUpdate = $data['user_id'] ?? null;
        $result = $userController->adminUpdateUser($userIdToUpdate, $data);
        json_response($result, $result['success'] ? 200 : 400);
    
    } elseif ($action === 'admin_create_user') {
        
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            json_response(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $result = $userController->adminCreateUser($data);
        json_response($result, $result['success'] ? 201 : 400);
    
    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }
    
} else {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}
