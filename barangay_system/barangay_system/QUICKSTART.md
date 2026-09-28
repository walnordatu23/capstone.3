# QUICK START GUIDE - Sample Barangay Portal

## 5-Minute Setup

### ✅ BEFORE YOU START
Make sure you have:
- **XAMPP** installed ([Download here](https://www.apachefriends.org/))
- PHP 8.4 and MySQL 8.2+ (included in XAMPP)
- All files copied to: `C:\xampp\htdocs\barangay_system\`

---

## STEP 1: START XAMPP (1 minute)
1. **Open XAMPP Control Panel**
   - Start > Search "XAMPP Control Panel"
   - Click **Apache** → "Start"
   - Click **MySQL** → "Start"
   - Both should show green indicators when running

---

## STEP 2: INITIALIZE DATABASE (2 minutes)

### Option A: Easy Way (Recommended)
1. Open browser: **http://localhost/phpmyadmin**
2. Click **"New"** in left sidebar
3. Enter database name: **`barangay_db`**
4. Click **"Create"**
5. Click on **barangay_db** (now shown in left sidebar)
6. Click **"SQL"** tab (top menu)
7. Open file: `C:\xampp\htdocs\barangay_system\db_init.sql`
8. Copy **ALL** contents
9. Paste into SQL text box in phpMyAdmin
10. Click **"Go"** button
11. ✅ Database ready!

### Option B: Command Line Way
```bash
cd C:\xampp\mysql\bin
mysql -u root < C:\xampp\htdocs\barangay_system\db_init.sql
```
Press Enter (no password)

---

## STEP 3: OPEN THE SITE (1 minute)
1. **Open your browser**
2. **Go to**: `http://localhost:8000/barangay_system/`
3. **You're in!** 🎉

---

## NOW WHAT? LOGIN WITH TEST ACCOUNTS

### 👤 Regular Resident
```
Email: resident@barangay.gov.ph
Password: resident123
```
↳ Can report issues, view legislation, track civic issues

### 💼 Official/Staff
```
Email: official@barangay.gov.ph
Password: official123
```
↳ Can verify residents, manage legislation, review issues

### 🔑 Administrator
```
Email: admin@barangay.gov.ph
Password: admin123
```
↳ Full system control - create users, manage everything

---

## 📝 REGISTER A NEW RESIDENT ACCOUNT

### From Login Page:
1. Click **"Create Account"** button
2. Fill out form:
   - First Name: e.g., `Juan`
   - Last Name: e.g., `Dela Cruz`
   - Email: e.g., `juan@example.com`
   - Phone: e.g., `09XXXXXXXXX`
   - Address: e.g., `Purok 1, Sample Barangay`
   - ID Type: Select from dropdown (e.g., Voter's ID)
   - ID Number: e.g., `ABC12345678`
   - Password: e.g., `mypassword123` (min 6 chars)
   - Confirm Password: type again
3. Click **"Submit Registration"**
4. ✅ Account created!

**Note**: New resident accounts need verification by an official (takes 1-2 business days in real system, set to approved in demo)

---

## 📋 REGISTER A NEW OFFICIAL/STAFF ACCOUNT

### Direct Link:
- Go to: `http://localhost:8000/barangay_system/official_register.php`
- Fill form with:
  - Full name
  - Email
  - Committee (dropdown)
  - Position
  - Password
- Click **"Register"**
- ✅ Account created immediately!

---

## 👨‍💼 CREATE USERS FROM ADMIN DASHBOARD

### As Administrator:
1. Login with admin account
2. Click **"User Management"** section
3. Click **"Add New User"** button
4. Select type: Resident / Official / Admin
5. Fill required fields
6. Click **"Create User"**
7. ✅ User created!

---

## ❓ SOMETHING NOT WORKING?

### Database connection error?
- **Check**: MySQL is running in XAMPP
- **Solution**: Start MySQL in XAMPP Control Panel

### Blank page?
- **Check**: Apache is running
- **Solution**: Start Apache in XAMPP Control Panel
- Check URL: `http://localhost:8000/barangay_system/`

### "Can't connect to MySQL server"?
- **Solution**: 
  1. Stop MySQL in XAMPP Control Panel
  2. Wait 5 seconds
  3. Start MySQL again
  4. Refresh browser page

### Form won't submit?
- **Check**: Browser console (Press F12)
- Look for red error messages
- Verify all required fields filled
- Check MySQL is running

---

## 🎯 WHAT TO TRY FIRST

### Quick Demo (5 minutes):
1. ✅ Login as resident with test account
2. ✅ View "Recent Legislation"
3. ✅ Report a new civic issue
4. ✅ Logout and login as official
5. ✅ View pending issues

### Test Registration (3 minutes):
1. ✅ Logout
2. ✅ Click "Create Account"
3. ✅ Fill out and submit form
4. ✅ New account created successfully

### Admin Features (5 minutes):
1. ✅ Login as admin
2. ✅ View User Management
3. ✅ Create a new test user
4. ✅ Logout and login as the new user

---

## 📊 SAMPLE DATA INCLUDED

The system comes pre-loaded with:
- **10 sample users** (1 admin, 2 officials, 7 residents)
- **6 legislation records** (sample laws/ordinances)
- **6 civic issues** (sample reported problems)

This lets you test the system immediately without adding data manually.

---

## 🔐 IMPORTANT SECURITY NOTES

✅ **Passwords**: Use strong passwords (8+ characters recommended)
✅ **Default Accounts**: Change default test account passwords in production
✅ **Database**: Only accessible from localhost (not internet-facing)
✅ **Sessions**: Auto-logout after inactivity (set in config.php)

---

## 📁 FILE LOCATIONS REFERENCE

| File | Purpose |
|------|---------|
| `index.php` | Main login page & single-page app |
| `resident_register.php` | Resident registration |
| `official_register.php` | Official registration |
| `resident_dashboard.php` | Resident portal |
| `official_dashboard.php` | Official portal |
| `admin_dashboard.php` | Admin control panel |
| `config.php` | Database settings & helpers |
| `db_init.sql` | Database schema & sample data |

---

## 🚀 NEXT STEPS

**When ready for production**:
1. Buy a domain (e.g., `barangaysample.gov.ph`)
2. Get web hosting with PHP 8.4+ and MySQL 8.2+
3. Upload files via FTP/SSH
4. Update `config.php` with production database credentials
5. Replace sample data with real user data
6. Set up backups and monitoring

---

## 💡 TIPS & TRICKS

**Forgot password?**
- Have admin create new account with temp password
- User can change password after login

**Want to test multiple users?**
- Use test accounts provided
- Or register new accounts immediately

**Need to reset everything?**
- Delete `barangay_db` database in phpMyAdmin
- Run `db_init.sql` again

**Want different test data?**
- Edit `db_init.sql` before running it
- Change addresses, legislation titles, etc.

---

## ✉️ FORM SUBMISSION NOTES

All forms submitted to:
- **Resident Registration** → `resident_register.php`
- **Official Registration** → `official_register.php`
- **Civic Issues** → `report_issue.php`
- **Admin Functions** → `admin_dashboard.php`

Forms include:
✅ **Client-side validation** (browser-level checks)
✅ **Server-side validation** (PHP-level verification)
✅ **Password hashing** (bcrypt encryption)
✅ **SQL injection prevention** (prepared statements)
✅ **Email uniqueness check** (no duplicate accounts)

---

**🎉 ENJOY YOUR BARANGAY PORTAL!**

For detailed documentation, see `README.md`
