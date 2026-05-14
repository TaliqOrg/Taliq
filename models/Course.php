<?php

class Course {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    public function getAllPublished() {
        $sql = "SELECT 
                    CourseId, 
                    Title, 
                    Description, 
                    Price, 
                    ThumbnailUrl, 
                    AverageRating,
                    RatingCount,
                    'course' AS CourseType, 
                    CreatedAt 
                FROM Course 
                WHERE IsPublished = 1 
                UNION ALL 
                SELECT 
                    WorkshopId AS CourseId, 
                    Title, 
                    Description, 
                    Price, 
                    ThumbnailUrl, 
                    AverageRating,
                    RatingCount,
                    'workshop' AS CourseType, 
                    CreatedAt 
                FROM Workshop 
                WHERE IsPublished = 1 
                ORDER BY CreatedAt DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($courseId, $courseType) {
        $table = ($courseType === 'course') ? 'Course' : 'Workshop';
        $idColumn = ($courseType === 'course') ? 'CourseId' : 'WorkshopId';

        $sql = "SELECT * FROM {$table} WHERE {$idColumn} = :id AND IsPublished = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $courseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByIds($items) {
        // Items format: [{courseId: X, workshopId: Y}, ...]
        $courses = [];
        
        foreach ($items as $item) {
            if (!empty($item['courseId'])) {
                $sql = "SELECT CourseId, Title, ThumbnailUrl, 'course' AS CourseType 
                        FROM Course WHERE CourseId = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':id' => $item['courseId']]);
                $course = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($course) $courses[] = $course;
            }
            if (!empty($item['workshopId'])) {
                $sql = "SELECT WorkshopId AS CourseId, Title, ThumbnailUrl, 'workshop' AS CourseType 
                        FROM Workshop WHERE WorkshopId = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':id' => $item['workshopId']]);
                $workshop = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($workshop) $courses[] = $workshop;
            }
        }
        
        return $courses;
    }

    public function getByIdWithLessons($courseId, $courseType) {
        $course = $this->getById($courseId, $courseType);
        
        if ($course && $courseType === 'course') {
            $sql = "SELECT LessonId, Title, Duration, SortOrder 
                    FROM Lesson 
                    WHERE CourseId = :course_id 
                    ORDER BY SortOrder ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':course_id' => $courseId]);
            $course['lessons'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $course;
    }
}

