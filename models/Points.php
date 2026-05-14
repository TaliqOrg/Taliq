<?php

class Points {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

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

    public function getPointsConfig($actionType) {
        $sql = "SELECT * FROM PointsConfig WHERE ActionType = :action_type AND IsActive = TRUE LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':action_type' => $actionType]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

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
