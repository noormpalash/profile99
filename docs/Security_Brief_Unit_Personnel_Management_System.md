# Security Brief: Unit Personnel Management System

**Target Audience:** Executive Leadership, Project Stakeholders, and Client Security Auditors  
**System Classification:** Internal Personnel & Records Administration System  
**Date:** October 2026  

---

## 1. System Architecture & Threat Model

The Unit Personnel Management System stores and manages service records, personnel rosters, medical categories, and leave schedules. The application employs defense-in-depth architecture across the presentation, application, and database layers to safeguard Personally Identifiable Information (PII) against unauthorized access, tampering, and data exfiltration.

```
[ Client Browser ]
        │  HTTPS (TLS 1.3) + Hardened Security Headers (CSP, HSTS)
        ▼
[ Application Layer (PHP 8.x) ]
   ├── Authentication Engine: Rate Limiting, Bcrypt (cost 12), Session Tokens
   ├── Authorization Gate: Hierarchical RBAC + Granular Permissions
   ├── Input Validation: CSRF Validation, Type Casting, Context-Aware Escaping
   └── File Gateway: MIME Sniffing Check, Image Re-encoding, Execution Lock (.htaccess)
        │  PDO Parameterized Queries (Zero Inline Concatenation)
        ▼
[ Database Layer (MySQL / MariaDB) ]
   └── Role-isolated storage, foreign-key consistency, restricted filesystem privileges
```

---

## 2. Authentication & Session Security

The system enforces strict identity verification standards before granting access to internal records:

- **Password Hashing:** Uses `bcrypt` with dynamic salt generation. The system never stores plain-text credentials.
- **Complexity Enforcement:** Passwords require a minimum of eight characters, including uppercase, lowercase, numeric, and special characters.
- **Brute-Force Lockout:** Tracks failed login attempts per username and IP address. The engine locks an account for 15 minutes after five consecutive failed attempts.
- **Session Token Validation:** Generates a cryptographic 32-byte session token on login and checks this token on every request against the active session stored in the database. When a user logs in from a new device, the system terminates older active sessions.
- **Inactivity Timeout:** Destroys idle sessions after 30 minutes of inactivity.
- **Cookie Flags:** All session cookies enforce `HttpOnly`, `SameSite=Lax`, and `Secure` attributes over HTTPS, preventing script-based cookie theft.

---

## 3. Access Control & Change Governance

Access rights follow the Principle of Least Privilege:

- **Granular Role-Based Access Control (RBAC):** Users operate under defined roles (`Superadmin`, `Admin`, `User`, `Operator`, `Daily`). Each role maps to explicit database permissions.
- **Separation of Duties (Two-Tier Approval System):** Operational clerks and data-entry staff submit profile modifications to a staging queue. Changes remain non-authoritative until an authorized administrator verifies the visual diff and approves the update.
- **Privilege Boundaries:** Administrative functions (role management, audit logs, system configurations, and database backups) enforce strict permission checks at the script entry point and return HTTP 403 upon violation.

---

## 4. Injection & Client-Side Attacks Defense

- **SQL Injection Prevention:** All database transactions run through PDO prepared statements with bound parameters. The application rejects raw SQL concatenation.
- **Cross-Site Scripting (XSS):** All dynamic browser outputs use UTF-8 `htmlspecialchars` escaping with `ENT_QUOTES`.
- **Cross-Site Request Forgery (CSRF):** Every state-altering HTTP request (`POST`) requires a cryptographically random, per-session CSRF token. The server rejects requests with mismatched or absent tokens.
- **Security Headers:** Every HTTP response enforces defensive headers:
  - `Content-Security-Policy`: Restricts script and stylesheet execution to trusted origins.
  - `X-Frame-Options: DENY`: Prevents UI redressing and clickjacking attacks.
  - `X-Content-Type-Options: nosniff`: Prevents MIME-type sniffing exploits.
  - `Referrer-Policy: strict-origin-when-cross-origin`: Restricts metadata leakage to external domains.
  - `Permissions-Policy`: Disables camera, microphone, and geolocation APIs.

---

## 5. File Upload & Storage Isolation

The application processes official ID photographs and personnel documents through an isolated media pipeline:

- **Execution Prevention:** The upload directory contains an Apache configuration (`.htaccess`) that blocks directory indexing (`Options -Indexes`) and explicitly forbids script execution (`.php`, `.phtml`, `.cgi`, `.sh`).
- **MIME & Dimension Verification:** Uploaded files undergo MIME-type inspection and re-encoding via GD/PhpSpreadsheet before the server writes them to disk.
- **Filename Sanitization:** Uploaded files are renamed using randomized hashes, preventing directory traversal and local file inclusion.

---

## 6. Audit Logging & Disaster Recovery

- **Activity Logs:** The application records user logins, credential changes, record updates, approvals, and configuration changes. Each log entry stores the user ID, action type, IP address, and timestamp.
- **Backup Controls:** Database backup and restore operations require superadministrator clearance.
- **Search Engine Isolation:** Global `robots.txt` and `<meta name="robots" content="noindex, nofollow, noarchive">` tags prevent external web indexers from discovering or caching internal pages.

---

## Summary Matrix

| Defense Vector | Implementation Mechanism | Status |
| :--- | :--- | :--- |
| **Credential Storage** | `bcrypt` hash + salt | **Enforced** |
| **Brute-Force Defense** | 5-attempt rate limit, 15-minute lockout | **Enforced** |
| **Session Fixation** | ID regeneration on login + single active device token | **Enforced** |
| **SQL Injection** | PDO prepared statements with strict parameter binding | **Enforced** |
| **CSRF Protection** | Unique cryptographic tokens on all `POST` requests | **Enforced** |
| **Clickjacking** | `X-Frame-Options: DENY` | **Enforced** |
| **File Upload Exploits** | Script execution blocked via `.htaccess` | **Enforced** |
| **Audit Compliance** | Timestamped, IP-tagged action logs | **Enforced** |
