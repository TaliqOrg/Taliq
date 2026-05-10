<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../includes/functions.php';

class UserController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function getProfile($userId) {
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
    
    public function updateProfile($userId, $firstName, $lastName, $email, $phoneNumber = null) {
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
    
    public function changePassword($userId, $currentPassword, $newPassword) {
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
}
