<?php

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../models/Course.php';
require_once '../includes/functions.php';

class CourseController {
    private $courseModel;

    public function __construct() {
        $this->courseModel = new Course();
    }

    public function getAll($limit = 0) {
        $courses = $this->courseModel->getAllPublished();
        $total = count($courses);
        if ($limit > 0) {
            $courses = array_slice($courses, 0, $limit);
        }
        return [
            'success' => true,
            'records' => $courses,
            'count'   => count($courses),
            'total'   => $total
        ];
    }

    public function getById($courseId, $courseType) {
        if (empty($courseId) || empty($courseType)) {
            return ['success' => false, 'message' => 'Course ID and type are required'];
        }
        if (!in_array($courseType, ['course', 'workshop'])) {
            return ['success' => false, 'message' => 'Invalid course type'];
        }
        $course = $this->courseModel->getById($courseId, $courseType);
        if ($course) {
            $course['Description'] = html_entity_decode($course['Description']);
            return ['success' => true, 'course'  => $course];
        }
        return ['success' => false, 'message' => 'Course not found'];
    }

    public function getByIds($ids) {
        if (empty($ids)) {
            return ['success' => false, 'message' => 'No IDs provided'];
        }
        $courses = $this->courseModel->getByIds($ids);
        return [
            'success' => true,
            'records' => $courses,
            'count' => count($courses)
        ];
    }

    public function getAllAdmin() {
        $courses = $this->courseModel->getAllForAdmin();
        return ['success' => true, 'records' => $courses, 'count' => count($courses)];
    }

    public function getCourseForEdit($courseId, $courseType) {
        if (empty($courseId) || !in_array($courseType, ['course', 'workshop'])) {
            return ['success' => false, 'message' => 'Invalid parameters'];
        }
        $course = $this->courseModel->getByIdForAdmin($courseId, $courseType);
        if ($course) {
            $course['Description'] = html_entity_decode($course['Description']);
            return ['success' => true, 'course' => $course];
        }
        return ['success' => false, 'message' => 'Course not found'];
    }

    public function editCourse($courseId, $courseType, $postData, $fileData) {
        if (empty($courseId) || !in_array($courseType, ['course', 'workshop'])) {
            return ['success' => false, 'message' => 'Invalid parameters'];
        }
        $required = ['Title', 'CategoryId', 'Description', 'Price', 'DurationHours', 'Level', 'Language'];
        if ($courseType === 'course') {
            $required[] = 'MaxStudents';
        }
        foreach ($required as $field) {
            if (empty($postData[$field])) {
                return ['success' => false, 'message' => "$field is required"];
            }
        }
        $thumbnailUrl = null;
        if (!empty($fileData['ThumbnailImage']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext     = strtolower(pathinfo($fileData['ThumbnailImage']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                return ['success' => false, 'message' => 'Invalid image type'];
            }
            $uploadDir = __DIR__ . '/../uploads/';
            $filename  = uniqid('course_') . '.' . $ext;
            if (!move_uploaded_file($fileData['ThumbnailImage']['tmp_name'], $uploadDir . $filename)) {
                return ['success' => false, 'message' => 'Failed to upload image'];
            }
            $thumbnailUrl = '/Taliq/uploads/' . $filename;
        }
        $success = $this->courseModel->updateCourse($courseId, $courseType, $postData, $thumbnailUrl);
        if ($success) {
            return ['success' => true, 'message' => 'Course updated successfully'];
        }
        return ['success' => false, 'message' => 'Failed to update course'];
    }

    public function removeCourse($courseId, $courseType) {
        if (empty($courseId) || !in_array($courseType, ['course', 'workshop'])) {
            return ['success' => false, 'message' => 'Invalid parameters'];
        }
        $success = $this->courseModel->deleteCourse($courseId, $courseType);
        if ($success) {
            return ['success' => true, 'message' => 'Course deleted successfully'];
        }
        return ['success' => false, 'message' => 'Failed to delete course'];
    }

    public function addCourse($postData, $fileData) {
        $courseType = $postData['CourseType'] ?? 'course';
        $required = ['Title', 'CategoryId', 'Description', 'Price', 'DurationHours', 'Level', 'Language', 'MaxStudents'];
        if ($courseType === 'workshop') {
            $required[] = 'Location';
        }
        foreach ($required as $field) {
            if (empty($postData[$field])) {
                return ['success' => false, 'message' => "$field is required"];
            }
        }
        $thumbnailUrl = null;
        if (!empty($fileData['ThumbnailImage']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($fileData['ThumbnailImage']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                return ['success' => false, 'message' => 'Invalid image type. Allowed: jpg, jpeg, png, gif, webp'];
            }
            $uploadDir = __DIR__ . '/../uploads/';
            $filename  = uniqid('course_') . '.' . $ext;
            if (!move_uploaded_file($fileData['ThumbnailImage']['tmp_name'], $uploadDir . $filename)) {
                return ['success' => false, 'message' => 'Failed to upload image'];
            }
            $thumbnailUrl = '/Taliq/uploads/' . $filename;
        }
        if ($courseType === 'workshop') {
            $newId = $this->courseModel->createWorkshop($postData, $thumbnailUrl);
        } else {
            $newId = $this->courseModel->createCourse($postData, $thumbnailUrl);
        }
        if ($newId) {
            return ['success' => true, 'message' => ucfirst($courseType) . ' created successfully', 'course_id' => $newId];
        }
        return ['success' => false, 'message' => 'Failed to create ' . $courseType];
    }

    public function getWithLessons($courseId, $courseType) {
        if (empty($courseId) || empty($courseType)) {
            return ['success' => false, 'message' => 'Course ID and type are required'];
        }
        if (!in_array($courseType, ['course', 'workshop'])) {
            return ['success' => false, 'message' => 'Invalid course type'];
        }
        $course = $this->courseModel->getByIdWithLessons($courseId, $courseType);
        if ($course) {
            $course['Description'] = html_entity_decode($course['Description']);
            return ['success' => true, 'course'  => $course];
        }
        return ['success' => false, 'message' => 'Course not found'];
    }
}