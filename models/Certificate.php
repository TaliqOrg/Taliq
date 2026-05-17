<?php
/**
 * Certificate Model
 *
 * Handles certificate issuance and retrieval. Supports checking for duplicate
 * certificates, issuing new certificates with unique codes, and fetching
 * certificate details with associated course/workshop and user information.
 *
 * @package    Taliq\Models
 * @subpackage Certificate
 * @version    1.0.0
 */

class Certificate {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Checks if a certificate has already been issued for a user and course.
     *
     * @param int $userId   The user's ID.
     * @param int $courseId The course ID.
     *
     * @return bool True if a certificate already exists.
     */
    public function alreadyIssued($userId, $courseId) {
        $sql = "SELECT 1 FROM Certificate WHERE UserId = :user_id AND CourseId = :course_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':course_id' => $courseId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Issues a new certificate for a user upon course completion.
     *
     * Generates a unique certificate code in the format TLQ-YYYY-XXXXXXXXXX.
     *
     * @param int $userId   The user's ID.
     * @param int $courseId The course ID.
     *
     * @return array|false Associative array with CertificateId and CertificateCode, or false on failure.
     */
    public function issue($userId, $courseId) {
        $stmt = $this->db->prepare("SELECT Title FROM Course WHERE CourseId = :id LIMIT 1");
        $stmt->execute([':id' => $courseId]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$course) return false;

        $code = 'TLQ-' . date('Y') . '-' . strtoupper(substr(md5($userId . $courseId . microtime()), 0, 10));

        $sql = "INSERT INTO Certificate (UserId, CourseId, CertificateCode, IssueDate)
                VALUES (:user_id, :course_id, :code, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id'   => $userId,
            ':course_id' => $courseId,
            ':code'      => $code
        ]);

        return [
            'CertificateId'   => (int)$this->db->lastInsertId(),
            'CertificateCode' => $code
        ];
    }

    /**
     * Retrieves a certificate's full details by its ID and owner.
     *
     * @param int $certId The certificate ID.
     * @param int $userId The user's ID (for ownership verification).
     *
     * @return array|false Associative array with certificate data, or false if not found.
     */
    public function getById($certId, $userId) {
        $sql = "SELECT
                    cert.CertificateId,
                    cert.CertificateCode,
                    cert.IssueDate,
                    COALESCE(c.Title, w.Title) AS CourseTitle,
                    u.FirstName,
                    u.LastName
                FROM Certificate cert
                LEFT JOIN Course   c ON cert.CourseId   = c.CourseId
                LEFT JOIN Workshop w ON cert.WorkshopId = w.WorkshopId
                JOIN  User         u ON cert.UserId     = u.UserId
                WHERE cert.CertificateId = :cert_id AND cert.UserId = :user_id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cert_id' => $certId, ':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
