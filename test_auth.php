<?php
/**
 * Test Authentication Backend
 * 
 * This file tests the authentication system
 * Visit: http://localhost/taleeq/Taliq/test_auth.php
 */

require_once 'config/database.php';
require_once 'controllers/AuthController.php';

echo "<h1>Taleeq Authentication Test</h1>";

// Test 1: Database Connection
echo "<h2>1. Testing Database Connection</h2>";
if ($pdo) {
    echo "✅ Database connection successful!<br>";
    echo "Connected to: " . DBNAME . "<br>";
    echo "Host: " . DBHOST . "<br><br>";
} else {
    echo "❌ Connection failed<br><br>";
    exit;
}

// Test 2: Check if User table exists
echo "<h2>2. Testing User Table</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM User");
    $result = $stmt->fetch();
    echo "✅ User table exists!<br>";
    echo "Total users in database: " . $result['count'] . "<br><br>";
} catch (Exception $e) {
    echo "❌ User table error: " . $e->getMessage() . "<br><br>";
}

// Test 3: Test Admin Login
echo "<h2>3. Testing Admin Login</h2>";
$authController = new AuthController();
$loginResult = $authController->login('admin@taleeq.com', 'password123');

if ($loginResult['success']) {
    echo "✅ Admin login successful!<br>";
    echo "User: " . $loginResult['user']['first_name'] . " " . $loginResult['user']['last_name'] . "<br>";
    echo "Email: " . $loginResult['user']['email'] . "<br>";
    echo "Role: " . $loginResult['user']['role'] . "<br>";
    echo "Redirect URL: " . $loginResult['redirect'] . "<br><br>";
} else {
    echo "❌ Login failed: " . $loginResult['message'] . "<br><br>";
}

// Test 4: Check Session
echo "<h2>4. Testing Session</h2>";
if (isset($_SESSION['user_id'])) {
    echo "✅ Session created successfully!<br>";
    echo "Session User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Session Role: " . $_SESSION['role'] . "<br><br>";
} else {
    echo "❌ Session not created<br><br>";
}

echo "<hr>";
echo "<h2>Test Summary</h2>";
echo "<p><strong>Admin Test Credentials:</strong></p>";
echo "<ul>";
echo "<li>Email: admin@taleeq.com</li>";
echo "<li>Password: password123</li>";
echo "<li>Expected Redirect: /pages/admin/admin_home.html</li>";
echo "</ul>";

echo "<p><strong>Regular User Test Credentials:</strong></p>";
echo "<ul>";
echo "<li>Email: john@example.com</li>";
echo "<li>Password: password123</li>";
echo "<li>Expected Redirect: /pages/user/user_home.html</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='api/auth.php'>Test API Endpoint (GET)</a></p>";
echo "<p>To test login via API, use a tool like Postman or curl:</p>";
echo "<pre>";
echo "POST http://localhost/taleeq/Taliq/api/auth.php\n";
echo "Content-Type: application/json\n\n";
echo json_encode([
    'action' => 'login',
    'email' => 'admin@taleeq.com',
    'password' => 'password123'
], JSON_PRETTY_PRINT);
echo "</pre>";
?>
