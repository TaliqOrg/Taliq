<?php
/**
 * Points Model
 *
 * Manages the gamification points system. Handles point retrieval, awarding
 * points via stored procedures, reading points configuration, transaction
 * history, purchase-based point awards, and user points initialization.
 *
 * @package    Taliq\Models
 * @subpackage Points
 * @version    1.0.0
 */

class Points {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Retrieves a user's current and lifetime point totals.
     *
     * @param int $userId The user's ID.
     *
     * @return array Associative array with TotalPoints and LifetimePoints.
     */
    public function getUserPoints($userId) {
        $sql = "SELECT * FROM UserPoints WHERE UserId = :user_id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return [
                'TotalPoints' => 0,
                'LifetimePoints' => 0
            ];
        }
        
        return $result;
    }

    /**
     * Awards points to a user via the AwardPoints stored procedure.
     *
     * @param int         $userId          The user's ID.
     * @param int         $points          The number of points to award.
     * @param string      $transactionType The transaction type (e.g., 'earned').
     * @param string      $source          The source action (e.g., 'purchase').
     * @param int|null    $sourceId        The source record ID (optional).
     * @param string      $description     A description of the award (optional).
     *
     * @return bool True on success, false on failure.
     */
    public function awardPoints($userId, $points, $transactionType, $source, $sourceId = null, $description = '') {
        try {
            $sql = "CALL AwardPoints(:user_id, :points, :transaction_type, :source, :source_id, :description)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':user_id' => $userId,
                ':points' => $points,
                ':transaction_type' => $transactionType,
                ':source' => $source,
                ':source_id' => $sourceId,
                ':description' => $description
            ]);
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error awarding points: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves the points configuration for a specific action type.
     *
     * @param string $actionType The action type identifier.
     *
     * @return array|false The configuration record, or false if not found.
     */
    public function getPointsConfig($actionType) {
        $sql = "SELECT * FROM PointsConfig WHERE ActionType = :action_type AND IsActive = TRUE LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':action_type' => $actionType]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves a user's points transaction history with pagination.
     *
     * @param int $userId The user's ID.
     * @param int $limit  Maximum number of records to return (default 50).
     * @param int $offset The offset for pagination (default 0).
     *
     * @return array Array of transaction records.
     */
    public function getUserTransactions($userId, $limit = 50, $offset = 0) {
        $sql = "SELECT * FROM PointsTransaction 
                WHERE UserId = :user_id 
                ORDER BY CreatedAt DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves the most recent transaction for a specific source and source ID.
     *
     * @param int    $userId   The user's ID.
     * @param string $source   The source action.
     * @param int    $sourceId The source record ID.
     *
     * @return array|false The transaction record, or false if not found.
     */
    public function getRecentTransaction($userId, $source, $sourceId) {
        $sql = "SELECT * FROM PointsTransaction 
                WHERE UserId = :user_id 
                AND Source = :source 
                AND SourceId = :source_id 
                ORDER BY CreatedAt DESC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':source' => $source,
            ':source_id' => $sourceId
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Awards points for a purchase based on the configured amount for the item type.
     *
     * @param int    $userId   The user's ID.
     * @param int    $orderId  The order ID.
     * @param string $itemType The purchased item type ('course' or 'workshop').
     *
     * @return bool True on success, false on failure or if no points configured.
     */
    public function awardPurchasePoints($userId, $orderId, $itemType) {
        try {
            $actionType = $itemType === 'course' ? 'purchase_course' : 'purchase_workshop';
            
            $config = $this->getPointsConfig($actionType);
            
            if (!$config || $config['PointsAwarded'] <= 0) {
                return false;
            }
            
            $description = $itemType === 'course' 
                ? "Purchase completed - Course" 
                : "Purchase completed - Workshop";
            
            return $this->awardPoints(
                $userId,
                $config['PointsAwarded'],
                'earned',
                'purchase',
                $orderId,
                $description
            );
        } catch (Exception $e) {
            error_log("Error awarding purchase points: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Initializes a points record for a new user with zero balances.
     *
     * @param int $userId The user's ID.
     *
     * @return bool True on success, false on failure.
     */
    public function initializeUserPoints($userId) {
        try {
            $sql = "INSERT INTO UserPoints (UserId, TotalPoints, LifetimePoints) 
                    VALUES (:user_id, 0, 0)
                    ON DUPLICATE KEY UPDATE UserId = UserId";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("Error initializing user points: " . $e->getMessage());
            return false;
        }
    }
}
