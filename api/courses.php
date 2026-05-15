<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../controllers/CourseController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new CourseController();

    // Pass $_POST and $_FILES directly to the controller!
    $result = $controller->createCourse($_POST, $_FILES);

    http_response_code($result['success'] ? 201 : 400);
    echo json_encode($result);
}
