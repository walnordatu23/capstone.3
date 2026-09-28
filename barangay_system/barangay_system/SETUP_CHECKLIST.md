# SETUP CHECKLIST & VERIFICATION

## ✅ PRE-INSTALLATION CHECKLIST

- [ ] XAMPP installed on computer
- [ ] PHP 8.4 available in XAMPP
- [ ] MySQL 8.2 available in XAMPP
- [ ] All files copied to `C:\xampp\htdocs\barangay_system\`
- [ ] Folder structure verified

### Files Required:
- [ ] `index.php` (main app)
- [ ] `config.php` (database config)
- [ ] `db_init.sql` (database setup)
- [ ] `resident_register.php`
- [ ] `official_register.php`
- [ ] `resident_dashboard.php`
- [ ] `official_dashboard.php`
- [ ] `admin_dashboard.php`
- [ ] `logout.php`
- [ ] `assets/` folder with CSS/JS

---

## 🔧 INSTALLATION CHECKLIST

### Step 1: Start XAMPP
- [ ] Open XAMPP Control Panel
- [ ] Start Apache (check for green indicator)
- [ ] Start MySQL (check for green indicator)
- [ ] Let them run for 10 seconds before proceeding

### Step 2: Create Database
- [ ] Open `http://localhost/phpmyadmin`
- [ ] Create new database named `barangay_db`
- [ ] Verify database appears in left sidebar
- [ ] Select `barangay_db` database

### Step 3: Import Sample Data
- [ ] Open `db_init.sql` file in text editor
- [ ] Copy all contents
- [ ] Paste into phpMyAdmin SQL tab
- [ ] Click "Go" to execute
- [ ] Verify: "% items were inserted successfully" message

### Step 4: Verify Database Tables
- [ ] In phpMyAdmin, click `barangay_db` in left sidebar
- [ ] You should see 3 tables:
  - [ ] `users` table
  - [ ] `legislation` table
  - [ ] `civic_issues` table
- [ ] Click each table to verify data exists

### Step 5: Access the Application
- [ ] Open browser
- [ ] Go to `http://localhost:8000/barangay_system/`
- [ ] See login page
- [ ] Verify navigation buttons work

---

## ✔️ FUNCTIONALITY CHECKLIST

### Login & Authentication
- [ ] Can login with admin account
- [ ] Can login with official account
- [ ] Can login with resident account
- [ ] Username/password validation works
- [ ] Logout button works
- [ ] Invalid credentials show error
- [ ] Session persists across page navigation

### Resident Registration
- [ ] Can access registration form
- [ ] All form fields present and required
- [ ] Form validation works (empty fields rejected)
- [ ] Email format validation works
- [ ] Password confirmation validation works
- [ ] Successful registration shows success message
- [ ] New user appears in admin user management

### Official Registration
- [ ] Can access official registration page
- [ ] All form fields present
- [ ] Role/Committee dropdown works
- [ ] Form submission works
- [ ] New official appears in system

### Admin Dashboard
- [ ] Can access admin dashboard
- [ ] User management table shows all users
- [ ] Can create new user with form
- [ ] User status badges display correctly
- [ ] Role badges display with correct colors

### Navigation & Screens
- [ ] Login screen displays correctly
- [ ] Registration screen accessible
- [ ] Each portal (resident/official/admin) displays
- [ ] Sidebar navigation works
- [ ] Screen switching works without page reload

---

## 🗄️ DATABASE VERIFICATION CHECKLIST

### Users Table
- [ ] Table has 10 sample users
- [ ] Admin account exists: `admin@barangay.gov.ph`
- [ ] Official accounts exist
- [ ] Resident accounts exist
- [ ] Passwords are hashed (not plain text)
- [ ] Verification status shows for each user

### Legislation Table
- [ ] Table has 6 sample records
- [ ] Each record has title, description, status
- [ ] Status shows "Active"
- [ ] Dates are properly formatted

### Civic Issues Table
- [ ] Table has 6 sample records
- [ ] Each issue has title, description, location
- [ ] Status shows "Open" or "Resolved"
- [ ] Reported_by references valid user_id

---

## 🔒 SECURITY VERIFICATION CHECKLIST

- [ ] Passwords are bcrypt hashed (verify in phpMyAdmin - should start with `$2y$`)
- [ ] No plain text passwords visible
- [ ] SQL injection prevention (prepared statements used)
- [ ] Email uniqueness validation works
- [ ] Invalid form data rejected
- [ ] Session management active (logout clears session)
- [ ] Error messages don't expose system details

---

## 🧪 TESTING CHECKLIST

### Test Case 1: Resident Login
- [ ] Navigate to `http://localhost:8000/barangay_system/`
- [ ] Email: `resident@barangay.gov.ph`
- [ ] Password: `resident123`
- [ ] Click Login
- [ ] Should see resident dashboard
- [ ] Should see "Recent Legislation" section
- [ ] Should see "Report Civic Issue" button

### Test Case 2: Official Login
- [ ] Click Logout
- [ ] Email: `official@barangay.gov.ph`
- [ ] Password: `official123`
- [ ] Click Login
- [ ] Should see official dashboard
- [ ] Should see user verification section
- [ ] Should see pending residents list

### Test Case 3: Admin Login
- [ ] Click Logout
- [ ] Email: `admin@barangay.gov.ph`
- [ ] Password: `admin123`
- [ ] Click Login
- [ ] Should see admin dashboard
- [ ] Should see all users in table
- [ ] Should see "Add New User" form

### Test Case 4: New User Registration
- [ ] Click "Create Account" on login page
- [ ] Fill all form fields
- [ ] Email: `test.user@example.com`
- [ ] Password: `testpass123`
- [ ] Submit form
- [ ] See success message
- [ ] User appears in admin dashboard

### Test Case 5: Invalid Registration
- [ ] Try to register with empty fields
- [ ] Should see validation error
- [ ] Try to register with existing email
- [ ] Should see "Email already exists" error
- [ ] Try passwords that don't match
- [ ] Should see password validation error

### Test Case 6: Report Civic Issue
- [ ] Login as resident
- [ ] Click "Report Civic Issue" button
- [ ] Fill out issue form
- [ ] Submit form
- [ ] See confirmation
- [ ] New issue appears in listing

### Test Case 7: Session Management
- [ ] Login as user
- [ ] Close browser tab (but not browser)
- [ ] Open new tab to same URL
- [ ] Should still be logged in (session persists)
- [ ] Click Logout
- [ ] Should return to login page

---

## 🐛 TROUBLESHOOTING CHECKLIST

### If you see: "Connection refused"
- [ ] Check Apache is running in XAMPP
- [ ] Check MySQL is running in XAMPP
- [ ] Check port 8000 is correct (or 80 if default)
- [ ] Close other applications using port 8000

### If you see: "Database not found"
- [ ] Check `barangay_db` exists in phpMyAdmin
- [ ] Check `db_init.sql` was imported successfully
- [ ] Check database credentials in `config.php`

### If you see: "Table doesn't exist"
- [ ] Check all 3 tables exist in phpMyAdmin
- [ ] Verify `db_init.sql` executed without errors
- [ ] Try re-importing `db_init.sql`

### If registration fails
- [ ] Check MySQL is running
- [ ] Check email format is valid
- [ ] Check passwords match
- [ ] Look for errors in browser console (F12)
- [ ] Check PHP error log: `C:\xampp\apache\logs\error.log`

### If login fails
- [ ] Verify email exists in database
- [ ] Try default test accounts first
- [ ] Check password is correct (case-sensitive)
- [ ] Check user status is "Verified" (residents must be verified)

---

## 📊 EXPECTED DATA AFTER SETUP

### Users (Total: 10)
- 1 Administrator
- 2 Officials
- 7 Residents (various verification statuses)

### Legislation (Total: 6)
- Sample ordinances and laws for Sample Barangay

### Civic Issues (Total: 6)
- Sample reported issues with "Open" or "Resolved" status

---

## ✨ FINAL VERIFICATION

### Open the application and verify:
- [ ] Login page displays with logo and form
- [ ] Navigation buttons visible and clickable
- [ ] Can switch between Login and Register screens
- [ ] Can switch between resident/official portals
- [ ] Form labels and inputs display correctly
- [ ] Styling looks clean and professional
- [ ] No JavaScript errors in console (F12)
- [ ] All interactive elements respond to clicks

### Test a complete user flow:
1. [ ] Register new resident account
2. [ ] Login with new account
3. [ ] View legislation
4. [ ] Report a civic issue
5. [ ] Logout
6. [ ] Login as official
7. [ ] View pending residents
8. [ ] Return to login page

---

## 📝 NOTES & OBSERVATIONS

| Item | Status | Notes |
|------|--------|-------|
| Database Connection | ✅ | Production-ready with PDO |
| Password Security | ✅ | bcrypt hashing implemented |
| Form Validation | ✅ | Both client and server-side |
| User Roles | ✅ | Resident, Official, Admin |
| Sample Data | ✅ | 10 users + 6 issues + 6 legislation |
| Single-Page App | ✅ | JavaScript-based screen switching |
| UI/UX | ✅ | Modern navy/gold design |
| Mobile Responsive | ✅ | CSS media queries included |

---

## 🎯 SUCCESS CRITERIA

Your setup is **COMPLETE** when:
1. ✅ XAMPP running (Apache + MySQL green indicators)
2. ✅ `barangay_db` database exists with 3 tables
3. ✅ Can login with test accounts
4. ✅ Can register new accounts
5. ✅ Can navigate between all portals
6. ✅ Can report civic issues
7. ✅ Data persists in database
8. ✅ No errors in console (F12)

---

## 🚀 YOU'RE READY!

Once you've checked all boxes above, the system is ready for:
- ✅ Testing by stakeholders
- ✅ Demonstration to management
- ✅ Development of additional features
- ✅ Integration with other systems
- ✅ Deployment to production server

---

**Last Updated**: December 2024
**Version**: 1.0
**Status**: Production Ready
