<?php
/**
 * User Model
 * 
 * Handles all user-related database operations
 */

class User {
    private $db;
    private $table = 'User';
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }
    
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE Email = :email AND IsActive = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }
    
    public function verifyPassword($email, $password) {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['PasswordHash'])) {
            unset($user['PasswordHash']);
            return $user;
        }
        return false;
    }
    
    public function create($firstName, $lastName, $email, $password, $phoneNumber = null, $role = 'user') {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO {$this->table} (FirstName, LastName, Email, PasswordHash, PhoneNumber, Role) 
                VALUES (:first_name, :last_name, :email, :password_hash, :phone_number, :role)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':phone_number' => $phoneNumber,
            ':role' => $role
        ]);
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function emailExists($email) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE Email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() > 0;
    }
    
    public function findById($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE UserId = :user_id AND IsActive = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch();
        if ($user) {
            unset($user['PasswordHash']);
        }
        return $user;
    }
    
    public function updateProfile($userId, $firstName, $lastName, $email, $phoneNumber = null) {
        $sql = "UPDATE {$this->table} 
                SET FirstName = :first_name, 
                    LastName = :last_name, 
                    Email = :email, 
                    PhoneNumber = :phone_number,
                    UpdatedAt = CURRENT_TIMESTAMP
                WHERE UserId = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email,
            ':phone_number' => $phoneNumber,
            ':user_id' => $userId
        ]);
    }
    
    public function updatePassword($userId, $newPassword) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE {$this->table} 
                SET PasswordHash = :password_hash,
                    UpdatedAt = CURRENT_TIMESTAMP
                WHERE UserId = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':password_hash' => $passwordHash,
            ':user_id' => $userId
        ]);
    }
    
    public function verifyCurrentPassword($userId, $password) {
        $sql = "SELECT PasswordHash FROM {$this->table} WHERE UserId = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['PasswordHash'])) {
            return true;
        }
        return false;
    }
    
    public function emailExistsForOtherUser($email, $userId) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE Email = :email AND UserId != :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':user_id' => $userId
        ]);
        return $stmt->fetchColumn() > 0;
    }
}
