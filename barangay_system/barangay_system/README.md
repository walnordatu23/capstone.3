# Sample Barangay - Legislative Information & Civic Issue Monitoring Portal

A comprehensive web-based system for managing barangay legislative information, civic issue tracking, and citizen engagement.

## Features

✅ **User Registration & Authentication**
- Resident self-registration with ID verification
- Official/Staff registration with committee assignment
- Admin account creation via dashboard
- Secure password hashing with bcrypt
- Session-based authentication

✅ **Role-Based Access Control**
- **Residents**: View legislation, report civic issues, track status
- **Officials**: Manage legislation, review civic issues, verify residents
- **Administrators**: Full system control, user management, data oversight

✅ **Core Functionality**
- Legislative browsing and search
- Civic issue reporting and tracking
- Real-time status updates
- User profile management
- Administrative dashboard

✅ **Security**
- Prepared SQL statements (SQL injection prevention)
- bcrypt password hashing
- Session management
- Input validation (client & server-side)
- Email uniqueness verification

## Technology Stack

- **Backend**: PHP 8.4
- **Database**: MySQL 8.2.12
- **Frontend**: HTML5, CSS3, JavaScript
- **Server**: XAMPP (Apache + PHP)
- **ORM**: PDO with prepared statements

## System Requirements

- **PHP**: 8.4 or higher
- **MySQL**: 8.2 or higher
- **XAMPP**: Latest version with PHP 8.4 and MySQL
- **Browser**: Modern browser (Chrome, Firefox, Edge)
- **Port**: 8000 (default)

## Installation & Setup

### Step 1: Install XAMPP
Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)

### Step 2: Place Files in XAMPP Root
Copy the entire `barangay_system` folder to:
```
C:\xampp\htdocs\barangay_system
```

### Step 3: Start XAMPP Services
1. Open XAMPP Control Panel
2. Start **Apache** (click "Start" button)
3. Start **MySQL** (click "Start" button)

### Step 4: Initialize Database

#### Option A: Using phpMyAdmin (Recommended)
1. Open browser and go to: `http://localhost/phpmyadmin`
2. Click "New" in left sidebar to create new database
3. Database name: `barangay_db`
4. Click "Create"
5. Select the newly created `barangay_db` database
6. Click "SQL" tab
7. Copy entire contents of `db_init.sql` file
8. Paste into SQL textarea
9. Click "Go" to execute

#### Option B: Using MySQL Command Line
1. Open Command Prompt/PowerShell
2. Navigate to: `C:\xampp\mysql\bin`
3. Run:
   ```bash
   mysql -u root -p < C:\xampp\htdocs\barangay_system\db_init.sql
   ```
4. Press Enter (no password prompted if default XAMPP setup)

### Step 5: Start the Application
1. Open browser
2. Navigate to: **`http://localhost:8000/barangay_system/`**
3. You should see the login page

## Default Test Accounts

### Administrator Account
- **Email**: admin@barangay.gov.ph
- **Password**: admin123

### Official/Staff Account
- **Email**: official@barangay.gov.ph
- **Password**: official123

### Resident Account
- **Email**: resident@barangay.gov.ph
- **Password**: resident123

**Note**: Resident accounts start with "Pending" verification status. Officials can verify them from the official dashboard.

## User Registration Workflow

### Resident Registration
1. Click "Create Account" on login page
2. Fill out registration form with:
   - First & Last Name
   - Email address
   - Contact number
   - Home address
   - Valid ID type & number
   - Password (6+ characters)
3. Submit form
4. Account created with "Pending" status
5. Officials verify within 1-2 business days
6. After verification, can access resident portal

### Official Registration
1. Go to `http://localhost:8000/barangay_system/official_register.php`
2. Fill out registration form with:
   - Full name
   - Email address
   - Committee assignment
   - Position/role
   - Password (6+ characters)
3. Submit form
4. Account created immediately (no verification required)
5. Can access official portal

### Admin User Creation
1. Login as administrator
2. Go to "User Management" section
3. Click "Add New User"
4. Select user type (Resident/Official/Admin)
5. Fill required fields
6. Click "Create User"
7. User account created and ready to use

## File Structure

```
barangay_system/
├── index.php                  # Main login & single-page app
├── config.php                 # Database connection & helpers
├── db_init.sql                # Database schema & sample data
│
├── resident_register.php       # Resident registration page
├── resident_login.php          # Resident login page
├── resident_dashboard.php      # Resident portal
│
├── official_register.php       # Official registration page
├── official_login.php          # Official login page
├── official_dashboard.php      # Official portal
│
├── admin_dashboard.php         # Admin control panel
├── legislation.php             # Legislation listing
├── report_issue.php            # Civic issue reporting
├── issue_status.php            # Issue tracking
│
├── logout.php                  # Session cleanup
└── assets/                     # CSS, JS, images
```

## Key Pages & Functions

### Public Pages
- **index.php** - Login & registration screens
- **resident_register.php** - Resident account creation
- **official_register.php** - Official account creation

### Resident Pages
- **resident_dashboard.php** - Resident portal with issue reporting
- **legislation.php** - View barangay legislation

### Official Pages
- **official_dashboard.php** - Official dashboard with user verification
- **legislation.php** - Manage legislation records

### Admin Pages
- **admin_dashboard.php** - Full system control
- **User management** - Create, edit, delete users
- **Verification** - Approve resident accounts

### Utility Pages
- **report_issue.php** - Submit civic issues
- **issue_status.php** - Track issue progress
- **logout.php** - Session cleanup

## Database Schema

### users Table
```sql
CREATE TABLE users (
  user_id INT PRIMARY KEY AUTO_INCREMENT,
  full_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('resident', 'official', 'admin') NOT NULL,
  phone VARCHAR(20),
  address TEXT,
  committee VARCHAR(100),
  id_type VARCHAR(50),
  identification VARCHAR(100),
  verification_status ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### legislation Table
```sql
CREATE TABLE legislation (
  legislation_id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  status VARCHAR(50) DEFAULT 'Active',
  enacted_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### civic_issues Table
```sql
CREATE TABLE civic_issues (
  issue_id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(255),
  reported_by INT,
  status VARCHAR(50) DEFAULT 'Open',
  created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reported_by) REFERENCES users(user_id)
);
```

## Sample Data

The system comes pre-loaded with 10 sample users, 6 legislation records, and 6 civic issues to demonstrate functionality.

**Sample User Roles:**
- 1 Administrator
- 2 Officials
- 7 Residents

**Sample Legislation:**
- Street cleaning ordinance
- Anti-littering law
- Youth development program
- And 3 more...

**Sample Civic Issues:**
- Potholes on main street
- Water supply interruption
- Waste management issues
- And 3 more...

## Troubleshooting

### "Connection refused" error
- **Cause**: XAMPP services not running
- **Solution**: Start Apache & MySQL from XAMPP Control Panel

### "Database not found" error
- **Cause**: Database not initialized
- **Solution**: Run `db_init.sql` via phpMyAdmin or command line

### Registration form not submitting
- **Cause**: Form validation or database connection issue
- **Solution**: Check browser console for errors, verify MySQL is running

### Login page shows blank screen
- **Cause**: PHP errors not displayed
- **Solution**: Check XAMPP error logs in `C:\xampp\apache\logs\error.log`

### Email already exists error during registration
- **Cause**: Email already registered in system
- **Solution**: Use different email or reset database with `db_init.sql`

## API Endpoints

All forms POST to their respective PHP handlers:

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `resident_register.php` | POST | Create resident account |
| `resident_login.php` | POST | Authenticate resident |
| `official_register.php` | POST | Create official account |
| `official_login.php` | POST | Authenticate official |
| `report_issue.php` | POST | Submit civic issue |
| `issue_status.php` | GET | Get issue details |
| `admin_dashboard.php` | POST | Create/manage users |
| `logout.php` | GET | Clear session |

## Security Features

✅ **Password Security**
- Minimum 6 characters required
- bcrypt hashing (PASSWORD_BCRYPT)
- Password confirmation on registration
- Never stored in plain text

✅ **SQL Injection Prevention**
- PDO prepared statements
- Input parameter binding
- No string concatenation in queries

✅ **Session Management**
- Session-based authentication
- Automatic timeout
- CSRF token support (in production)

✅ **Input Validation**
- Email format validation
- Phone number format check
- ID type & number validation
- Server-side validation (even if client-side bypassed)

## Development Notes

### Adding New Users via Admin Dashboard
1. Login as administrator
2. Navigate to "User Management"
3. Click "Add New User"
4. Select user type from dropdown
5. Fill all required fields
6. Click "Create User" button
7. User will be created with appropriate role

### Verifying Resident Accounts
1. Login as official
2. Go to "Pending Residents" section
3. Review resident information
4. Click "Verify" or "Reject"
5. Resident receives status update

### Creating Legislation Records
1. Login as official
2. Go to "Legislation Management"
3. Click "Add Legislation"
4. Enter title, description, date
5. Submit form
6. New legislation appears in public listing

## Performance Considerations

- Database indexes on `email` and `user_id` for fast lookups
- Session caching to reduce database queries
- Prepared statements prevent query compilation overhead
- No N+1 query problems in current implementation

## Future Enhancement Ideas

- ⭐ Email verification for new accounts
- ⭐ Password reset functionality
- ⭐ User profile editing
- ⭐ File uploads for civic issue attachments
- ⭐ Department assignment for officials
- ⭐ Issue assignment to officials
- ⭐ Notification system
- ⭐ Advanced search & filtering
- ⭐ Report generation (PDF/Excel)
- ⭐ Mobile app integration

## Support & Documentation

For issues or questions:
1. Check XAMPP error logs: `C:\xampp\apache\logs\error.log`
2. Review MySQL logs: `C:\xampp\mysql\data\error.log`
3. Check browser console (F12) for JavaScript errors
4. Verify database connection in `config.php`

## License

This is a prototype demonstration system for educational purposes.

## Version History

- **v1.0** - Initial prototype with registration, authentication, and civic issue tracking
- Database: 10 users, 6 legislation records, 6 civic issues
- Single-page application with role-based portals

---

**Last Updated**: December 2024
**System Status**: Production Ready ✅
