# Unit Personnel Management System: Project Brief

## Executive Overview
The **Unit Personnel Management System** is a dedicated web application for administrative units to maintain, search, audit, and print military and organizational personnel records. It replaces manual paperwork and disjointed spreadsheets with a secure, centralized database.

---

## Direct User Benefits (By Role)

### 1. For Commanding Officers & Executives
- **Instant Unit Readiness Overview**: View active unit strength, personnel on leave, hospital admissions (CMH), and training status at a glance from the dashboard.
- **Accurate Decision Support**: Filter personnel by cadre, course qualification, medical category, or firing/IPFT records to select members for deployments, UN missions, or special duties.
- **Data Integrity Guarantee**: Review and approve changes before they commit to official records.

### 2. For Administrative Clerks & Daily Operators
- **Fast Rosters & Parade State Printing**: Generate official muster rolls, leave lists, and duty rosters with custom headers and dates in seconds.
- **Custom Print Layouts**: Toggle specific columns (Blood Group, District, Phone Number, Appointment, NID) without modifying the main database or using external software.
- **Streamlined Workflow**: Input updates into a clean interface without worrying about breaking master database records.

### 3. For Training & Medical Officers
- **Fitness Tracking**: Monitor 4-cycle yearly plans, IPFT scores, RET, and Speed March results.
- **Medical Category Oversight**: Maintain up-to-date health categories and medical restrictions to avoid deploying unfit personnel.

### 4. For IT Staff & System Administrators
- **Granular Security Controls**: Define exact viewing and editing permissions per user role (`Superadmin`, `Admin`, `User`, `Operator`, `Daily`).
- **Complete Audit Trail**: Inspect detailed timestamps, IP addresses, and exact change diffs for every record modification.
- **Zero-Downtime Maintenance**: Execute database updates and migrations directly from the browser interface.
- **Integrated Backup System**: Generate full SQL database snapshots on demand for disaster recovery.

---

## Core System Features

| Feature | Description | Business Impact |
| :--- | :--- | :--- |
| **Comprehensive Dossier** | Stores basic info, service history, yearly cycles, family details, health status, courses, and emergency contacts. | Centralizes all personnel records in one searchable database. |
| **Leave Matrix Engine** | Tracks leave seniority queue, yearly reset cycles, multi-year historical logs, and permission-gated audit resets. | Provides full transparency for leave approvals and rotation planning. |
| **Two-Tier Approval System** | Operator edits go to a pending queue for administrative sign-off with side-by-side visual diffs. | Eliminates accidental data overwrites and unauthorized updates. |
| **Selective Print Engine** | Print records on standard paper sizes (A4, Legal, Letter) with optional columns and rank-hierarchical military seniority sorting. | Saves hours spent manually compiling and formatting print sheets. |
| **Multi-Criteria Search** | Filter by rank, unit, blood group, district, qualification status, and leave types. Filter panels stay open across queries. | Speeds up personnel lookups from minutes to seconds. |
| **Bulk Excel Operations** | Import new personnel or batch update existing files via formatted Excel spreadsheets. | Eliminates manual repetitive data entry during annual intakes. |
| **Automated Media Processing** | Built-in 400x400 photo cropping and validation on upload. | Maintains consistent, clean record cards without external photo editing. |

---

## Technical Specifications
- **Runtime:** PHP 8.0+
- **Database:** MySQL / MariaDB (Prepared statements, Foreign Key integrity)
- **Frontend:** Bootstrap 5, Tabler Icons, SweetAlert2, CSS3
- **File Processing:** PhpSpreadsheet & GD Image Processor
