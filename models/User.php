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
}
