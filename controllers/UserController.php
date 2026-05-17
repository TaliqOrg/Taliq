<?php
/**
 * User Controller
 *
 * Manages user account operations for both regular users and administrators.
 * Regular users can view their profile, update personal information, and change
 * their password. Administrators have additional access to list all users,
 * view individual user details, create new users, update existing users, and
 * delete user accounts.
 *
 * @package    Taliq\Controllers
 * @subpackage Users
 * @version    1.0.0
 */

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../includes/functions.php';

class UserController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Retrieves the profile data for a specific user.
     *
     * @param int $userId The user's ID.
     *
     * @return array Associative array with success status and user data.
     */
    public function getProfile($userId)
    {
        if (empty($userId)) {
            return [
                'success' => false,
                'message' => 'User ID is required'
            ];
        }

        $user = $this->userModel->findById($userId);

        if ($user) {
            return [
                'success' => true,
                'user' => [
                    'id' => $user['UserId'],
                    'first_name' => $user['FirstName'],
                    'last_name' => $user['LastName'],
                    'email' => $user['Email'],
                    'phone_number' => $user['PhoneNumber'],
                    'role' => $user['Role']
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'User not found'
        ];
    }

    /**
     * Updates a user's profile information.
     *
     * Sanitizes inputs, validates required fields and email format, checks
     * for email uniqueness, and syncs the session on success.
     *
     * @param int         $userId      The user's ID.
     * @param string      $firstName   The updated first name.
     * @param string      $lastName    The updated last name.
     * @param string      $email       The updated email address.
     * @param string|null $phoneNumber The updated phone number (optional).
     *
     * @return array Associative array with success status, message, and updated user data.
     */
    public function updateProfile($userId, $firstName, $lastName, $email, $phoneNumber = null)
    {
        $firstName = sanitize_input($firstName);
        $lastName = sanitize_input($lastName);
        $email = sanitize_input($email);
        $phoneNumber = $phoneNumber ? sanitize_input($phoneNumber) : null;

        if (empty($userId)) {
            return [
                'success' => false,
                'message' => 'User ID is required'
            ];
        }

        if (empty($firstName) || empty($lastName) || empty($email)) {
            return [
                'success' => false,
                'message' => 'First name, last name, and email are required'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email format'
            ];
        }

        if ($this->userModel->emailExistsForOtherUser($email, $userId)) {
            return [
                'success' => false,
                'message' => 'Email already in use by another account'
            ];
        }

        $result = $this->userModel->updateProfile($userId, $firstName, $lastName, $email, $phoneNumber);

        if ($result) {
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['email'] = $email;

            return [
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone_number' => $phoneNumber
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to update profile'
        ];
    }

    /**
     * Changes the user's password after verifying the current one.
     *
     * @param int    $userId          The user's ID.
     * @param string $currentPassword The current password for verification.
     * @param string $newPassword     The new password (min 6 characters).
     *
     * @return array Associative array with success status and message.
     */
    public function changePassword($userId, $currentPassword, $newPassword)
    {
        if (empty($userId)) {
            return [
                'success' => false,
                'message' => 'User ID is required'
            ];
        }

        if (empty($currentPassword) || empty($newPassword)) {
            return [
                'success' => false,
                'message' => 'Current password and new password are required'
            ];
        }

        if (strlen($newPassword) < 6) {
            return [
                'success' => false,
                'message' => 'New password must be at least 6 characters long'
            ];
        }

        if (!$this->userModel->verifyCurrentPassword($userId, $currentPassword)) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }

        $result = $this->userModel->updatePassword($userId, $newPassword);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Password updated successfully'
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to update password'
        ];
    }

    /**
     * Retrieves all users in the system (admin only).
     *
     * @return array Associative array with success status and users list.
     */
    public function getAllUsers()
    {
        $users = $this->userModel->getAll();
        return [
            'success' => true,
            'users' => $users
        ];
    }

    /**
     * Deletes a user account by ID (admin only).
     *
     * @param int $userId The user ID to delete.
     *
     * @return array Associative array with success status and message.
     */
    public function deleteUser($userId)
    {
        if (empty($userId)) {
            return ['success' => false, 'message' => 'User ID is required'];
        }

        $result = $this->userModel->delete($userId);

        if ($result) {
            return ['success' => true, 'message' => 'User deleted successfully'];
        }
        return ['success' => false, 'message' => 'Failed to delete user'];
    }

    /**
     * Retrieves a user's full details for admin editing.
     *
     * @param int $userId The user ID to retrieve.
     *
     * @return array Associative array with success status and user data.
     */
    public function getUserForAdmin($userId)
    {
        if (empty($userId)) return ['success' => false, 'message' => 'User ID is required'];

        $user = $this->userModel->findById($userId);
        if ($user) {
            return ['success' => true, 'user' => $user];
        }
        return ['success' => false, 'message' => 'User not found'];
    }

    /**
     * Updates a user's details from the admin panel.
     *
     * Sanitizes inputs, validates required fields, checks email uniqueness,
     * and optionally updates the password if provided.
     *
     * @param int   $userId The user ID to update.
     * @param array $data   Associative array with first_name, last_name, email, role, and optional password.
     *
     * @return array Associative array with success status and message.
     */
    public function adminUpdateUser($userId, $data)
    {
        if (empty($userId)) return ['success' => false, 'message' => 'User ID is required'];


        $firstName = trim(sanitize_input($data['first_name'] ?? ''));
        $lastName  = trim(sanitize_input($data['last_name'] ?? ''));
        $email     = trim(sanitize_input($data['email'] ?? ''));
        $role      = trim(sanitize_input($data['role'] ?? 'user'));
        $password  = !empty($data['password']) ? trim($data['password']) : null;

        if (empty($firstName) || empty($lastName) || empty($email)) {
            return ['success' => false, 'message' => 'First name, last name, and email are required'];
        }


        if ($this->userModel->emailExistsForOtherUser($email, (int)$userId)) {
            return ['success' => false, 'message' => 'Email already in use by another account'];
        }

        $result = $this->userModel->adminUpdateUser((int)$userId, $firstName, $lastName, $email, $role, $password);

        if ($result) {
            return ['success' => true, 'message' => 'User updated successfully'];
        }
        return ['success' => false, 'message' => 'Failed to update user'];
    }

    /**
     * Creates a new user account from the admin panel.
     *
     * Validates all required fields, email format, email uniqueness,
     * and minimum password length before creating.
     *
     * @param array $data Associative array with first_name, last_name, email, role, and password.
     *
     * @return array Associative array with success status and message.
     */
    public function adminCreateUser($data) {
        $firstName = trim(sanitize_input($data['first_name'] ?? ''));
        $lastName  = trim(sanitize_input($data['last_name'] ?? ''));
        $email     = trim(sanitize_input($data['email'] ?? ''));
        $role      = trim(sanitize_input($data['role'] ?? 'user'));
        $password  = trim($data['password'] ?? '');
        
        if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }


        if ($this->userModel->emailExists($email)) {
            return ['success' => false, 'message' => 'Email is already in use by another account.'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
        }
        
        $newUserId = $this->userModel->create($firstName, $lastName, $email, $password, null, $role);
        
        if ($newUserId) {
            return ['success' => true, 'message' => 'User created successfully'];
        }
        return ['success' => false, 'message' => 'Failed to create user. Database error.'];
    }
}
