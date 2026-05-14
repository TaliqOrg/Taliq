<?php

class Lesson {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

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

    public function getLessonProgress($userId, $lessonId) {
        $sql = "SELECT * FROM LessonProgress 
                WHERE UserId = :user_id AND LessonId = :lesson_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':lesson_id' => $lessonId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markAsComplete($userId, $lessonId, $courseId) {
        try {
            // Check if already completed to prevent duplicate points
            $existing = $this->getLessonProgress($userId, $lessonId);
            if ($existing && $existing['IsCompleted']) {
                return true; // Already completed
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
            
            // Award 50 points directly to User table
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
}
