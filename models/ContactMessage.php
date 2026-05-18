<?php
/*
 * Task 11: Contact Us Model
 * Author:  Fadhlallah Almohammed
 */

class ContactMessage {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    public function save($name, $email, $subject, $message) {
        $sql = "INSERT INTO ContactMessage (Name, Email, Subject, Message)
                VALUES (:name, :email, :subject, :message)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':name'    => $name,
            ':email'   => $email,
            ':subject' => $subject,
            ':message' => $message
        ]);
    }
}
