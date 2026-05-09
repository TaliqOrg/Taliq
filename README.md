# Taleeq - Learning Management System

A learning management platform built with pure PHP, JavaScript, HTML, and CSS.

## Technology Stack

- **Backend**: Pure PHP (no frameworks)
- **Frontend**: Pure JavaScript, HTML5, CSS3
- **Database**: MySQL
- **Server**: Apache (with mod_rewrite)

## Project Structure

```
Taliq/
│
├── api/                          # REST API endpoints
│   ├── auth.php                  # Authentication endpoints (login, register, logout)
│   ├── courses.php               # Course management endpoints
│   ├── users.php                 # User management endpoints
│   ├── cart.php                  # Shopping cart endpoints
│   └── enrollments.php           # Course enrollment endpoints
│
├── config/                       # Configuration files
│   ├── database.php              # Database connection and configuration
│   └── constants.php             # Application constants and settings
│
├── controllers/                  # Business logic controllers
│   ├── AuthController.php        # Authentication logic
│   ├── CourseController.php      # Course management logic
│   ├── UserController.php        # User management logic
│   └── CartController.php        # Shopping cart logic
│
├── models/                       # Database models
│   ├── User.php                  # User model (CRUD operations)
│   ├── Course.php                # Course model (CRUD operations)
│   ├── Enrollment.php            # Enrollment model
│   └── Cart.php                  # Cart model
│
├── includes/                     # Shared utility files
│   └── functions.php             # Helper functions
│
├── pages/                        # HTML pages
│   ├── admin/                    # Admin pages
│   │   ├── admin_home.html       # Admin dashboard
│   │   ├── manage_courses.html   # Course management interface
│   │   ├── manage_users.html     # User management interface
│   │   ├── new_course.html       # Create new course
│   │   ├── edit_course_details.html
│   │   ├── course_curriculum.html
│   │   ├── New_User.html
│   │   └── Edit_User.html
│   │
│   ├── user/                     # User pages
│   │   ├── user_home.html        # User dashboard
│   │   ├── profile.html          # User profile
│   │   ├── cart.html             # Shopping cart
│   │   ├── checkout.html         # Checkout page
│   │   └── course_player.html    # Course video player
│   │
│   ├── welcome_page.html         # Landing page
│   ├── login.html                # Login page
│   ├── signup.html               # Registration page
│   ├── explore.html              # Browse courses
│   ├── course_details.html       # Course details page
│   └── contact.html              # Contact page
│
├── css/                          # Stylesheets
│   ├── welcome_page.css
│   ├── login.css
│   ├── user_home.css
│   ├── admin_home.css
│   ├── profile.css
│   ├── explore.css
│   ├── course_details.css
│   ├── course_curriculum.css
│   ├── course_player.css
│   ├── manage_courses.css
│   ├── Cart.css
│   ├── checkout.css
│   └── contact.css
│
├── js/                           # JavaScript files
│   ├── api.js                    # API wrapper for backend calls
│   ├── auth.js                   # Authentication functions
│   ├── courses.js                # Course-related functions
│   ├── users.js                  # User-related functions
│   ├── cart.js                   # Shopping cart functions
│   └── utils.js                  # Utility functions
│
├── images/                       # Static images
│   ├── welcome_image.png
│   ├── login_image.png
│   └── [course thumbnails]
│
├── uploads/                      # User-uploaded files
│   └── .gitkeep                  # Keep directory in git
│
├── database/                     # Database files
│   ├── schema.sql                # Database schema/structure
│   └── seed.sql                  # Sample data for testing
│
├── .htaccess                     # Apache rewrite rules
├── .gitignore                    # Git ignore file
├── index.php                     # Main entry point
└── README.md                     # This file
```

## Directory Descriptions

### `/api/`
Contains all REST API endpoints that handle HTTP requests. Each file corresponds to a specific resource (auth, courses, users, etc.) and handles GET, POST, PUT, DELETE operations.

### `/config/`
Configuration files for database connections, application constants, and environment settings.

### `/controllers/`
Business logic layer that processes requests, interacts with models, and returns responses. Controllers act as intermediaries between API endpoints and models.

### `/models/`
Data access layer that handles all database operations (CRUD). Each model represents a database table and contains methods for interacting with that table.

### `/includes/`
Shared utility functions and helpers used throughout the application.

### `/pages/`
HTML pages organized by user role (admin, user) and public pages. These are the frontend views.

### `/css/`
Stylesheets for each page, maintaining separation of concerns.

### `/js/`
JavaScript files organized by functionality. The `api.js` file provides a centralized way to make backend API calls.

### `/images/`
Static images and assets used in the application.

### `/uploads/`
Directory for user-uploaded files (course materials, profile pictures, etc.). Should have write permissions.

### `/database/`
SQL files for database setup and sample data.

## Setup Instructions

### 1. Database Setup
```sql
-- Create database
CREATE DATABASE taleeq_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import schema
mysql -u root -p taleeq_db < database/schema.sql

-- (Optional) Import sample data
mysql -u root -p taleeq_db < database/seed.sql
```

### 2. Configuration
Update `config/database.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'taleeq_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

Update `config/constants.php` with your base URL:
```php
define('BASE_URL', 'http://localhost/taleeq/Taliq');
```

### 3. File Permissions
```bash
chmod 755 uploads/
```

### 4. Apache Configuration
Ensure `mod_rewrite` is enabled and `.htaccess` files are allowed.

## API Endpoints

### Authentication
- `POST /api/auth.php` - Login, Register, Logout
- `GET /api/auth.php` - Check authentication status

### Courses
- `GET /api/courses.php?action=list` - Get all courses
- `GET /api/courses.php?action=get&id={id}` - Get course by ID
- `GET /api/courses.php?action=search&q={query}` - Search courses
- `POST /api/courses.php` - Create new course (admin only)
- `PUT /api/courses.php` - Update course (admin only)
- `DELETE /api/courses.php` - Delete course (admin only)

### Users
- `GET /api/users.php?action=profile&id={id}` - Get user profile
- `GET /api/users.php?action=list` - Get all users (admin only)
- `PUT /api/users.php` - Update user profile
- `DELETE /api/users.php` - Delete user (admin only)

### Cart
- `GET /api/cart.php` - Get user's cart
- `POST /api/cart.php` - Add item to cart
- `DELETE /api/cart.php` - Remove item from cart

### Enrollments
- `GET /api/enrollments.php` - Get user's enrolled courses
- `POST /api/enrollments.php` - Enroll in a course

## Database Schema

The database consists of **18 tables** organized into the following categories:

### Core Tables
1. **User** - User accounts and authentication
2. **Category** - Course and workshop categories

### Content Tables
3. **Course** - Online courses
4. **Workshop** - In-person/virtual workshops
5. **Lesson** - Individual lessons within courses
6. **WorkshopSession** - Scheduled workshop sessions

### E-commerce Tables
7. **Cart** - User shopping carts
8. **CartItem** - Items in shopping carts
9. **Order** - Purchase orders
10. **OrderItem** - Items in orders
11. **Discount** - Discount codes and promotions
12. **PaymentMethod** - Available payment methods
13. **Payment** - Payment transactions

### Learning & Engagement Tables
14. **Enrollment** - Course enrollments and progress
15. **WorkshopRegistration** - Workshop registrations
16. **Certificate** - Issued certificates
17. **Wishlist** - User wishlists
18. **Review** - Course and workshop reviews

### Key Features
- **Foreign Key Constraints** - Maintain referential integrity
- **Indexes** - Optimized for common queries
- **ENUM Types** - Controlled values for status fields
- **Timestamps** - Track creation and updates
- **Cascading Deletes** - Automatic cleanup of related records
- **Unique Constraints** - Prevent duplicate enrollments
- **Check Constraints** - Validate data (e.g., ratings 1-5)

For complete schema details, see `database/schema.sql`

## Development Workflow

1. **Database First**: Define schema in `database/schema.sql`
2. **Models**: Create model classes in `models/` for database operations
3. **Controllers**: Implement business logic in `controllers/`
4. **API Endpoints**: Create API endpoints in `api/`
5. **Frontend**: Connect HTML pages with JavaScript API calls
6. **Testing**: Test each component individually

## Security Considerations

- All user inputs should be sanitized
- Use prepared statements for database queries (PDO)
- Passwords must be hashed (password_hash/password_verify)
- Implement CSRF protection for forms
- Validate file uploads
- Use HTTPS in production
- Implement rate limiting for API endpoints
- Session management and timeout

## Best Practices

- Follow MVC pattern (Model-View-Controller)
- Use PDO for database operations
- Implement error handling and logging
- Write clean, documented code
- Use meaningful variable and function names
- Keep functions small and focused
- Separate concerns (business logic vs presentation)

## Next Steps

1. Implement database schema in `database/schema.sql`
2. Create database connection in `config/database.php`
3. Build models for User, Course, Enrollment, Cart
4. Implement controllers for business logic
5. Create API endpoints
6. Connect frontend pages with JavaScript
7. Test and debug
8. Deploy to production

