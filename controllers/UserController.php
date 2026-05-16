<?php
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

    public function getAllUsers()
    {
        $users = $this->userModel->getAll();
        return [
            'success' => true,
            'users' => $users
        ];
    }

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

    // --- FOR ADMIN EDIT USER ---
    public function getUserForAdmin($userId)
    {
        if (empty($userId)) return ['success' => false, 'message' => 'User ID is required'];

        $user = $this->userModel->findById($userId);
        if ($user) {
            return ['success' => true, 'user' => $user];
        }
        return ['success' => false, 'message' => 'User not found'];
    }

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

    // --- FOR ADMIN CREATE USER ---
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
