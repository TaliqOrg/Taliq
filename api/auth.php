<?php

require_once '../controllers/AuthController.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$authController = new AuthController();

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        json_response(['success' => false, 'message' => 'Invalid JSON data'], 400);
    }
    
    $action = $data['action'] ?? '';
    
    if ($action === 'login') {
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        
        $result = $authController->login($email, $password);
        json_response($result, $result['success'] ? 200 : 401);
        
    } elseif ($action === 'register') {
        $firstName = $data['first_name'] ?? '';
        $lastName = $data['last_name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $phoneNumber = $data['phone_number'] ?? null;
        
        $result = $authController->register($firstName, $lastName, $email, $password, $phoneNumber);
        json_response($result, $result['success'] ? 201 : 400);
        
    } elseif ($action === 'logout') {
        $result = $authController->logout();
        json_response($result);
        
    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }
    
} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'logout') {
        $result = $authController->logout();
        header('Location: /taleeq/Taliq/pages/login.html');
        exit();
    } else {
        json_response(['success' => false, 'message' => 'Invalid action'], 400);
    }
    
} else {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}
