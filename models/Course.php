<?php
class Course {
    private $pdo;

    public function __construct($db) {
        $this->pdo = $db;
    }

    public function GetAllPublishedCourses() {
        $query = "SELECT CourseId, Title, Description, Price, ThumbnailUrl, 'course' AS CourseType, CreatedAt FROM Course WHERE IsPublished = 1 UNION ALL SELECT WorkshopId, Title, Description, Price, NULL AS ThumbnailUrl, 'workshop' AS CourseType, CreatedAt FROM Workshop WHERE IsPublished = 1 ORDER BY CreatedAt DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt;
    }


}

?>
