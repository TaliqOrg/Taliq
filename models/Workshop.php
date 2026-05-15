<?php

class Workshop {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    public function getSessions($workshopId) {
        $sql = "SELECT SessionId, SessionDate, StartTime, EndTime, Location, AvailableSeats
                FROM WorkshopSession
                WHERE WorkshopId = :workshop_id
                ORDER BY SessionDate ASC, StartTime ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':workshop_id' => $workshopId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
