<?php
/**
 * Authentication Controller
 *
 * Handles user authentication logic including login with session creation,
 * logout with session destruction, and new user registration with validation.
 * Syncs the user's cart from the database into the session upon successful login.
 *
 * @package    Taliq\Controllers
 * @subpackage Authentication
 * @version    1.0.0
 */

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../controllers/CartController.php';
require_once '../includes/functions.php';

class AuthController {

    /** @var User The User model instance. */
    private $userModel;
    
    /**
     * Initializes the AuthController with a User model instance.
     */
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Authenticates a user by email and password.
     *
     * Sets session variables and syncs the cart on success. Returns a redirect
     * URL based on the user's role (admin or user).
     *
     * @param string $email    The user's email address.
     * @param string $password The user's plaintext password.
     *
     * @return array Associative array with success status, message, redirect URL, and user data.
     */
    public function login($email, $password) {
        $email = sanitize_input($email);
        
        if (empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Email and password are required'
            ];
        }
        
        $user = $this->userModel->verifyPassword($email, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user['UserId'];
            $_SESSION['first_name'] = $user['FirstName'];
            $_SESSION['last_name'] = $user['LastName'];
            $_SESSION['email'] = $user['Email'];
            $_SESSION['role'] = $user['Role'];

            $cartController = new CartController();
            $cartController->syncToSession($user['UserId']);

            if ($user['Role'] === 'admin') {
                $redirectUrl = '/pages/admin/admin_home.html';
            } else {
                $redirectUrl = '/pages/user/user_home.html';
            }
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'redirect' => $redirectUrl,
                'user' => [
                    'id' => $user['UserId'],
                    'first_name' => $user['FirstName'],
                    'last_name' => $user['LastName'],
                    'email' => $user['Email'],
                    'role' => $user['Role']
                ]
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    /**
     * Logs the current user out by destroying the session.
     *
     * @return array Associative array with success status and message.
     */
    public function logout() {
        session_unset();
        session_destroy();
        return [
            'success' => true,
            'message' => 'Logged out successfully'
        ];
    }
    
    /**
     * Registers a new user account.
     *
     * Validates all required fields, email format, password length, and
     * checks for duplicate emails before creating the account.
     * Automatically logs the user in upon successful registration.
     *
     * @param string      $firstName   The user's first name.
     * @param string      $lastName    The user's last name.
     * @param string      $email       The user's email address.
     * @param string      $password    The user's plaintext password (min 6 chars).
     * @param string|null $phoneNumber Optional phone number.
     *
     * @return array Associative array with success status, message, redirect URL, and user data.
     */
    public function register($firstName, $lastName, $email, $password, $phoneNumber = null) {
        $firstName = sanitize_input($firstName);
        $lastName = sanitize_input($lastName);
        $email = sanitize_input($email);
        
        if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'All fields are required'
            ];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email format'
            ];
        }
        
        if (strlen($password) < 6) {
            return [
                'success' => false,
                'message' => 'Password must be at least 6 characters long'
            ];
        }
        
        if ($this->userModel->emailExists($email)) {
            return [
                'success' => false,
                'message' => 'Email already registered'
            ];
        }
        
        $userId = $this->userModel->create($firstName, $lastName, $email, $password, $phoneNumber);
        
        if ($userId) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'user';
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'redirect' => '/pages/user/user_home.html',
                'user' => [
                    'id' => $userId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'role' => 'user'
                ]
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Registration failed. Please try again.'
        ];
    }
}
