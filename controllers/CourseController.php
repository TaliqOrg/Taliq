<?php
include_once '../models/Course.php';

class CourseController {
    private $modelCourse;

    public function __construct($pdo) {
        $this->modelCourse = new Course($pdo);
    }

    // Method to handle fetching ALL courses
    public function getAllCourses() {
        $stmt = $this->modelCourse->GetAllPublishedCourses();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $course_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $course_item = array(
                    "CourseId" => $CourseId,
                    "Title" => $Title,
                    "Description" => html_entity_decode($Description),
                    "Price" => $Price,
                    "ThumbnailUrl" => $ThumbnailUrl,
                    "CourseType" => $CourseType,
                    "AverageRating" => $AverageRating,
                    "RatingCount" => $RatingCount,
                );
                $course_arr["records"][] = $course_item;
            }
            http_response_code(200);
            echo json_encode($course_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Record not found."));
        }
    }

    public function getCourseDetails($CourseId = 1, $CourseType = "course") {
        $DataAboutCourse = $this->modelCourse->GetCourseById($CourseId, $CourseType);

        extract($DataAboutCourse);
        $CourseDetails = array(
            "CourseId" => $CourseId,
            "Title" => $Title,
            "Description" => html_entity_decode($Description),
            "Price" => $Price,
            "ThumbnailUrl" => $ThumbnailUrl,
            "Requirements" => $Requirements,
            "AverageRating" => $AverageRating,
            "RatingCount" => $RatingCount,
            "EnrollmentCount" => $EnrollmentCount,
            "DurationHours" => $DurationHours,
            "Level" => $Level,
            "Language" => $Language,
            "HasCertificate" => $HasCertificate,
            "LearningOutcomes" => $LearningOutcomes,

        );
        http_response_code(200);
        echo json_encode($CourseDetails);
    }

}
?>