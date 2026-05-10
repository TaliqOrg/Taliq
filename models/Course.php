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

    public function GetCourseById($CourseId,$CourseType) {
    //  Determine which table to query
        $table = ($CourseType == 'course') ? "Course" : "Workshop";
        $idColumn = ($CourseType == 'course') ? "CourseId" : "WorkshopId";

        $query = "SELECT * FROM $table WHERE $idColumn = ? LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$CourseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


}

?>
