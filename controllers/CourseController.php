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
        
        // 1. Check required fields using LOWERCASE names exactly as they are in your HTML
        if(empty($postData['title']) || empty($postData['category_id'])) {
            return ["success" => false, "message" => "Title and Category ID are required."];
        }

        // 2. IMAGE UPLOAD LOGIC
        $thumbnailPath = "";
        
        // Check if an image was uploaded via the "thumbnail" input
        if (isset($fileData['thumbnail']) && $fileData['thumbnail']['error'] === UPLOAD_ERR_OK) {
            
            $uploadDir = '../images/'; 
            
            // Create a unique name so images don't overwrite each other
            $fileName = time() . '_' . basename($fileData['thumbnail']['name']);
            $targetFilePath = $uploadDir . $fileName;
            
            // Move the file into the Taliq/images/ folder
            if (move_uploaded_file($fileData['thumbnail']['tmp_name'], $targetFilePath)) {
                $thumbnailPath = 'images/' . $fileName; 
            } else {
                return ["success" => false, "message" => "Failed to save the image to the folder."];
            }
        } else {
            return ["success" => false, "message" => "Please upload a thumbnail image."];
        }

        // 3. Map the lowercase HTML inputs to the uppercase Database columns
        $courseData = [
            'CategoryId'    => htmlspecialchars(strip_tags($postData['category_id'])),
            'Title'         => htmlspecialchars(strip_tags($postData['title'])),
            'Description'   => htmlspecialchars(strip_tags($postData['description'] ?? '')),
            'Price'         => isset($postData['price']) ? (float)$postData['price'] : 0.00,
            'DurationHours' => isset($postData['duration']) ? (float)$postData['duration'] : null,
            'Level'         => $postData['level'] ?? 'Beginner',
            'Language'      => htmlspecialchars(strip_tags($postData['language'] ?? 'English')),
            'Thumbnail'  => $thumbnailPath, // Saved image path!
            'IsPublished'   => isset($postData['is_published']) ? 1 : 0
        ];

        // 4. Save to the Database
        if($this->course->create($courseData)) {
            return ["success" => true, "message" => "Course and Image created successfully!"];
        } else {
            return ["success" => false, "message" => "Failed to save course to the database."];
        }
    }
}
?>