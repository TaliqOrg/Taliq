<?php
/**
 * Lesson Model (Version 2)
 *
 * Simplified lesson and progress management model with section grouping,
 * navigation, progress tracking, and course-level completion calculation.
 *
 * @package    Taliq\Models
 * @subpackage Lesson
 * @version    2.0.0
 */

require_once __DIR__ . '/../config/database.php';

class Lesson {
    /** @var PDO */
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /** @return array */
    public function getLessonsByCourse($courseId) {
        $sql = "SELECT LessonId, CourseId, SectionTitle, Title, Description, 
                       ContentType, ContentUrl, Duration, SortOrder
                FROM Lesson 
                WHERE CourseId = :course_id 
                ORDER BY SortOrder ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':course_id' => $courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array|false */
    public function getLessonById($lessonId) {
        $sql = "SELECT l.*, c.Title as CourseTitle 
                FROM Lesson l
                JOIN Course c ON l.CourseId = c.CourseId
                WHERE l.LessonId = :lesson_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':lesson_id' => $lessonId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** @return array|false */
    public function getNextLesson($courseId, $currentSortOrder) {
        $sql = "SELECT LessonId, Title, SortOrder 
                FROM Lesson 
                WHERE CourseId = :course_id AND SortOrder > :sort_order 
                ORDER BY SortOrder ASC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':course_id' => $courseId, ':sort_order' => $currentSortOrder]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** @return array|false */
    public function getPreviousLesson($courseId, $currentSortOrder) {
        $sql = "SELECT LessonId, Title, SortOrder 
                FROM Lesson 
                WHERE CourseId = :course_id AND SortOrder < :sort_order 
                ORDER BY SortOrder DESC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':course_id' => $courseId, ':sort_order' => $currentSortOrder]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** @return array */
    public function getLessonsBySectionGrouped($courseId) {
        $lessons = $this->getLessonsByCourse($courseId);
        
        $sections = [];
        foreach ($lessons as $lesson) {
            $sectionTitle = $lesson['SectionTitle'] ?: 'General';
            
            if (!isset($sections[$sectionTitle])) {
                $sections[$sectionTitle] = [
                    'section_title' => $sectionTitle,
                    'lessons' => []
                ];
            }
            
            $sections[$sectionTitle]['lessons'][] = $lesson;
        }
        
        return array_values($sections);
    }

    /** @return array|false */
    public function getLessonProgress($userId, $lessonId) {
        $sql = "SELECT * FROM LessonProgress 
                WHERE UserId = :user_id AND LessonId = :lesson_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':lesson_id' => $lessonId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** @return bool */
    public function markAsComplete($userId, $lessonId, $courseId) {
        $existing = $this->getLessonProgress($userId, $lessonId);
        
        if ($existing && $existing['IsCompleted']) {
            return true;
        }
        
        if ($existing) {
            // Update existing record
            $sql = "UPDATE LessonProgress 
                    SET IsCompleted = TRUE, 
                        CompletedAt = NOW()
                    WHERE UserId = :user_id AND LessonId = :lesson_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([':user_id' => $userId, ':lesson_id' => $lessonId]);
        } else {
            // Insert new record
            $sql = "INSERT INTO LessonProgress (UserId, LessonId, CourseId, IsCompleted, CompletedAt)
                    VALUES (:user_id, :lesson_id, :course_id, TRUE, NOW())";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':user_id' => $userId, 
                ':lesson_id' => $lessonId, 
                ':course_id' => $courseId
            ]);
        }
        return $result;
    }

    /** @return bool */
    public function updateWatchTime($userId, $lessonId, $courseId, $seconds) {
        $existing = $this->getLessonProgress($userId, $lessonId);
        
        if ($existing) {
            $sql = "UPDATE LessonProgress 
                    SET WatchTimeSeconds = :seconds 
                    WHERE UserId = :user_id AND LessonId = :lesson_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':seconds' => $seconds, 
                ':user_id' => $userId, 
                ':lesson_id' => $lessonId
            ]);
        } else {
            $sql = "INSERT INTO LessonProgress (UserId, LessonId, CourseId, WatchTimeSeconds)
                    VALUES (:user_id, :lesson_id, :course_id, :seconds)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':user_id' => $userId, 
                ':lesson_id' => $lessonId, 
                ':course_id' => $courseId,
                ':seconds' => $seconds
            ]);
        }
    }

    /** @return array */
    public function getCourseProgress($userId, $courseId) {
        $sql = "SELECT COUNT(*) FROM Lesson WHERE CourseId = :course_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':course_id' => $courseId]);
        $totalLessons = (int)$stmt->fetchColumn();

        // Get completed lessons
        $sql = "SELECT COUNT(*) FROM LessonProgress 
                WHERE UserId = :user_id AND CourseId = :course_id AND IsCompleted = TRUE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':course_id' => $courseId]);
        $completedLessons = (int)$stmt->fetchColumn();

        // Calculate percentage
        $progressPercentage = $totalLessons > 0 
            ? round(($completedLessons / $totalLessons) * 100, 2) 
            : 0;

        return [
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'progress_percentage' => $progressPercentage,
            'is_completed' => $progressPercentage >= 100
        ];
    }

    public function getLessonsWithProgress($userId, $courseId) {
        $sql = "SELECT 
                    l.LessonId,
                    l.SectionTitle,
                    l.Title,
                    l.Description,
                    l.ContentType,
                    l.ContentUrl,
                    l.Duration,
                    l.SortOrder,
                    COALESCE(lp.IsCompleted, FALSE) as IsCompleted,
                    lp.CompletedAt,
                    lp.WatchTimeSeconds
                FROM Lesson l
                LEFT JOIN LessonProgress lp ON l.LessonId = lp.LessonId AND lp.UserId = :user_id
                WHERE l.CourseId = :course_id
                ORDER BY l.SortOrder ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':course_id' => $courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array */
    public function getLessonsWithProgressGrouped($userId, $courseId) {
        $lessons = $this->getLessonsWithProgress($userId, $courseId);
        
        $sections = [];
        foreach ($lessons as $lesson) {
            $sectionTitle = $lesson['SectionTitle'] ?: 'General';
            
            if (!isset($sections[$sectionTitle])) {
                $sections[$sectionTitle] = [
                    'section_title' => $sectionTitle,
                    'lessons' => []
                ];
            }
            
            $sections[$sectionTitle]['lessons'][] = $lesson;
        }
        
        return array_values($sections);
    }
}
