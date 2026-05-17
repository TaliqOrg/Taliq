<?php
/**
 * Course Model
 *
 * Handles all course and workshop database operations. Provides methods for
 * retrieving published listings (courses + workshops via UNION), individual
 * course lookups, admin CRUD operations with thumbnail uploads, and
 * curriculum retrieval with associated lessons.
 *
 * @package    Taliq\Models
 * @subpackage Course
 * @version    1.0.0
 */

class Course {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Retrieves all published courses and workshops, ordered by creation date.
     *
     * @return array Array of published course/workshop records.
     */
    public function getAllPublished() {
        $sql = "SELECT CourseId, Title, Description, Price, ThumbnailUrl, AverageRating, RatingCount, Level, Language, CategoryId, 'course' AS CourseType, CreatedAt
                FROM Course
                WHERE IsPublished = 1
                UNION ALL
                SELECT WorkshopId AS CourseId, Title, Description, Price, ThumbnailUrl, AverageRating, RatingCount, Level, Language, CategoryId, 'workshop' AS CourseType, CreatedAt
                FROM Workshop
                WHERE IsPublished = 1
                ORDER BY CreatedAt DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves a single published course or workshop by ID and type.
     *
     * @param int    $courseId   The course or workshop ID.
     * @param string $courseType The type: 'course' or 'workshop'.
     *
     * @return array|false The course record, or false if not found.
     */
    public function getById($courseId, $courseType) {
        $table = ($courseType === 'course') ? 'Course' : 'Workshop';
        $idColumn = ($courseType === 'course') ? 'CourseId' : 'WorkshopId';
        $sql = "SELECT * FROM {$table} WHERE {$idColumn} = :id AND IsPublished = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $courseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves multiple courses/workshops by an array of ID pairs.
     *
     * @param array $items Array of associative arrays with courseId and/or workshopId keys.
     *
     * @return array Array of matching course/workshop records.
     */
    public function getByIds($items) {
        $courses = [];
        foreach ($items as $item) {
            if (!empty($item['courseId'])) {
                $sql = "SELECT CourseId, Title, ThumbnailUrl, 'course' AS CourseType FROM Course WHERE CourseId = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':id' => $item['courseId']]);
                $course = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($course) $courses[] = $course;
            }
            if (!empty($item['workshopId'])) {
                $sql = "SELECT WorkshopId AS CourseId, Title, ThumbnailUrl, 'workshop' AS CourseType FROM Workshop WHERE WorkshopId = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':id' => $item['workshopId']]);
                $workshop = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($workshop) $courses[] = $workshop;
            }
        }
        return $courses;
    }

    /**
     * Retrieves all courses and workshops for admin management (includes unpublished).
     *
     * @return array Array of all course/workshop records.
     */
    public function getAllForAdmin() {
        $sql = "SELECT CourseId, Title, Price, ThumbnailUrl, IsPublished, 'course' AS CourseType, CategoryId, Level, DurationHours, Language, CreatedAt
                FROM Course
                UNION ALL
                SELECT WorkshopId AS CourseId, Title, Price, ThumbnailUrl, IsPublished, 'workshop' AS CourseType, CategoryId, Level, DurationHours, Language, CreatedAt
                FROM Workshop
                ORDER BY CreatedAt DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves a single course or workshop for admin editing (no publish filter).
     *
     * @param int    $courseId   The course or workshop ID.
     * @param string $courseType The type: 'course' or 'workshop'.
     *
     * @return array|false The course record, or false if not found.
     */
    public function getByIdForAdmin($courseId, $courseType) {
        $table    = ($courseType === 'course') ? 'Course' : 'Workshop';
        $idColumn = ($courseType === 'course') ? 'CourseId' : 'WorkshopId';
        $sql  = "SELECT * FROM {$table} WHERE {$idColumn} = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $courseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Updates an existing course or workshop record.
     *
     * @param int         $courseId     The course or workshop ID.
     * @param string      $courseType   The type: 'course' or 'workshop'.
     * @param array       $data         Associative array of field values.
     * @param string|null $thumbnailUrl The new thumbnail URL, or null to keep existing.
     *
     * @return bool True on success.
     */
    public function updateCourse($courseId, $courseType, $data, $thumbnailUrl) {
        $table    = ($courseType === 'course') ? 'Course' : 'Workshop';
        $idColumn = ($courseType === 'course') ? 'CourseId' : 'WorkshopId';
        $thumbSql = $thumbnailUrl ? ', ThumbnailUrl = :thumbnail_url' : '';

        $sql = "UPDATE {$table}
                SET Title = :title, CategoryId = :category_id, Description = :description, Price = :price, DurationHours = :duration_hours, Level = :level, Language = :language, IsPublished = :is_published {$thumbSql}
                WHERE {$idColumn} = :id";

        $params = [
            ':title'          => $data['Title'],
            ':category_id'    => $data['CategoryId'],
            ':description'    => $data['Description'],
            ':price'          => $data['Price'],
            ':duration_hours' => $data['DurationHours'],
            ':level'          => strtolower($data['Level']),
            ':language'       => $data['Language'],
            ':is_published'   => !empty($data['IsPublished']) ? 1 : 0,
            ':id'             => $courseId,
        ];
        if ($thumbnailUrl) {
            $params[':thumbnail_url'] = $thumbnailUrl;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Deletes a course or workshop by ID and type.
     *
     * @param int    $courseId   The course or workshop ID.
     * @param string $courseType The type: 'course' or 'workshop'.
     *
     * @return bool True on success.
     */
    public function deleteCourse($courseId, $courseType) {
        $table    = ($courseType === 'course') ? 'Course' : 'Workshop';
        $idColumn = ($courseType === 'course') ? 'CourseId' : 'WorkshopId';
        $sql  = "DELETE FROM {$table} WHERE {$idColumn} = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $courseId]);
    }

    /**
     * Creates a new course record in the database.
     *
     * @param array       $data         Associative array of course fields.
     * @param string|null $thumbnailUrl The thumbnail URL for the course.
     *
     * @return int|false The new course ID, or false on failure.
     */
    public function createCourse($data, $thumbnailUrl) {
        $sql = "INSERT INTO Course
                    (CategoryId, Title, Description, Price, DurationHours, Level, Language, ThumbnailUrl, IsPublished)
                VALUES
                    (:category_id, :title, :description, :price, :duration_hours, :level, :language, :thumbnail_url, :is_published)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':category_id'    => $data['CategoryId'],
            ':title'          => $data['Title'],
            ':description'    => $data['Description'],
            ':price'          => $data['Price'],
            ':duration_hours' => $data['DurationHours'],
            ':level'          => strtolower($data['Level']),
            ':language'       => $data['Language'],
            ':thumbnail_url'  => $thumbnailUrl,
            ':is_published'   => !empty($data['IsPublished']) ? 1 : 0,
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Retrieves a course with its associated lessons list.
     *
     * @param int    $courseId   The course ID.
     * @param string $courseType The type: 'course' or 'workshop'.
     *
     * @return array|false The course record with a 'lessons' key, or false if not found.
     */
    public function getByIdWithLessons($courseId, $courseType) {
        
        $course = $this->getById($courseId, $courseType);

        if ($course && $courseType === 'course') {

            $sql = "SELECT LessonId, Title, Description, ContentType, ContentUrl, Duration, SortOrder
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