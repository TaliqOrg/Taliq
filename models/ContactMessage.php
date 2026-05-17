<?php
/**
 * ContactMessage Model
 *
 * Handles persistence of contact form submissions. Stores the sender's name,
 * email, subject, and message body into the ContactMessage table.
 *
 * @package    Taliq\Models
 * @subpackage Contact
 * @version    1.0.0
 */

class ContactMessage {
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Saves a contact form submission to the database.
     *
     * @param string $name    The sender's name.
     * @param string $email   The sender's email address.
     * @param string $subject The message subject.
     * @param string $message The message body.
     *
     * @return bool True on success.
     */
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
