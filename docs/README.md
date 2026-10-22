# C Cube Event Management System 🚀

A modern, animated, high-performance full-stack event and competition management platform engineered for hackathons, coding contests, and technical symposiums.

---

## 🌟 Features Overview

### 🎨 Visual & Frontend Experience
- **Futuristic Dark Cyber Theme**: Deep navy backgrounds (`#0a0b18`), glowing indigo/purple accents, and glassmorphism styling.
- **Micro-Animations & Smooth Scrolling**: Scroll reveals, interactive hover tilts, ripple effects on clicks, and animated counters that reflect real database numbers.
- **Dynamic Event Discovery**: Live category filtering, keyword search, status badges, and capacity bars.
- **Interactive Timelines**: Step-by-step registration status tracking from registration to payment verification and final approval.
- **Accessibility & Responsiveness**: Fluid layouts for desktop, tablet, and mobile with dedicated touch-friendly drawers and reduced-motion support.

### 🛡️ Authentication & Access Control
- **Dual Dashboard Architecture**: Separate role-based portals for **Participants** and **Administrators**.
- **Brute Force Protection**: IP and email-based rate limiting on login attempts.
- **Session Security**: Session ID regeneration on authentication, strict session timeouts, and secure session flags.
- **Password Safety**: High-entropy bcrypt hashing (`PASSWORD_BCRYPT` with cost factor 12) via `password_hash()` and `password_verify()`.

### 🏆 Competitions & Registration Engine
- **Event Lifecycle**: Complete control over Draft, Published, Closed, and Cancelled event states.
- **Flexible Team Models**: Supports individual entries or team formations with dynamic member registration (minimum & maximum team sizes enforced).
- **Duplicate Registration Guard**: Prevents participants from registering twice for the same event via database unique constraints and server-side checks.
- **Capacity Management**: Real-time seat tracking with automatic cutoff once capacity or deadlines are reached.

### 💳 Transparent Payment Workflow
- **Manual Verification Pipeline**: Designed for offline/bank transfers without third-party vendor lock-in.
- **Tamper-Proof Verification**: Payment amounts are validated against actual event fees in MySQL—never trusting frontend numbers.
- **Receipt Inspection**: Secure upload of receipts (`JPG`, `PNG`, `WebP`, `PDF`) with randomized filenames and MIME type validation.
- **Protected Receipt Access**: Files stored outside public execution directories; receipts can **only** be viewed by the registration owner or administrators.
- **Administrative Audit Trail**: Every payment verification and rejection decision is logged with timestamps, reasons, and administrator IDs.

### ⚙️ Administration & Reporting
- **Real-Time Analytics**: Participant counts, live revenue metrics, and approval queues computed directly from MySQL.
- **Comprehensive Management**: CRUD operations for Events, Categories, Registrations, Payments, and Participants.
- **CSV Data Export**: Single-click export of registration rosters with custom filters for offline judging and check-in desks.
- **Platform Customization**: Update bank accounts, contact information, social links, and toggle maintenance mode directly from the admin UI.

---

## 🛠️ Technology Stack

| Layer | Technologies |
|---|---|
| **Frontend** | HTML5, CSS3 (Custom Design System, Glassmorphism), Vanilla JavaScript ES6+ |
| **Backend** | PHP 8.0+ (Vanilla, Object-Oriented PDO, Strict Types) |
| **Database** | MySQL 5.7+ / MariaDB 10.4+ |
| **Environment**| XAMPP / WampServer / LAMP stack with Apache & phpMyAdmin |
| **Fonts & Icons** | Google Fonts (*Inter*, *Space Grotesk*), Font Awesome 6 |

---

## 📂 Project Directory Structure

```text
ccube-event-management/
├── index.php                      # Homepage (Hero, Live Stats, Featured Events, CTA)
├── events.php                     # Public event listing, search & category filters
├── event-details.php              # Individual competition detail & registration form
├── register.php                   # Participant registration with CSRF & duplicate check
├── login.php                      # Secure login with rate limiting & session regeneration
├── logout.php                     # Secure session termination
├── register-event.php             # POST handler for event registration & team members
├── view-receipt.php               # Secure authorized-only receipt viewer
├── config/
│   └── database.php               # PDO database connection & error handling
├── includes/
│   ├── header.php                 # Public header, navigation, and auth dropdown
│   ├── footer.php                 # Public footer, branding, and script inclusion
│   ├── auth.php                   # Authentication guards, sessions, and RBAC
│   ├── functions.php              # Utility functions, formatting, audit logs, uploads
│   ├── csrf.php                   # Cryptographic CSRF token generation & validation
│   └── event_card.php             # Reusable event card partial
├── assets/
│   ├── css/
│   │   ├── style.css              # Core typography, buttons, forms, navbar, footer
│   │   ├── animations.css         # Keyframes, glows, transitions, and hover states
│   │   └── dashboard.css          # Participant & admin dashboard layouts, tables, cards
│   ├── js/
│   │   ├── main.js                # Core UI interactivity, counter observer, toast engine
│   │   ├── animations.js          # 3D card tilts, mouse glow tracking, button ripple
│   │   └── dashboard.js           # Modal handlers, clipboard helpers, sidebar drawer
│   └── images/                    # Visual assets
├── uploads/
│   ├── .htaccess                  # Execution barrier preventing PHP uploads from running
│   ├── event-banners/             # Uploaded competition banners
│   └── payment-receipts/          # Uploaded payment slips
├── participant/
│   ├── dashboard.php              # Participant overview, stats, recent registrations
│   ├── my-registrations.php       # Registration history with status timelines
│   ├── payment.php                # Bank instructions & payment receipt submission
│   ├── profile.php                # Personal details & password change
│   └── notifications.php          # Participant alerts & status update feed
├── admin/
│   ├── admin-header.php           # Admin panel layout header, badges, and sidebar
│   ├── admin-footer.php           # Admin panel footer and scripts
│   ├── dashboard.php              # Live metrics, quick actions, pending approvals
│   ├── events.php                 # Events listing, filters, and delete actions
│   ├── event-create.php           # Event creation form with image upload & team limits
│   ├── event-edit.php             # Event editing form & state updates
│   ├── categories.php             # Competition tracks & categories CRUD
│   ├── registrations.php          # Review applications, inspect team rosters, approve/reject
│   ├── payments.php               # Review transaction IDs, verify receipts, audit trail
│   ├── participants.php           # Participant user accounts, ban/unban controls
│   ├── reports.php                # Aggregate analytics & protected CSV report download
│   ├── settings.php               # Bank account details, branding, contact info
│   └── audit-logs.php             # Chronological audit ledger of administrative actions
├── api/
│   ├── events.php                 # JSON endpoint for dynamic event search
│   └── notifications.php          # JSON endpoint for async notifications polling
├── database/
│   ├── schema.sql                 # Complete MySQL DDL schema with tables and indexes
│   └── seed.sql                   # Sample competitions, categories, settings, test accounts
└── docs/
    └── README.md                  # Complete documentation and deployment guide
```

---

## 🚀 Setup & Installation Guide (XAMPP)

### Prerequisites
1. **XAMPP** (with PHP 8.0+ and MySQL/MariaDB) installed on your computer.
2. Web browser (Chrome, Edge, Firefox).

---

### Step 1: Place the Project in XAMPP `htdocs`
Move or copy the `ccube-event-management` folder into your XAMPP web root directory:
```text
C:\xampp\htdocs\ccube-event-management
```

---

### Step 2: Start Apache and MySQL
1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.
4. Ensure both services show green status indicators.

---

### Step 3: Create Database & Import SQL
1. Open your browser and navigate to phpMyAdmin:
   ```text
   http://localhost/phpmyadmin/
   ```
2. Click on **Import** in the top navigation bar.
3. Choose the schema file:
   ```text
   C:\xampp\htdocs\ccube-event-management\database\schema.sql
   ```
4. Click **Go** (or **Import**) to execute the DDL script. This will automatically create the database `ccube_event_management` and all 9 tables.
5. Next, click on the **Import** tab again and select the seed file:
   ```text
   C:\xampp\htdocs\ccube-event-management\database\seed.sql
   ```
6. Click **Go** to import demo categories, sample competitions, bank instructions, and test accounts.

---

### Step 4: Configure Database Connection
If you are using default XAMPP settings (`root` with no password on port 3306), the connection is already configured in `config/database.php`.

If your MySQL configuration uses a custom password or port, edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'ccube_event_management');
define('DB_USER', 'root');
define('DB_PASS', 'your_password_here');
```

---

### Step 5: Launch the Website!
Visit the application in your browser:
👉 **[http://localhost/ccube-event-management/](http://localhost/ccube-event-management/)**

---

## 🔑 Demo Accounts for Local Testing

| Role | Email Address | Password | Portal URL |
|---|---|---|---|
| **Administrator** | `admin@ccube.dev` | `Admin@CCube2025` | [http://localhost/ccube-event-management/login.php](http://localhost/ccube-event-management/login.php) |
| **Participant** | `ali@example.com` | `Test@1234` | [http://localhost/ccube-event-management/login.php](http://localhost/ccube-event-management/login.php) |

> ⚠️ **Important Security Note**: The default administrator credentials above are provided strictly for local development and demonstration. Change the administrator password immediately via `admin/settings.php` or `participant/profile.php` before any network or production deployment.

---

## 🧪 Testing the Complete Workflow

### 1. Participant Registration & Login
1. Navigate to `http://localhost/ccube-event-management/register.php`.
2. Fill out the registration form with your name, unique email, phone number, institution, and password.
3. After registration, log in at `login.php`. You will be directed to your **Participant Dashboard**.

### 2. Competition Registration
1. Click **Explore Events** in the navbar or sidebar.
2. Select an event (e.g. **HackCube 48** or **CodeStorm 2025**).
3. If it is a team event, provide your team name and add team members.
4. Submit the registration. You will receive an immediate confirmation with a unique reference number (e.g., `REG-2025-XXXX`).

### 3. Payment Submission
1. From **My Registrations**, click **Submit Payment**.
2. Note the configured bank account details (HBL / Account Title / IBAN).
3. Enter your bank transfer Transaction Reference Number and upload a screenshot/slip receipt (`JPG`, `PNG`, or `PDF`).
4. Click **Submit Payment**. The registration status changes to `Pending Verification`.

### 4. Admin Review & Verification
1. Log in with the Administrator account (`admin@ccube.dev` / `Admin@CCube2025`).
2. Go to **Payments** from the admin sidebar.
3. View the submitted payment record, click **View Receipt** to inspect the receipt in a secure viewer tab.
4. Click **Verify**. Check the box to automatically approve the registration and confirm.
5. Return to the participant account: the status now reflects `Approved` with a green badge and notification alert!

### 5. CSV Report Export
1. In the Admin Panel, navigate to **Reports & Export**.
2. Filter registrations by event or status.
3. Click **Download CSV Export** to download the clean spreadsheet for event day check-in.

---

## 🔒 Security Best Practices Implemented

- **Prepared SQL Statements**: 100% of database operations use PDO prepared statements with parameterized inputs, mitigating SQL injection risks.
- **CSRF Tokens**: All state-changing POST forms incorporate cryptographic random tokens verified before execution.
- **XSS Prevention**: All dynamic user-supplied data is escaped with `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
- **Upload Hardening**: Files are validated by MIME type via `finfo`, assigned randomized 32-character hexadecimal names, and stored in a protected folder with script execution disabled by `.htaccess`.
- **Private Receipt Streaming**: Direct static access to payment receipts is blocked; only the respective registration owner or admins can stream files through `view-receipt.php`.
- **Rate-Limited Logins**: Failed login attempts are recorded and rate-limited by IP address and email to protect against brute-force attacks.

---

## 💬 Support & Inquiries
For questions, support, or feedback:
- **Email**: hello@ccube.dev
- **Campus**: FAST NUCES Lahore / C Cube Innovation Hub
