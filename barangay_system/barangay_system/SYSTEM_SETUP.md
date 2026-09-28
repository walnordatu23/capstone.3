# Barangay System - Complete Setup Guide

## ✅ System Status: FULLY FUNCTIONAL

The barangay legislative and civic issue monitoring system is now fully set up with real login authentication and database integration.

---

## 📋 Test Accounts (All with password: `password`)

### Resident Accounts
- **Juan Dela Cruz** (Verified Resident)
  - Email: `juan.delacruz@email.com`
  - Password: `password`
  - Status: Residency Verified ✓

- **Ana Reyes** (Verified Resident)
  - Email: `ana.reyes@email.com`
  - Password: `password`
  - Status: Residency Verified ✓

- **Rey Gomez** (Verified Resident)
  - Email: `rey.gomez@email.com`
  - Password: `password`
  - Status: Residency Verified ✓

### Official Accounts
- **Maria Santos** (Officer - All Committees)
  - Email: `maria.santos@barangay.gov`
  - Password: `password`
  - Committee: All committees

- **Roberto Cruz** (Infrastructure Committee)
  - Email: `roberto.cruz@barangay.gov`
  - Password: `password`
  - Committee: Infrastructure

- **Luz Peralta** (Health Committee)
  - Email: `luz.peralta@barangay.gov`
  - Password: `password`
  - Committee: Health

### Admin Account
- **Pedro Bautista** (Administrator)
  - Email: `pedro.bautista@barangay.gov`
  - Password: `password`

---

## 🎯 Features Implemented

### For Residents
1. **Register** - Create new resident account with identity verification
2. **Login** - Secure authentication
3. **Submit Civic Issues** - Report infrastructure problems, waste management, peace & order issues
4. **Track Issues** - View status updates on submitted reports
5. **View Legislation** - Browse local ordinances and resolutions
6. **Profile** - Residency verification status

### For Officials
1. **Login** - Committee-based access
2. **Review Civic Reports** - See issues assigned to their committee
3. **Update Issue Status** - Change issue status (Open → In Progress → Resolved)
4. **Browse Legislation** - View all local ordinances and resolutions
5. **Committee Dashboard** - Count of assigned issues

### For Administrators
1. **Dashboard** - System-wide statistics
2. **User Management** - Create and manage all users (residents, officials, admins)
3. **Manage Residency** - Verify/unverify resident accounts
4. **View All Issues** - Full overview of civic issues
5. **System Monitoring** - Track metrics

---

## 🔄 Authentication Flow

```
User visits index.php (Landing Page)
    ↓
Choose role (Resident or Official/Admin)
    ↓
Login with email and password
    ↓
Database verifies credentials using bcrypt
    ↓
Session created with user data
    ↓
Redirect to dashboard
    ↓
User can access protected pages (requireLogin() and requireRole() checks)
```

---

## 📁 Database Schema

### users table
- id, full_name, email, password_hash, role, committee, residency_verified, address, identification, created_at

### legislation table
- id, title, type, category, description, status, published_date, created_at

### civic_issues table
- id, user_id, title, issue_type, location, description, photo_path, status, assigned_committee, created_at, updated_at

---

## 🚀 Key Implementation Details

1. **Password Hashing**: Uses bcrypt (PASSWORD_BCRYPT) for security
2. **Session Management**: PHP sessions with proper role-based access control
3. **Database Connection**: PDO with exception handling
4. **Input Validation**: Server-side validation on all forms
5. **File Uploads**: Civic issue photos saved to uploads/ directory
6. **Error Handling**: Flash messages for user feedback

---

## 📝 How to Test

1. **Test Resident Login**:
   - Go to http://localhost/barangay_system/
   - Click "Resident Login"
   - Use: juan.delacruz@email.com / password
   - Submit civic issue
   - Track issue status

2. **Test Official Login**:
   - Go to http://localhost/barangay_system/
   - Click "Official/Admin Login"
   - Use: maria.santos@barangay.gov / password
   - View assigned issues
   - Update issue status

3. **Test Admin Login**:
   - Go to http://localhost/barangay_system/
   - Click "Official/Admin Login"
   - Use: pedro.bautista@barangay.gov / password
   - Create new users
   - View system statistics

---

## ⚙️ Database Configuration

**File**: `config.php`
```php
$DB_HOST = 'localhost';
$DB_NAME = 'barangay_system';
$DB_USER = 'root';
$DB_PASS = '';
```

Make sure XAMPP MySQL is running.

---

## 🔐 Security Notes

- All passwords are hashed using bcrypt
- Session data is validated on each protected page
- User roles control access to different sections
- SQL injection prevention via PDO prepared statements
- HTML output is escaped to prevent XSS

---

## 📞 Support

For issues or questions, check:
- config.php for database configuration
- requireRole() and requireLogin() functions for access control
- Database structure in db_init.sql
