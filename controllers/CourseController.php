<?php
/**
 * Course Controller
 *
 * Manages course-related business logic for both public-facing and admin
 * operations. Handles retrieving published courses, course details, lessons,
 * curriculum data, and provides full CRUD operations for admin course management
 * including thumbnail image uploads.
 *
 * @package    Taliq\Controllers
 * @subpackage Courses
 * @version    1.0.0
 */

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../models/Course.php';
require_once '../includes/functions.php';

class CourseController {
    private $courseModel;

    public function __construct() {
        $this->courseModel = new Course();
    }

    /**
     * Retrieves all published courses with an optional limit.
     *
     * @param int $limit Maximum number of courses to return (0 = no limit).
     *
     * @return array Associative array with success status, records, count, and total.
     */
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

    /**
     * Retrieves a single course by its ID and type.
     *
     * @param int    $courseId   The course ID.
     * @param string $courseType The type: 'course' or 'workshop'.
     *
     * @return array Associative array with success status and course data or error message.
     */
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

    /**
     * Retrieves multiple courses by an array of ID/type pairs.
     *
     * @param array $ids Array of associative arrays with courseId and workshopId keys.
     *
     * @return array Associative array with success status, records, and count.
     */
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

    /**
     * Retrieves all courses for the admin panel (includes unpublished).
     *
     * @return array Associative array with success status, records, and count.
     */
    public function getAllAdmin() {
        $courses = $this->courseModel->getAllForAdmin();
        return ['success' => true, 'records' => $courses, 'count' => count($courses)];
    }

    /**
     * Retrieves a single course for admin editing.
     *
     * @param int    $courseId   The course ID.
     * @param string $courseType The type: 'course' or 'workshop'.
     *
     * @return array Associative array with success status and course data or error message.
     */
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

    /**
     * Updates an existing course with optional thumbnail image upload.
     *
     * @param int    $courseId   The course ID to update.
     * @param string $courseType The type: 'course' or 'workshop'.
     * @param array  $postData  The form data containing course fields.
     * @param array  $fileData  The uploaded file data ($_FILES).
     *
     * @return array Associative array with success status and message.
     */
    public function editCourse($courseId, $courseType, $postData, $fileData) {
        if (empty($courseId) || !in_array($courseType, ['course', 'workshop'])) {
            return ['success' => false, 'message' => 'Invalid parameters'];
        }
        $required = ['Title', 'CategoryId', 'Description', 'Price', 'DurationHours', 'Level', 'Language'];
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

    /**
     * Deletes a course by its ID and type.
     *
     * @param int    $courseId   The course ID to delete.
     * @param string $courseType The type: 'course' or 'workshop'.
     *
     * @return array Associative array with success status and message.
     */
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

    /**
     * Creates a new course with optional thumbnail image upload.
     *
     * @param array $postData The form data containing course fields.
     * @param array $fileData The uploaded file data ($_FILES).
     *
     * @return array Associative array with success status, message, and new course ID.
     */
    public function addCourse($postData, $fileData) {
        $required = ['Title', 'CategoryId', 'Description', 'Price', 'DurationHours', 'Level', 'Language'];
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
        $courseId = $this->courseModel->createCourse($postData, $thumbnailUrl);
        if ($courseId) {
            return ['success' => true, 'message' => 'Course created successfully', 'course_id' => $courseId];
        }
        return ['success' => false, 'message' => 'Failed to create course'];
    }

    /**
     * Retrieves a course along with its associated lessons.
     *
     * @param int    $courseId   The course ID.
     * @param string $courseType The type: 'course' or 'workshop'.
     *
     * @return array Associative array with success status and course data including lessons.
     */
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