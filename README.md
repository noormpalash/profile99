# Unit Personnel System

A robust, granular permissions-based personnel management system built with PHP 8.x and MySQL.

## 1. Features
- **Granular Permissions:** Custom roles with specific access control (e.g., `techadmin`, `superadmin`, `admin`, `user`).
- **Approval Workflow:** Edits by non-admin users go to a pending queue for approval.
- **Activity Logs:** Tracks all CRUD operations and logins.
- **Bulk Import/Export:** Excel-based data ingestion using PhpSpreadsheet.
- **Dynamic App Settings:** Customizable app title, name, and logo via UI.
- **Comprehensive Profiles:** Tracks Cadres, Courses, MOQs, Leaves, Social Links, and more.

---

## 2. Local Setup (XAMPP / WAMP)

1. **Install Dependencies:**
   Ensure Composer is installed. In the project root, run:
   ```bash
   composer install
   ```
2. **Database Setup:**
   - Open `http://localhost/phpmyadmin`
   - Create a database (e.g., `army_personnel_db`).
   - Import `database.sql` into the new database.
3. **Configure Connection:**
   Edit `config/db.php`:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_NAME', 'army_personnel_db');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
4. **Access App:**
   Go to `http://localhost/your-folder-name/pages/login.php`

---

## 3. Shared Hosting Deployment

1. **Upload Files:**
   Upload all files via FTP or File Manager to your `public_html` directory (or a subdirectory). **Make sure the `vendor/` folder is uploaded** (run `composer install` locally first if SSH is unavailable).
2. **Database Setup:**
   - Create a MySQL Database and User in cPanel.
   - Import `database.sql` via phpMyAdmin.
3. **Configure Connection:**
   Edit `config/db.php` with your live database credentials:
   ```php
   define('DB_HOST', 'localhost'); // Often localhost on shared hosting
   define('DB_NAME', 'cpaneluser_personneldb');
   define('DB_USER', 'cpaneluser_dbuser');
   define('DB_PASS', 'StrongPassword123!');
   ```
4. **Permissions:**
   Ensure the following directories have `755` permissions so PHP can upload files:
   - `uploads/`
   - `uploads/personnel/`

---

## 4. Default Login

- **Username:** `superadmin`
- **Password:** The password hash shipped in `database.sql` is a placeholder. Generate your own before first use:
  ```php
  echo password_hash('YourNewPassword123', PASSWORD_DEFAULT);
  ```
  Replace the `password_hash` value for the `superadmin` row in the `users` table.

---

## 5. Folder Structure
```text
/
├── assets/          # CSS, JS, Vendor UI assets (Bootstrap, Tabler)
├── classes/         # Core Logic (Auth, Personnel, LookupManager, Logger)
├── config/          # db.php configuration
├── includes/        # Shared Header, Footer, Sidebar
├── pages/           # All application routes (Dashboard, Login, Profiles, etc.)
├── uploads/         # App Logos and Personnel Photos
├── vendor/          # Composer dependencies (PhpSpreadsheet)
├── database.sql     # Database schema and initial data
└── composer.json    # Dependency definitions
```
