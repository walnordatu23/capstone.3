# API & ENDPOINT REFERENCE GUIDE

Complete documentation of all form endpoints, accepted parameters, and expected responses.

---

## 📋 TABLE OF CONTENTS

1. [Authentication Endpoints](#authentication-endpoints)
2. [Registration Endpoints](#registration-endpoints)
3. [Admin Endpoints](#admin-endpoints)
4. [Civic Issue Endpoints](#civic-issue-endpoints)
5. [Utility Endpoints](#utility-endpoints)
6. [Error Responses](#error-responses)
7. [Using These Endpoints](#using-these-endpoints)

---

## AUTHENTICATION ENDPOINTS

### Resident Login
**Endpoint**: `/resident_login.php`  
**Method**: `POST`  
**Description**: Authenticate a resident user

#### Request Parameters:
```
{
  "email": "resident@barangay.gov.ph",  // Required, user's email
  "password": "resident123"              // Required, plaintext password
}
```

#### Response - Success (302 Redirect):
```
Location: /resident_dashboard.php
Session: resident user loaded
```

#### Response - Failure (200 OK):
```
Display: "Invalid email or password"
Session: No session created
```

#### Validation Rules:
- Email must be valid format
- Password must be at least 6 characters
- User account must exist in database
- User role must be "resident"
- User verification status must be "Verified"

---

### Official Login
**Endpoint**: `/official_login.php`  
**Method**: `POST`  
**Description**: Authenticate an official/staff user

#### Request Parameters:
```
{
  "email": "official@barangay.gov.ph",  // Required, official's email
  "password": "official123"              // Required, plaintext password
}
```

#### Response - Success (302 Redirect):
```
Location: /official_dashboard.php
Session: official user loaded
```

#### Response - Failure (200 OK):
```
Display: "Invalid email or password"
Session: No session created
```

#### Validation Rules:
- Email must be valid format
- Password must be at least 6 characters
- User account must exist in database
- User role must be "official" or "admin"
- No verification status requirement

---

## REGISTRATION ENDPOINTS

### Resident Registration
**Endpoint**: `/resident_register.php`  
**Method**: `POST`  
**Description**: Create a new resident user account

#### Request Parameters:
```
{
  "full_name": "Juan Dela Cruz",         // Generated from first_name + last_name
  "first_name": "Juan",                  // Required for form submission
  "last_name": "Dela Cruz",              // Required for form submission
  "email": "juan@example.com",           // Required, must be unique
  "phone": "+63 9XX XXXX XXXX",         // Required, phone number
  "address": "Purok 1, Sample Barangay", // Required, home address
  "id_type": "Voter's ID",               // Required, see ID types below
  "identification": "ABC12345678",       // Required, ID number
  "password": "mypassword123",           // Required, min 6 characters
  "password_confirm": "mypassword123"    // Required, must match password
}
```

#### ID Type Options (dropdown):
- PhilSys ID
- Barangay ID
- Voter's ID
- Postal ID

#### Response - Success (200 OK):
```
Display: "✔ Registration successful! Your account is pending verification."
Session: Flash message set
Action: Redirect to login page after 3 seconds
```

#### Response - Validation Errors (200 OK):
```
Display: Error alert with specific messages:
  - "First name is required"
  - "Last name is required"
  - "Email address is required"
  - "Email is not valid"
  - "Email already exists"
  - "Phone number is required"
  - "Address is required"
  - "ID type is required"
  - "ID number is required"
  - "Password must be at least 6 characters"
  - "Password confirmation required"
  - "Passwords do not match"
```

#### Validation Rules:
- All fields required
- Email must be unique (check against `users` table)
- Email must be valid format (regex pattern)
- Password min 6 characters
- Passwords must match
- First/Last names combined into full_name
- ID type must be from dropdown list
- Default role: "resident"
- Default verification_status: "Pending"

#### Database Insert:
```sql
INSERT INTO users (full_name, email, password_hash, role, phone, address, id_type, identification, verification_status)
VALUES (:full_name, :email, :password_hash, 'resident', :phone, :address, :id_type, :identification, 'Pending')
```

---

### Official Registration
**Endpoint**: `/official_register.php`  
**Method**: `POST`  
**Description**: Create a new official/staff user account

#### Request Parameters:
```
{
  "full_name": "Maria Santos",           // Required, full name
  "email": "maria@barangay.gov.ph",     // Required, must be unique
  "committee": "Health Committee",       // Required, selected from dropdown
  "position": "Health Officer",          // Optional, position/title
  "password": "official123",             // Required, min 6 characters
  "password_confirm": "official123"      // Required, must match password
}
```

#### Committee Options (dropdown):
- Finance Committee
- Health Committee
- Public Safety Committee
- Education Committee
- Infrastructure Committee

#### Response - Success (200 OK):
```
Display: "✔ Registration successful!"
Session: Flash message set
Action: Redirect to login page
```

#### Response - Validation Errors (200 OK):
```
Display: Error alert with specific messages:
  - "Full name required"
  - "Email required"
  - "Invalid email format"
  - "Email already exists"
  - "Committee required"
  - "Password required"
  - "Password min 6 characters"
  - "Passwords must match"
```

#### Validation Rules:
- All fields required (position is optional)
- Email must be unique
- Email must be valid format
- Password min 6 characters
- Passwords must match
- Committee must be from dropdown list
- Default role: "official"
- Default verification_status: "Verified" (no pending)

#### Database Insert:
```sql
INSERT INTO users (full_name, email, password_hash, role, committee, verification_status)
VALUES (:full_name, :email, :password_hash, 'official', :committee, 'Verified')
```

---

## ADMIN ENDPOINTS

### Create User (Admin Dashboard)
**Endpoint**: `/admin_dashboard.php`  
**Method**: `POST`  
**Description**: Create new user of any type (requires admin authentication)

#### Request Parameters:
```
{
  "user_type": "resident",               // Required: resident|official|admin
  "full_name": "John Reyes",             // Required for all types
  "email": "john.reyes@example.com",     // Required, must be unique
  "phone": "09XXXXXXXXX",                // Required for resident type
  "address": "Purok 1, Sample Barangay", // Required for resident type
  "committee": "Finance Committee",      // Required for official type
  "password": "temppassword123",         // Required for all types
  "id_type": "Voter's ID",               // Required for resident type
  "identification": "ABC12345678"        // Required for resident type
}
```

#### Response - Success (200 OK):
```
Display: Success message with new user ID
Session: Flash message set
Table: Updated user list displayed immediately
```

#### Response - Validation Errors (200 OK):
```
Display: Error alert in admin form
```

#### Validation Rules:
- User type required and must be valid
- Full name required
- Email required and must be unique
- Password required min 6 characters
- Type-specific fields required based on user type
- Defaults:
  - Resident: verification_status = "Verified"
  - Official: verification_status = "Verified"
  - Admin: verification_status = "Verified"

#### Access Control:
- Requires user role = "admin"
- Requires active session
- Automatic redirect to login if not authenticated

---

## CIVIC ISSUE ENDPOINTS

### Report Civic Issue
**Endpoint**: `/report_issue.php`  
**Method**: `POST`  
**Description**: Submit a new civic issue report

#### Request Parameters:
```
{
  "title": "Potholes on Main Street",    // Required, issue title
  "description": "Large potholes...",    // Required, detailed description
  "location": "Main Street, Purok 1",    // Required, specific location
  "reported_by": 3                       // Auto-filled from session (user_id)
}
```

#### Response - Success (200 OK):
```
Display: "✔ Issue reported successfully!"
Session: Flash message set
Action: Added to civic_issues table
```

#### Response - Validation Errors (200 OK):
```
Display: Error messages:
  - "Title is required"
  - "Description is required"
  - "Description must be at least 10 characters"
  - "Location is required"
```

#### Validation Rules:
- Title required, max 255 characters
- Description required, min 10 characters
- Location required
- user_id taken from session
- Default status: "Open"
- created_date: automatic timestamp

#### Database Insert:
```sql
INSERT INTO civic_issues (title, description, location, reported_by, status)
VALUES (:title, :description, :location, :user_id, 'Open')
```

#### Access Control:
- Requires authenticated session
- Resident or Official role

---

### View Issue Status
**Endpoint**: `/issue_status.php`  
**Method**: `GET`  
**Description**: Retrieve details and status of a civic issue

#### Query Parameters:
```
{
  "id": 1  // Required, issue_id
}
```

#### Response - Success (200 OK):
```json
{
  "issue_id": 1,
  "title": "Potholes on Main Street",
  "description": "Large potholes damaging vehicles...",
  "location": "Main Street",
  "reported_by": 3,
  "status": "Open",
  "created_date": "2024-01-15 10:30:00",
  "assigned_to": null,
  "updated_date": null
}
```

#### Response - Issue Not Found (200 OK):
```
Display: "Issue not found"
```

#### Query:
```sql
SELECT * FROM civic_issues WHERE issue_id = ?
```

#### Access Control:
- Requires authenticated session
- Can view own issues
- Officials can view all issues

---

## UTILITY ENDPOINTS

### Logout
**Endpoint**: `/logout.php`  
**Method**: `GET`  
**Description**: Destroy user session and sign out

#### Response (302 Redirect):
```
Location: /index.php (login page)
Session: Destroyed
Cookies: Session cookie deleted
```

#### Actions:
```php
session_destroy();
setcookie(session_name(), '', 0, '/');
header('Location: index.php');
```

#### Access Control:
- Works with any authenticated session
- Public endpoint (no auth check needed)

---

### Get Flash Message
**Endpoint**: In-page JavaScript  
**Method**: Session variable access  
**Description**: Retrieve and display user feedback message

#### Session Variable:
```php
$_SESSION['flash_message'] => "Registration successful!"
$_SESSION['flash_type'] => "success|error|warning|info"
```

#### HTML Display:
```html
<?php
if (isset($_SESSION['flash_message'])) {
  echo '<div class="alert alert-' . $_SESSION['flash_type'] . '">';
  echo $_SESSION['flash_message'];
  echo '</div>';
  unset($_SESSION['flash_message']);
  unset($_SESSION['flash_type']);
}
?>
```

#### Message Types:
- `success` - CSS class: `alert-success` (green)
- `error` - CSS class: `alert-danger` (red)
- `warning` - CSS class: `alert-warn` (orange)
- `info` - CSS class: `alert-info` (blue)

---

## ERROR RESPONSES

### Database Connection Error
```
Status: 500 Internal Server Error
Display: "Database connection failed"
Action: Check config.php database credentials
```

### SQL Query Error
```
Status: 500 Internal Server Error
Display: "Query failed" (safe message)
Log: Full error logged to Apache error log
Action: Check query syntax in PHP file
```

### Duplicate Email Error
```
Status: 200 OK
Display: "Email already exists in the system"
Action: User prompted to enter different email
```

### Invalid Email Format
```
Status: 200 OK
Display: "Email is not valid"
Action: User prompted to enter valid email
```

### Password Mismatch
```
Status: 200 OK
Display: "Passwords do not match"
Action: User prompted to re-enter passwords
```

### Unauthorized Access
```
Status: 302 Redirect
Location: /index.php (login page)
Message: "You must be logged in to access this page"
```

### Insufficient Permissions
```
Status: 302 Redirect
Location: /index.php
Message: "You do not have permission to access this resource"
```

### Session Expired
```
Status: 302 Redirect
Location: /index.php
Message: "Your session has expired. Please login again."
```

---

## USING THESE ENDPOINTS

### From HTML Form
```html
<form action="/resident_register.php" method="POST">
  <input type="text" name="first_name" required>
  <input type="email" name="email" required>
  <input type="password" name="password" required>
  <button type="submit">Register</button>
</form>
```

### From JavaScript (AJAX)
```javascript
const formData = new FormData();
formData.append('full_name', 'Juan Dela Cruz');
formData.append('email', 'juan@example.com');
formData.append('password', 'mypassword123');

fetch('/resident_register.php', {
  method: 'POST',
  body: formData
})
.then(response => response.text())
.then(html => {
  // Process response HTML
  console.log(html);
});
```

### With cURL
```bash
curl -X POST http://localhost:8000/barangay_system/resident_register.php \
  -d "full_name=Juan+Dela+Cruz" \
  -d "email=juan@example.com" \
  -d "password=mypassword123"
```

### With Postman
1. Set method to POST
2. Enter endpoint URL
3. Go to "Body" tab
4. Select "form-data"
5. Enter key-value pairs
6. Click "Send"

---

## SECURITY NOTES

### Password Security
- ✅ All passwords hashed with bcrypt (PASSWORD_BCRYPT)
- ✅ Passwords never stored in plain text
- ✅ Password hashes start with `$2y$` (bcrypt identifier)
- ✅ Password comparison uses `password_verify()` function

### SQL Injection Prevention
- ✅ All queries use PDO prepared statements
- ✅ Parameters bound with named placeholders (`:param`)
- ✅ No string concatenation in SQL
- ✅ Input validation before database insert

### Session Security
- ✅ Session data stored server-side only
- ✅ Session ID obfuscated in cookies
- ✅ Session timeout after inactivity
- ✅ Secure session cookie flags set

### Input Validation
- ✅ Email format validation (regex)
- ✅ Phone number format validation
- ✅ Password minimum length (6 characters)
- ✅ Required field validation
- ✅ Type checking for form fields

### Access Control
- ✅ Endpoints check for active session
- ✅ Role-based access control implemented
- ✅ Admin functions require admin role
- ✅ Dashboard access restricted by role

---

## TESTING ENDPOINTS

### Quick Test URLs
```
Login: http://localhost:8000/barangay_system/index.php
Register Resident: http://localhost:8000/barangay_system/resident_register.php
Register Official: http://localhost:8000/barangay_system/official_register.php
Admin Dashboard: http://localhost:8000/barangay_system/admin_dashboard.php
Logout: http://localhost:8000/barangay_system/logout.php
```

### Test Accounts
```
Admin: admin@barangay.gov.ph / admin123
Official: official@barangay.gov.ph / official123
Resident: resident@barangay.gov.ph / resident123
```

---

## DATABASE HELPER FUNCTIONS

### In config.php

#### currentUser()
```php
$current_user = currentUser();
// Returns array with user data or NULL if not logged in
```

#### requireLogin()
```php
requireLogin();
// Redirects to login if not authenticated
```

#### requireRole($roles)
```php
requireRole(['admin']);
// Redirects to login if user doesn't have required role
```

#### flashMessage()
```php
echo flashMessage();
// Outputs flash message HTML with styling
```

#### setFlash($message, $type = 'success')
```php
setFlash('Account created!', 'success');
// Sets flash message for next page load
```

---

## FUTURE API ENHANCEMENTS

Potential endpoints for future development:

```
POST   /api/users/password-reset
GET    /api/users/{id}/profile
PUT    /api/users/{id}/profile
GET    /api/issues/search?q=query
GET    /api/issues/{id}/comments
POST   /api/issues/{id}/comments
GET    /api/legislation/
POST   /api/legislation/
PUT    /api/legislation/{id}
DEL    /api/legislation/{id}
```

---

**Last Updated**: December 2024  
**API Version**: 1.0  
**Status**: Production Ready
