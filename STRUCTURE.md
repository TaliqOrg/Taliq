# Taleeq Project Structure Map

This document maps the project files to their corresponding database tables and functionality.

## Database Tables → Files Mapping

### 1. User Table
**Database:** `User` (UserId, FirstName, LastName, Email, PasswordHash, PhoneNumber, Role, CreatedAt, UpdatedAt, IsActive)

**Backend Files:**
- `models/User.php` - User CRUD operations
- `controllers/UserController.php` - User business logic
- `controllers/AuthController.php` - Authentication logic
- `api/users.php` - User API endpoints
- `api/auth.php` - Authentication API endpoints

**Frontend Files:**
- `js/auth.js` - Authentication functions
- `js/users.js` - User profile functions
- `pages/login.html` - Login page
- `pages/signup.html` - Registration page
- `pages/user/profile.html` - User profile page
- `pages/admin/manage_users.html` - User management (admin)
- `pages/admin/New_User.html` - Create user (admin)
- `pages/admin/Edit_User.html` - Edit user (admin)

---

### 2. Category Table
**Database:** `Category` (CategoryId, Name, Description, CreatedAt)

**Backend Files:**
- `models/Category.php` - Category CRUD operations
- `controllers/CourseController.php` - Handles category filtering
- `api/courses.php` - Category-related endpoints

**Frontend Files:**
- `js/courses.js` - Category filtering functions
- `pages/explore.html` - Browse by category

---

### 3. Course Table
**Database:** `Course` (CourseId, CategoryId, Title, Description, Price, DurationHours, Level, Language, ThumbnailUrl, IsPublished, CreatedAt, UpdatedAt)

**Backend Files:**
- `models/Course.php` - Course CRUD operations
- `controllers/CourseController.php` - Course business logic
- `api/courses.php` - Course API endpoints

**Frontend Files:**
- `js/courses.js` - Course display and management
- `pages/explore.html` - Browse courses
- `pages/course_details.html` - Course details
- `pages/admin/manage_courses.html` - Course management (admin)
- `pages/admin/new_course.html` - Create course (admin)
- `pages/admin/edit_course_details.html` - Edit course (admin)

---

### 4. Workshop Table
**Database:** `Workshop` (WorkshopId, CategoryId, Title, Description, Price, Location, Capacity, Language, IsPublished, CreatedAt, UpdatedAt)

**Backend Files:**
- `models/Workshop.php` - Workshop CRUD operations
- `controllers/WorkshopController.php` - Workshop business logic
- `api/workshops.php` - Workshop API endpoints

**Frontend Files:**
- `js/workshops.js` - Workshop display and management
- `pages/explore.html` - Browse workshops (shared with courses)

---

### 5. Lesson Table
**Database:** `Lesson` (LessonId, CourseId, Title, Description, ContentType, ContentUrl, OrderNumber, DurationMinutes, CreatedAt)

**Backend Files:**
- `models/Lesson.php` - Lesson CRUD operations
- `controllers/CourseController.php` - Lesson management within courses
- `api/courses.php` - Lesson endpoints (nested under courses)

**Frontend Files:**
- `js/courses.js` - Lesson display functions
- `pages/admin/course_curriculum.html` - Manage course lessons (admin)
- `pages/user/course_player.html` - Play course lessons

---

### 6. WorkshopSession Table
**Database:** `WorkshopSession` (SessionId, WorkshopId, SessionDate, StartTime, EndTime, Location, AvailableSeats, CreatedAt)

**Backend Files:**
- `models/Workshop.php` - Session management (part of Workshop model)
- `controllers/WorkshopController.php` - Session scheduling logic
- `api/workshops.php` - Session endpoints

**Frontend Files:**
- `js/workshops.js` - Session display and booking

---

### 7. Cart Table
**Database:** `Cart` (CartId, UserId, Status, CreatedAt, UpdatedAt)

**Backend Files:**
- `models/Cart.php` - Cart CRUD operations
- `controllers/CartController.php` - Cart business logic
- `api/cart.php` - Cart API endpoints

**Frontend Files:**
- `js/cart.js` - Cart management functions
- `pages/user/cart.html` - Shopping cart page

---

### 8. CartItem Table
**Database:** `CartItem` (CartItemId, CartId, CourseId, WorkshopId, Quantity, UnitPrice, AddedAt)

**Backend Files:**
- `models/Cart.php` - CartItem operations (part of Cart model)
- `controllers/CartController.php` - Item management
- `api/cart.php` - CartItem endpoints

**Frontend Files:**
- `js/cart.js` - Add/remove cart items
- `pages/user/cart.html` - Display cart items

---

### 9. Order Table
**Database:** `Order` (OrderId, UserId, DiscountId, OrderDate, TotalAmount, Status, CreatedAt)

**Backend Files:**
- `models/Order.php` - Order CRUD operations
- `controllers/OrderController.php` - Order processing logic
- `api/orders.php` - Order API endpoints

**Frontend Files:**
- `js/orders.js` - Order management
- `pages/user/checkout.html` - Checkout and order creation

---

### 10. OrderItem Table
**Database:** `OrderItem` (OrderItemId, OrderId, CourseId, WorkshopId, Quantity, UnitPrice, Subtotal)

**Backend Files:**
- `models/Order.php` - OrderItem operations (part of Order model)
- `controllers/OrderController.php` - Item processing
- `api/orders.php` - OrderItem endpoints

**Frontend Files:**
- `js/orders.js` - Order details display
- `pages/user/checkout.html` - Order summary

---

### 11. Discount Table
**Database:** `Discount` (DiscountId, Code, Description, DiscountType, DiscountValue, StartDate, EndDate, IsActive)

**Backend Files:**
- `models/Order.php` - Discount validation (part of Order model)
- `controllers/OrderController.php` - Apply discount logic
- `api/orders.php` - Discount validation endpoint

**Frontend Files:**
- `js/cart.js` - Apply discount code
- `pages/user/checkout.html` - Discount code input

---

### 12. PaymentMethod Table
**Database:** `PaymentMethod` (PaymentMethodId, MethodName, Description, IsActive)

**Backend Files:**
- `models/Payment.php` - Payment method operations
- `controllers/PaymentController.php` - Payment method logic
- `api/payments.php` - Payment method endpoints

**Frontend Files:**
- `js/orders.js` - Payment method selection
- `pages/user/checkout.html` - Payment method options

---

### 13. Payment Table
**Database:** `Payment` (PaymentId, OrderId, PaymentMethodId, Amount, PaymentDate, Status, TransactionReference)

**Backend Files:**
- `models/Payment.php` - Payment CRUD operations
- `controllers/PaymentController.php` - Payment processing logic
- `api/payments.php` - Payment API endpoints

**Frontend Files:**
- `js/orders.js` - Payment processing
- `pages/user/checkout.html` - Payment form

---

### 14. Enrollment Table
**Database:** `Enrollment` (EnrollmentId, UserId, CourseId, EnrollmentDate, ProgressPercentage, CompletionStatus, CompletedAt)

**Backend Files:**
- `models/Enrollment.php` - Enrollment CRUD operations
- `controllers/CourseController.php` - Enrollment logic
- `api/enrollments.php` - Enrollment API endpoints

**Frontend Files:**
- `js/courses.js` - Enrollment functions
- `pages/user/user_home.html` - Display enrolled courses
- `pages/user/course_player.html` - Track progress

---

### 15. WorkshopRegistration Table
**Database:** `WorkshopRegistration` (WorkshopRegistrationId, UserId, WorkshopId, SessionId, RegistrationDate, AttendanceStatus)

**Backend Files:**
- `models/Workshop.php` - Registration operations
- `controllers/WorkshopController.php` - Registration logic
- `api/workshops.php` - Registration endpoints

**Frontend Files:**
- `js/workshops.js` - Workshop registration
- `pages/user/user_home.html` - Display registered workshops

---

### 16. Certificate Table
**Database:** `Certificate` (CertificateId, UserId, CourseId, WorkshopId, IssueDate, CertificateCode, CertificateUrl)

**Backend Files:**
- `models/Certificate.php` - Certificate CRUD operations
- `controllers/CourseController.php` - Certificate generation
- `api/courses.php` - Certificate endpoints

**Frontend Files:**
- `js/courses.js` - Certificate display
- `pages/user/profile.html` - Display certificates

---

### 17. Wishlist Table
**Database:** `Wishlist` (WishlistId, UserId, CourseId, WorkshopId, AddedAt)

**Backend Files:**
- `models/Wishlist.php` - Wishlist CRUD operations
- `controllers/UserController.php` - Wishlist management
- `api/wishlist.php` - Wishlist API endpoints

**Frontend Files:**
- `js/wishlist.js` - Wishlist functions
- `pages/user/profile.html` - Display wishlist

---

### 18. Review Table
**Database:** `Review` (ReviewId, UserId, CourseId, WorkshopId, Rating, Comment, ReviewDate)

**Backend Files:**
- `models/Review.php` - Review CRUD operations
- `controllers/CourseController.php` - Review management
- `api/reviews.php` - Review API endpoints

**Frontend Files:**
- `js/reviews.js` - Review submission and display
- `pages/course_details.html` - Display and submit reviews

---

## Complete File Structure

```
Taliq/
│
├── config/
│   ├── database.php          # Database connection (all tables)
│   └── constants.php          # App constants
│
├── models/                    # One model per table
│   ├── User.php              # → User table
│   ├── Category.php          # → Category table
│   ├── Course.php            # → Course table
│   ├── Workshop.php          # → Workshop + WorkshopSession tables
│   ├── Lesson.php            # → Lesson table
│   ├── Cart.php              # → Cart + CartItem tables
│   ├── Order.php             # → Order + OrderItem + Discount tables
│   ├── Payment.php           # → Payment + PaymentMethod tables
│   ├── Enrollment.php        # → Enrollment table
│   ├── Certificate.php       # → Certificate table
│   ├── Wishlist.php          # → Wishlist table
│   └── Review.php            # → Review table
│
├── controllers/               # Business logic
│   ├── AuthController.php    # → User authentication
│   ├── UserController.php    # → User management
│   ├── CourseController.php  # → Course, Lesson, Enrollment, Certificate
│   ├── WorkshopController.php # → Workshop, WorkshopSession, Registration
│   ├── CartController.php    # → Cart, CartItem
│   ├── OrderController.php   # → Order, OrderItem, Discount
│   └── PaymentController.php # → Payment, PaymentMethod
│
├── api/                       # REST endpoints
│   ├── auth.php              # Authentication endpoints
│   ├── users.php             # User management
│   ├── courses.php           # Course + Lesson + Enrollment
│   ├── workshops.php         # Workshop + Session + Registration
│   ├── cart.php              # Cart + CartItem
│   ├── orders.php            # Order + OrderItem + Discount
│   ├── payments.php          # Payment + PaymentMethod
│   ├── enrollments.php       # Enrollment tracking
│   ├── reviews.php           # Reviews
│   └── wishlist.php          # Wishlist
│
├── js/                        # Frontend JavaScript
│   ├── api.js                # API wrapper (all endpoints)
│   ├── auth.js               # Authentication
│   ├── users.js              # User profile
│   ├── courses.js            # Course display/management
│   ├── workshops.js          # Workshop display/management
│   ├── cart.js               # Shopping cart
│   ├── orders.js             # Order processing
│   ├── reviews.js            # Review submission
│   ├── wishlist.js           # Wishlist management
│   └── utils.js              # Utility functions
│
├── database/
│   ├── schema.sql            # All 18 tables
│   └── seed.sql              # Sample data
│
└── pages/                     # HTML pages
    ├── welcome_page.html
    ├── login.html
    ├── signup.html
    ├── explore.html
    ├── course_details.html
    ├── contact.html
    ├── admin/
    │   ├── admin_home.html
    │   ├── manage_courses.html
    │   ├── manage_users.html
    │   ├── new_course.html
    │   ├── edit_course_details.html
    │   ├── course_curriculum.html
    │   ├── New_User.html
    │   └── Edit_User.html
    └── user/
        ├── user_home.html
        ├── profile.html
        ├── cart.html
        ├── checkout.html
        └── course_player.html
```

## Implementation Priority

### Phase 1: Core Authentication & Users
1. User table → User.php → AuthController.php → auth.php → auth.js
2. Login/Signup pages

### Phase 2: Content Management
3. Category table → Category.php
4. Course table → Course.php → CourseController.php → courses.php → courses.js
5. Lesson table → Lesson.php
6. Workshop table → Workshop.php → WorkshopController.php → workshops.php

### Phase 3: E-commerce
7. Cart + CartItem → Cart.php → CartController.php → cart.php → cart.js
8. Order + OrderItem → Order.php → OrderController.php → orders.php
9. Payment + PaymentMethod → Payment.php → PaymentController.php → payments.php
10. Discount table integration

### Phase 4: Learning Features
11. Enrollment → Enrollment.php → enrollments.php
12. WorkshopRegistration → workshops.php
13. Certificate → Certificate.php

### Phase 5: Engagement
14. Review → Review.php → reviews.php → reviews.js
15. Wishlist → Wishlist.php → wishlist.php → wishlist.js

---

## Quick Reference

**To add a new feature:**
1. Check which table(s) it involves
2. Create/update the model(s) in `/models/`
3. Create/update the controller in `/controllers/`
4. Create/update the API endpoint in `/api/`
5. Create/update the JavaScript in `/js/`
6. Update the HTML page(s) in `/pages/`

**File naming convention:**
- Models: Singular, PascalCase (e.g., `User.php`, `Course.php`)
- Controllers: Singular + "Controller" (e.g., `UserController.php`)
- API files: Plural, lowercase (e.g., `users.php`, `courses.php`)
- JS files: Plural, lowercase (e.g., `users.js`, `courses.js`)
