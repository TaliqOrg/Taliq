<?php
/**
 * Workshop Model
 *
 * Handles workshop session retrieval. Provides scheduled session data
 * including dates, times, locations, and available seat counts.
 *
 * @package    Taliq\Models
 * @subpackage Workshop
 * @version    1.0.0
 */

class Workshop {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Retrieves all scheduled sessions for a specific workshop.
     *
     * @param int $workshopId The workshop ID.
     *
     * @return array Array of session records ordered by date and time.
     */
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
