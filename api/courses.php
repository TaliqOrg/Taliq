
<?php
// 1. Add headers so the browser knows to expect JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/database.php";
include_once '../models/Course.php';

$database = (new Database())->getConnection();
$modelCourse = new Course($database);


$stmt = $modelCourse->GetAllPublishedCourses();
$num = $stmt->rowCount();


//echo $num;
//display all courses in database in home screen
if ($num > 0) {
    $course_arr["records"] = array();

    while ($row = $stmt->fetch()) {
        extract($row);
        $course_item = array(
            "CourseId" => $CourseId,
            "Title" => $Title,
            "Description" => html_entity_decode($Description),
            "Price" => $Price,
            "ThumbnailUrl" => $ThumbnailUrl,
            "CourseType" => $CourseType
        );
        $course_arr["records"][] = $course_item;
    }
    echo json_encode($course_arr);

}
else {
    http_response_code(404);
    echo json_encode(array("message" => "Record not found."));
}

?>

