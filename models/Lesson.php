<?php
/**
 * Lesson Model
 *
 * Handles lesson data retrieval, progress tracking, and CRUD operations.
 * Supports fetching lessons by course with optional user progress joins,
 * navigation between lessons, marking completions with point awards,
 * watch time tracking, course-level progress calculation, section grouping,
 * and admin lesson management (create, update, delete, reorder).
 *
 * @package    Taliq\Models
 * @subpackage Lesson
 * @version    1.0.0
 */

class Lesson {

    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Retrieves all lessons for a course, optionally joined with user progress.
     *
     * @param int      $courseId The course ID.
     * @param int|null $userId  The user's ID for progress data (optional).
     *
     * @return array Array of lesson records ordered by SortOrder.
     */
    public function getLessonsByCourse($courseId, $userId = null) {
        $sql = "SELECT 
                    l.LessonId,
                    l.CourseId,
                    l.SectionTitle,
                    l.Title,
                    l.Description,
                    l.ContentType,
                    l.ContentUrl,
                    l.SortOrder,
                    l.Duration";
        
        if ($userId) {
            $sql .= ",
                    lp.IsCompleted,
                    lp.CompletedAt,
                    lp.LastAccessedAt,
                    lp.WatchTimeSeconds";
        }
        
        $sql .= " FROM Lesson l";
        
        if ($userId) {
            $sql .= " LEFT JOIN LessonProgress lp 
                     ON l.LessonId = lp.LessonId AND lp.UserId = :user_id";
        }
        
        $sql .= " WHERE l.CourseId = :course_id
                 ORDER BY l.SortOrder ASC";
        
        $stmt = $this->db->prepare($sql);
        $params = [':course_id' => $courseId];
        
        if ($userId) {
            $params[':user_id'] = $userId;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves a single lesson by ID with its parent course title.
     *
     * @param int      $lessonId The lesson ID.
     * @param int|null $userId   The user's ID for progress data (optional).
     *
     * @return array|false The lesson record, or false if not found.
     */
    public function getLessonById($lessonId, $userId = null) {
        $sql = "SELECT 
                    l.LessonId,
                    l.CourseId,
                    l.SectionTitle,
                    l.Title,
                    l.Description,
                    l.ContentType,
                    l.ContentUrl,
                    l.SortOrder,
                    l.Duration,
                    c.Title AS CourseTitle";
        
        if ($userId) {
            $sql .= ",
                    lp.IsCompleted,
                    lp.CompletedAt,
                    lp.LastAccessedAt,
                    lp.WatchTimeSeconds";
        }
        
        $sql .= " FROM Lesson l
                 INNER JOIN Course c ON l.CourseId = c.CourseId";
        
        if ($userId) {
            $sql .= " LEFT JOIN LessonProgress lp 
                     ON l.LessonId = lp.LessonId AND lp.UserId = :user_id";
        }
        
        $sql .= " WHERE l.LessonId = :lesson_id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $params = [':lesson_id' => $lessonId];
        
        if ($userId) {
            $params[':user_id'] = $userId;
        }
        
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves the next lesson in sort order within a course.
     *
     * @param int $currentLessonId The current lesson ID.
     * @param int $courseId        The course ID.
     *
     * @return array|false The next lesson record, or false if none.
     */
    public function getNextLesson($currentLessonId, $courseId) {
        $sql = "SELECT l1.LessonId, l1.Title
                FROM Lesson l1
                INNER JOIN Lesson l2 ON l1.CourseId = l2.CourseId
                WHERE l2.LessonId = :current_lesson_id
                AND l1.CourseId = :course_id
                AND l1.SortOrder > l2.SortOrder
                ORDER BY l1.SortOrder ASC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':current_lesson_id' => $currentLessonId,
            ':course_id' => $courseId
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves the previous lesson in sort order within a course.
     *
     * @param int $currentLessonId The current lesson ID.
     * @param int $courseId        The course ID.
     *
     * @return array|false The previous lesson record, or false if none.
     */
    public function getPreviousLesson($currentLessonId, $courseId) {
        $sql = "SELECT l1.LessonId, l1.Title
                FROM Lesson l1
                INNER JOIN Lesson l2 ON l1.CourseId = l2.CourseId
                WHERE l2.LessonId = :current_lesson_id
                AND l1.CourseId = :course_id
                AND l1.SortOrder < l2.SortOrder
                ORDER BY l1.SortOrder DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':current_lesson_id' => $currentLessonId,
            ':course_id' => $courseId
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves the progress record for a specific user and lesson.
     *
     * @param int $userId   The user's ID.
     * @param int $lessonId The lesson ID.
     *
     * @return array|false The progress record, or false if not found.
     */
    public function getLessonProgress($userId, $lessonId) {
        $sql = "SELECT * FROM LessonProgress 
                WHERE UserId = :user_id AND LessonId = :lesson_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':lesson_id' => $lessonId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Marks a lesson as complete and awards 50 points if not already completed.
     *
     * Uses INSERT ... ON DUPLICATE KEY UPDATE for idempotent completion tracking.
     *
     * @param int $userId   The user's ID.
     * @param int $lessonId The lesson ID.
     * @param int $courseId The course ID.
     *
     * @return bool True on success, false on failure.
     */
    public function markAsComplete($userId, $lessonId, $courseId) {
        try {
            $existing = $this->getLessonProgress($userId, $lessonId);
            if ($existing && $existing['IsCompleted']) {
                return true;
            }
            
            $sql = "INSERT INTO LessonProgress 
                    (UserId, LessonId, CourseId, IsCompleted, CompletedAt, LastAccessedAt)
                    VALUES (:user_id, :lesson_id, :course_id, TRUE, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE 
                    IsCompleted = TRUE,
                    CompletedAt = NOW(),
                    LastAccessedAt = NOW()";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':user_id' => $userId,
                ':lesson_id' => $lessonId,
                ':course_id' => $courseId
            ]);
            
            if ($result && (!$existing || !$existing['IsCompleted'])) {
                $sql = "UPDATE User SET Points = Points + 50 WHERE UserId = :user_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':user_id' => $userId]);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error marking lesson as complete: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates the watch time for a lesson progress record.
     *
     * @param int $userId           The user's ID.
     * @param int $lessonId         The lesson ID.
     * @param int $courseId         The course ID.
     * @param int $watchTimeSeconds The total watch time in seconds.
     *
     * @return bool True on success, false on failure.
     */
    public function updateWatchTime($userId, $lessonId, $courseId, $watchTimeSeconds) {
        try {
            $sql = "INSERT INTO LessonProgress 
                    (UserId, LessonId, CourseId, WatchTimeSeconds, LastAccessedAt)
                    VALUES (:user_id, :lesson_id, :course_id, :watch_time, NOW())
                    ON DUPLICATE KEY UPDATE 
                    WatchTimeSeconds = :watch_time,
                    LastAccessedAt = NOW()";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':user_id' => $userId,
                ':lesson_id' => $lessonId,
                ':course_id' => $courseId,
                ':watch_time' => $watchTimeSeconds
            ]);
        } catch (PDOException $e) {
            error_log("Error updating watch time: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculates overall course progress based on completed lessons.
     *
     * @param int $userId   The user's ID.
     * @param int $courseId The course ID.
     *
     * @return array Associative array with total_lessons, completed_lessons, and progress_percentage.
     */
    public function getCourseProgress($userId, $courseId) {
        $sql = "SELECT 
                    COUNT(*) as total_lessons,
                    SUM(CASE WHEN lp.IsCompleted = TRUE THEN 1 ELSE 0 END) as completed_lessons,
                    ROUND((SUM(CASE WHEN lp.IsCompleted = TRUE THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as progress_percentage
                FROM Lesson l
                LEFT JOIN LessonProgress lp ON l.LessonId = lp.LessonId AND lp.UserId = :user_id
                WHERE l.CourseId = :course_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':course_id' => $courseId
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves lessons grouped by their SectionTitle.
     *
     * @param int      $courseId The course ID.
     * @param int|null $userId  The user's ID for progress data (optional).
     *
     * @return array Array of section groups, each with section_title and lessons.
     */
    public function getLessonsBySectionGrouped($courseId, $userId = null) {
        $lessons = $this->getLessonsByCourse($courseId, $userId);
        
        $grouped = [];
        foreach ($lessons as $lesson) {
            $section = $lesson['SectionTitle'] ?: 'General';
            if (!isset($grouped[$section])) {
                $grouped[$section] = [
                    'section_title' => $section,
                    'lessons' => []
                ];
            }
            $grouped[$section]['lessons'][] = $lesson;
        }
        
        return array_values($grouped);
    }

    /**
     * Creates a new lesson record.
     *
     * @param array $data Associative array with course_id, SectionTitle, Title, Description,
     *                     ContentType, ContentUrl, Duration, and SortOrder.
     *
     * @return bool True on success.
     */
    public function createLesson($data) {

        $sql = "INSERT INTO Lesson (CourseId, SectionTitle, Title, Description, ContentType, ContentUrl, Duration, SortOrder)
                VALUES (:course_id, :section_title, :title, :description, :content_type, :content_url, :duration, :sort_order)";
        
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':course_id'     => $data['course_id'],
            ':section_title' => $data['SectionTitle'] ?? 'General',
            ':title'         => $data['Title'],
            ':description'   => $data['Description'],
            ':content_type'  => strtolower($data['ContentType']),
            ':content_url'   => $data['ContentUrl'],
            ':duration'      => $data['Duration'] ?? 0,
            ':sort_order'    => $data['SortOrder'] ?? 0
        ]);
    }

    /**
     * Deletes a lesson by its ID.
     *
     * @param int $lessonId The lesson ID to delete.
     *
     * @return bool True on success.
     */
    public function deleteLesson($lessonId) {

        $sql = "DELETE FROM Lesson WHERE LessonId = :lesson_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':lesson_id' => $lessonId]);

    }

    /**
     * Updates an existing lesson record.
     *
     * @param array $data Associative array with LessonId and updated fields.
     *
     * @return bool True on success.
     */
    public function updateLesson($data) {
        $sql = "UPDATE Lesson 
                SET SectionTitle = :section_title, Title = :title, Description = :description, 
                    ContentType = :content_type, ContentUrl = :content_url, Duration = :duration, SortOrder = :sort_order
                WHERE LessonId = :lesson_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':lesson_id'     => $data['LessonId'],
            ':section_title' => $data['SectionTitle'] ?? 'General',
            ':title'         => $data['Title'],
            ':description'   => $data['Description'],
            ':content_type'  => strtolower($data['ContentType']),
            ':content_url'   => $data['ContentUrl'],
            ':duration'      => $data['Duration'] ?? 0,
            ':sort_order'    => $data['SortOrder'] ?? 0
        ]);
    }

    /**
     * Batch-updates lesson sort orders within a transaction.
     *
     * @param array $orders Array of associative arrays with lesson_id and sort_order.
     *
     * @return bool True on success, false on failure (rolls back).
     */
    public function updateLessonOrders($orders) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE Lesson SET SortOrder = :sort_order WHERE LessonId = :lesson_id");
            foreach ($orders as $order) {
                $stmt->execute([
                    ':sort_order' => $order['sort_order'],
                    ':lesson_id'  => $order['lesson_id']
                ]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

}
