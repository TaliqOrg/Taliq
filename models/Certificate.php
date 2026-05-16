<?php

class Certificate {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    public function alreadyIssued($userId, $courseId) {
        $sql = "SELECT 1 FROM Certificate WHERE UserId = :user_id AND CourseId = :course_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId, ':course_id' => $courseId]);
        return (bool)$stmt->fetch();
    }

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
