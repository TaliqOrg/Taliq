
<?php
// 1. Add headers so the browser knows to expect JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/database.php";
include_once '../controllers/CourseController.php';

$controller = new CourseController($pdo);

if (isset($_GET['CourseId']) && isset($_GET['CourseType'])) {
    $controller->getCourseDetails($_GET['CourseId'], $_GET['CourseType']);
}
else{
    $controller->getAllCourses();
}

