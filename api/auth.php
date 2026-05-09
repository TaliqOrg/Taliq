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
