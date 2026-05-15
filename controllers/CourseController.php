<?php
    require_once '../config/database.php';
    require_once '../models/Course.php';

    class CourseController {
        private $course;

        public function __construct() {
            $this->course = new Course();
        }

        // We now receive $_POST as $postData and $_FILES as $fileData
        public function createCourse($postData, $fileData) {
            
            if(empty($postData['title']) || empty($postData['category_id'])) {
                return ["success" => false, "message" => "Title and Category ID are required."];
            }

            $thumbnailPath = "";
            
            if (isset($fileData['thumbnail']) && $fileData['thumbnail']['error'] === UPLOAD_ERR_OK) {
                
                $uploadDir = '../images/'; 
                
                $fileName = time() . '_' . basename($fileData['thumbnail']['name']);
                $targetFilePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($fileData['thumbnail']['tmp_name'], $targetFilePath)) {
                    $thumbnailPath = 'images/' . $fileName; 
                } else {
                    return ["success" => false, "message" => "Failed to save the image to the folder."];
                }
            } else {
                return ["success" => false, "message" => "Please upload a thumbnail image."];
            }

            $courseData = [
                'CategoryId'    => htmlspecialchars(strip_tags($postData['category_id'])),
                'Title'         => htmlspecialchars(strip_tags($postData['title'])),
                'Description'   => htmlspecialchars(strip_tags($postData['description'] ?? '')),
                'Price'         => isset($postData['price']) ? (float)$postData['price'] : 0.00,
                'DurationHours' => isset($postData['duration']) ? (float)$postData['duration'] : null,
                'Level'         => $postData['level'] ?? 'Beginner',
                'Language'      => htmlspecialchars(strip_tags($postData['language'] ?? 'English')),
                'Thumbnail'  => $thumbnailPath, // Saved image path!
                'IsPublished'   => (isset($postData['is_published']) && $postData['is_published'] == 1) ? 1 : 0
            ];

            // Save to the Database
            if($this->course->create($courseData)) {
                return ["success" => true, "message" => "Course and Image created successfully!"];
            } else {
                return ["success" => false, "message" => "Failed to save course to the database."];
            }
        }

        public function getAllCourses() {

            $courses = $this->course->getAll();
            return ['success' => true, 'data' => $courses];
        }

        public function deleteCourse($id) {
            
            // Safety check
            if(empty($id)) {
                return ["success" => false, "message" => "Course ID is required."];
            }

            $courseDetails = $this->course->getById($id);
            if ($courseDetails && !empty($courseDetails['ThumbnailUrl'])) {
                $imagePath = '../' . $courseDetails['ThumbnailUrl'];
                if (file_exists($imagePath)) {
                    unlink($imagePath); // Delete the actual file
                }
            }

            // Call the model
            if($this->course->delete($id)) {
                return ["success" => true, "message" => "Course deleted successfully!"];
            } else {
                return ["success" => false, "message" => "Failed to delete course."];
            }
        }

    }
?>