<?php
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET, POST, DELETE");

    require_once '../controllers/CourseController.php';

    $controller = new CourseController();

    // form submission to ADD COURSE
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Pass $_POST and $_FILES to the controller!
        $result = $controller->createCourse($_POST, $_FILES);

        http_response_code($result['success'] ? 201 : 400);
        echo json_encode($result);
    }

    // fetching data to FETCH COURSES
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {

        $result = $controller->getAllCourses();
        http_response_code(200);

        echo json_encode($result);
    }

    // to DELETE COURSE
    else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

        $id = isset($_GET['id']) ? $_GET['id'] : null;

        $result = $controller->deleteCourse($id);
        http_response_code($result['success'] ? 200 : 400);
        
        echo json_encode($result);
    }

?>
