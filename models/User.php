<?php

/**
 * User Model
 *
 * Handles all user-related database operations including authentication,
 * account creation, profile management, password operations, email
 * validation, and admin user management.
 *
 * @package    Taliq\Models
 * @subpackage User
 * @version    1.0.0
 */

class User
{
    /** @var PDO The database connection instance. */
    private $db;

    /** @var string The database table name. */
    private $table = 'User';

    public function __construct()
    {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Finds an active user by their email address.
     *
     * @param string $email The email to search for.
     * @return array|false The user record, or false if not found.
     */
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE Email = :email AND IsActive = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Verifies a user's password and returns the user record (without hash).
     *
     * @param string $email    The user's email.
     * @param string $password The plaintext password to verify.
     * @return array|false The user record without PasswordHash, or false.
     */
    public function verifyPassword($email, $password)
    {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['PasswordHash'])) {
            unset($user['PasswordHash']);
            return $user;
        }
        return false;
    }

    /**
     * Creates a new user account.
     *
     * @param string      $firstName   The first name.
     * @param string      $lastName    The last name.
     * @param string      $email       The email address.
     * @param string      $password    The plaintext password (will be hashed).
     * @param string|null $phoneNumber The phone number (optional).
     * @param string      $role        The user role (default 'user').
     * @return int|false The new user ID, or false on failure.
     */
    public function create($firstName, $lastName, $email, $password, $phoneNumber = null, $role = 'user')
    {
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

    /**
     * Checks if an email address already exists.
     *
     * @param string $email The email to check.
     * @return bool True if the email exists.
     */
    public function emailExists($email)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE Email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Finds an active user by their ID (excludes PasswordHash).
     *
     * @param int $userId The user ID.
     * @return array|false The user record without PasswordHash, or false.
     */
    public function findById($userId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE UserId = :user_id AND IsActive = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch();
        if ($user) {
            unset($user['PasswordHash']);
        }
        return $user;
    }

    /**
     * Updates a user's profile information.
     *
     * @param int         $userId      The user ID.
     * @param string      $firstName   The updated first name.
     * @param string      $lastName    The updated last name.
     * @param string      $email       The updated email.
     * @param string|null $phoneNumber The updated phone number (optional).
     * @return bool True on success.
     */
    public function updateProfile($userId, $firstName, $lastName, $email, $phoneNumber = null)
    {
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

    /**
     * Updates a user's password.
     *
     * @param int    $userId      The user ID.
     * @param string $newPassword The new plaintext password (will be hashed).
     * @return bool True on success.
     */
    public function updatePassword($userId, $newPassword)
    {
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

    /**
     * Verifies a user's current password by ID.
     *
     * @param int    $userId   The user ID.
     * @param string $password The plaintext password to verify.
     * @return bool True if the password matches.
     */
    public function verifyCurrentPassword($userId, $password)
    {
        $sql = "SELECT PasswordHash FROM {$this->table} WHERE UserId = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['PasswordHash'])) {
            return true;
        }
        return false;
    }

    /**
     * Checks if an email is in use by another user.
     *
     * @param string $email  The email to check.
     * @param int    $userId The current user's ID to exclude.
     * @return bool True if the email is used by another user.
     */
    public function emailExistsForOtherUser($email, $userId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE Email = :email AND UserId != :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':user_id' => $userId
        ]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Retrieves all users ordered by creation date.
     *
     * @return array Array of user records (without password hashes).
     */
    public function getAll()
    {
        $sql = "SELECT UserId, FirstName, LastName, Email, PhoneNumber, Role, IsActive, CreatedAt 
                FROM {$this->table} 
                ORDER BY CreatedAt DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Deletes a user by ID.
     *
     * @param int $userId The user ID to delete.
     * @return bool True on success.
     */
    public function delete($userId)
    {
        $sql = "DELETE FROM {$this->table} WHERE UserId = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }

    /**
     * Updates a user's details from the admin panel, with optional password change.
     *
     * @param int         $userId    The user ID.
     * @param string      $firstName The updated first name.
     * @param string      $lastName  The updated last name.
     * @param string      $email     The updated email.
     * @param string      $role      The updated role.
     * @param string|null $password  The new password, or null to keep existing.
     * @return bool True on success.
     */
    public function adminUpdateUser($userId, $firstName, $lastName, $email, $role, $password = null)
    {
        $sql = "UPDATE {$this->table} 
                SET FirstName = :first_name, 
                    LastName = :last_name, 
                    Email = :email, 
                    Role = :role,
                    UpdatedAt = CURRENT_TIMESTAMP";

        $params = [
            ':first_name' => $firstName,
            ':last_name'  => $lastName,
            ':email'      => $email,
            ':role'       => $role,
            ':user_id'    => (int)$userId 
        ];

        if (!empty($password)) {
            $sql .= ", PasswordHash = :password_hash";
            $params[':password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE UserId = :user_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
