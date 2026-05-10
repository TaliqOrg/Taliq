# Reusable Header & Footer Components Guide

## Overview
This system provides unified header and footer components that automatically adapt based on user authentication status and role.

---

## File Structure

```
Taliq/
├── components/
│   ├── header.html          ← Unified header with 3 states
│   └── footer.html          ← Unified footer with 3 states
│
├── js/
│   ├── auth.js              ← Authentication logic
│   └── components.js        ← Component loader & state manager
│
└── pages/
    └── *.html               ← Your pages use placeholders
```

---

## How to Use in Any Page

### Step 1: Include Required Scripts
```html
<head>
    <!-- Styles -->
    <link rel="stylesheet" href="../css/welcome_page.css">
    
    <!-- Scripts (order matters!) -->
    <script src="../js/auth.js"></script>
    <script src="../js/components.js"></script>
</head>
```

### Step 2: Add Placeholders
```html
<body>
    <!-- Header Placeholder -->
    <div id="header-placeholder"></div>
    
    <!-- Your Content -->
    <main>
        <h1>Your Page Content</h1>
    </main>
    
    <!-- Footer Placeholder -->
    <div id="footer-placeholder"></div>
</body>
```

**That's it!** The components will load automatically and adapt to the user's state.

---

## Three States

### 1. Visitor (Not Logged In)
**Header Shows:**
- Logo: "Taliq"
- Links: Home, Explore, Contact Us, Log In
- No search bar
- No user profile

**Footer Shows:**
- Platform links
- Support links
- No user/admin sections

---

### 2. Logged-In User
**Header Shows:**
- Logo: "Taliq"
- Search bar
- Links: Dashboard, Explore, Contact Us
- Profile icon with user's first name
- Shopping cart
- Logout button

**Footer Shows:**
- Platform links
- Support links
- My Account section (Dashboard, Profile, Cart)

---

### 3. Admin
**Header Shows:**
- Logo: "Taliq Admin"
- Links: Dashboard, Users, Courses, Reports
- Profile icon with admin's name (shield icon)
- Logout button
- No search bar
- No cart

**Footer Shows:**
- Platform links
- Support links
- Admin section (Dashboard, Manage Users, Manage Courses)

---

## How It Works

### 1. Page Loads
```
Page loads → DOMContentLoaded event fires
    ↓
components.js detects placeholders
    ↓
Fetches header.html and footer.html
    ↓
Inserts HTML into placeholders
```

### 2. State Detection
```
components.js calls checkSession()
    ↓
Gets user authentication status from API
    ↓
Determines: Visitor | User | Admin
    ↓
Shows/hides appropriate navigation sections
```

### 3. Dynamic Updates
```
User info retrieved
    ↓
Updates all [data-user-name] elements
    ↓
Updates all [data-user-email] elements
    ↓
Header and footer reflect current user
```

---

## Customization

### Modify Header Navigation
Edit: `components/header.html`

**Add a new visitor link:**
```html
<div id="visitorLinks">
    <a href="/pages/pricing.html" class="nav-link">Pricing</a>
</div>
```

**Add a new user link:**
```html
<div id="userLinks">
    <a href="/pages/user/wishlist.html" class="nav-link">Wishlist</a>
</div>
```

**Add a new admin link:**
```html
<div id="adminLinks">
    <a href="/pages/admin/analytics.html" class="nav-link">Analytics</a>
</div>
```

---

### Modify Footer Content
Edit: `components/footer.html`

**Add a new footer column:**
```html
<div class="footer-column">
    <h4>Resources</h4>
    <a href="/pages/blog.html">Blog</a>
    <a href="/pages/faq.html">FAQ</a>
</div>
```

---

## Path Adjustments

### For Pages in Root (`/pages/`)
```html
<script src="../js/auth.js"></script>
<script src="../js/components.js"></script>
```

### For Pages in Subfolders (`/pages/user/` or `/pages/admin/`)
```html
<script src="../../js/auth.js"></script>
<script src="../../js/components.js"></script>
```

---

## Example Pages

### Public Page (Visitor)
```html
<!DOCTYPE html>
<html>
<head>
    <script src="../js/auth.js"></script>
    <script src="../js/components.js"></script>
</head>
<body>
    <div id="header-placeholder"></div>
    
    <main>
        <h1>Welcome to Taliq</h1>
        <p>Explore our courses!</p>
    </main>
    
    <div id="footer-placeholder"></div>
</body>
</html>
```

### User Page (Protected)
```html
<!DOCTYPE html>
<html>
<head>
    <script src="../../js/auth.js"></script>
    <script src="../../js/components.js"></script>
</head>
<body>
    <div id="header-placeholder"></div>
    
    <main>
        <h1>My Dashboard</h1>
        <p>Welcome, <span data-user-name>User</span>!</p>
    </main>
    
    <div id="footer-placeholder"></div>
</body>
</html>
```

### Admin Page (Protected)
```html
<!DOCTYPE html>
<html>
<head>
    <script src="../../js/auth.js"></script>
    <script src="../../js/components.js"></script>
</head>
<body>
    <div id="header-placeholder"></div>
    
    <main>
        <h1>Admin Dashboard</h1>
        <p>Manage your platform</p>
    </main>
    
    <div id="footer-placeholder"></div>
</body>
</html>
```

---

## Benefits

✅ **DRY (Don't Repeat Yourself)** - Write header/footer once, use everywhere  
✅ **Automatic State Management** - No manual show/hide logic needed  
✅ **Centralized Updates** - Change header/footer in one place  
✅ **Role-Based UI** - Different navigation for different users  
✅ **Easy Maintenance** - Update links in one file  
✅ **Consistent UX** - Same look and feel across all pages  

---

## Migration Guide

### Converting Existing Pages

**Before:**
```html
<body>
    <header class="main-header">
        <nav>
            <!-- 50 lines of navigation code -->
        </nav>
    </header>
    
    <main>Content</main>
    
    <footer>
        <!-- 30 lines of footer code -->
    </footer>
</body>
```

**After:**
```html
<head>
    <script src="../../js/auth.js"></script>
    <script src="../../js/components.js"></script>
</head>
<body>
    <div id="header-placeholder"></div>
    
    <main>Content</main>
    
    <div id="footer-placeholder"></div>
</body>
```

**Result:** 80 lines → 2 lines! 🎯

---

## Troubleshooting

### Header/Footer Not Loading
- Check browser console for errors
- Verify placeholder IDs: `header-placeholder` and `footer-placeholder`
- Check script paths are correct
- Ensure `components/` folder exists

### Wrong Navigation Showing
- Check `checkSession()` is returning correct data
- Verify user role in database
- Clear browser cache

### User Name Not Showing
- Ensure elements have `data-user-name` attribute
- Check session contains `first_name`
- Verify `updateUserInfo()` is called

---

## Summary

| Component | File | Purpose |
|-----------|------|---------|
| `header.html` | Unified header | 3 navigation states |
| `footer.html` | Unified footer | 3 footer states |
| `components.js` | Loader | Fetches & manages components |
| `auth.js` | Authentication | Session & protection |

**One header. One footer. Three states. Infinite pages.** 🚀
