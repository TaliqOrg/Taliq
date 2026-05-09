# Admin Login Feature - Simple Implementation

## What This Does
Admin can login from the login page and will be automatically redirected to the admin home page.

---

## Files Involved

### 1. **config/config.inc.php** - Database Settings
```php
define('DBHOST', 'localhost');
define('DBNAME', 'taleeq_db');
define('DBUSER', 'root');
define('DBPASS', '');
define('DBCHARSET', 'utf8mb4');
define('DBCONNSTRING', "mysql:host=" . DBHOST . ";dbname=" . DBNAME . ";charset=" . DBCHARSET);
```

### 2. **config/database.php** - Connect to Database
```php
require_once 'config.inc.php';

try {
    $pdo = new PDO(DBCONNSTRING, DBUSER, DBPASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
```

### 3. **config/constants.php** - Start Session
```php
session_start();
```

### 4. **includes/functions.php** - Helper Functions
```php
function sanitize_input($data) {
    // Clean user input
}

function json_response($data, $status_code = 200) {
    // Send JSON response
}
```

### 5. **models/User.php** - User Database Operations
```php
class User {
    public function findByEmail($email) {
        // Find user by email
    }
    
    public function verifyPassword($email, $password) {
        // Check if password is correct
    }
}
```

### 6. **controllers/AuthController.php** - Login Logic
```php
class AuthController {
    public function login($email, $password) {
        // 1. Verify email and password
        // 2. Create session
        // 3. Return redirect URL based on role
    }
    
    public function logout() {
        // Destroy session
    }
}
```

### 7. **api/auth.php** - API Endpoint
```php
// Receives login request from frontend
// Calls AuthController
// Returns JSON response
```

---

## How It Works

### Login Flow:
1. User enters email and password in login page
2. Frontend sends POST request to `/api/auth.php`
3. API calls `AuthController->login()`
4. Controller verifies credentials using `User->verifyPassword()`
5. If valid:
   - Creates session with user data
   - Returns success with redirect URL
   - If role = 'admin' → redirect to `/pages/admin/admin_home.html`
   - If role = 'user' → redirect to `/pages/user/user_home.html`
6. Frontend redirects user to appropriate page

---

## Test Credentials

**Admin:**
- Email: `admin@taleeq.com`
- Password: `password123`
- Redirects to: `/pages/admin/admin_home.html`

**Regular User:**
- Email: `john@example.com`
- Password: `password123`
- Redirects to: `/pages/user/user_home.html`

---

## API Usage

### Login Request
```javascript
POST /api/auth.php
Content-Type: application/json

{
  "action": "login",
  "email": "admin@taleeq.com",
  "password": "password123"
}
```

### Login Response (Success)
```json
{
  "success": true,
  "message": "Login successful",
  "redirect": "/pages/admin/admin_home.html",
  "user": {
    "id": 1,
    "first_name": "Admin",
    "last_name": "User",
    "email": "admin@taleeq.com",
    "role": "admin"
  }
}
```

### Login Response (Failed)
```json
{
  "success": false,
  "message": "Invalid email or password"
}
```

---

## Session Data Stored

After successful login:
```php
$_SESSION['user_id']     // User ID
$_SESSION['first_name']  // First Name
$_SESSION['last_name']   // Last Name
$_SESSION['email']       // Email
$_SESSION['role']        // Role (admin/user)
```

---

## Testing

Visit: `http://localhost/taleeq/Taliq/test_auth.php`

Tests:
1. ✅ Database connection
2. ✅ User table exists
3. ✅ Admin login works
4. ✅ Session created

---

## What's NOT Included (Simplified)

❌ Registration feature  
❌ Password reset  
❌ Remember me  
❌ User profile update  
❌ Complex validation  
❌ Email verification  

**Focus:** Just admin login and redirect!
