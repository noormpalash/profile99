# Changelog & Version History

All notable changes to the **Unit Personnel Management System** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.4.0] - 2026-08-28

### Added
- **Selective Column Printing**: "Print with Info" option allowing custom field selection (Blood Group, District, Phone Number, Appointment, Unit, Cadre, Platoon, NID).
- **Dynamic Print Layout**: Automatic portrait/landscape orientation switching based on active column count.
- **Seniority by Personal No Toggle**: Checkbox in print dialog to toggle sorting by military Personal Number (BJO precedence).

### Fixed
- **Persistent Advanced Search Panel**: Clicking "Clear" or "Clear filters" retains the collapsible search panel in open state instead of collapsing.

### Changed
- **Unified Print Engine**: Replaced split print buttons with single adaptive modal and shared `executePrint` engine.

---

## [1.3.0] - 2026-08-26

### Added
- **Database Backup & Restore**: `DatabaseBackupService` for snapshot generation and disaster recovery.
- **Granular Permission Nodes**: Added `print_records` and `database_management` system permissions.
- **Print Formatting Options**: Added options for custom page size (A4, Legal, Letter), custom heading/subheading, and date insertion.
- **Active Leave Remarks**: Auto-populates active leave duration in the print remarks column.

---

## [1.2.0] - 2026-08-20

### Added
- **Web Migration Runner**: `MigrationService` and `MigrationInterface` for executing structured SQL/PHP migrations via web UI.
- **Auto-Updater System**: `UpdaterService` for checking, downloading, and applying application updates.
- **Bulk Excel Import/Export**: `download_template.php` supporting structured batch personnel ingestion via PhpSpreadsheet.

---

## [1.1.0] - 2026-08-15

### Added
- **Approval Workflow Queue**: Non-admin edits captured in pending state with diff inspector (`approvals.php`, `ApprovalFormatter.php`).
- **Centralized Activity Logger**: Full audit trail recording authentication, profile changes, and system operations (`Logger.php`, `logs.php`).
- **Dynamic App Branding**: Configurable application name, page title, and custom logo upload via `AppSettings.php`.

---

## [1.0.0] - 2026-08-01

### Added
- **Core Personnel CRUD**: Profile management with automated 400x400 GD photo resizing and format validation.
- **Role-Based Access Control (RBAC)**: Multi-tier permission model supporting Superadmin, Admin, User, Operator, and Daily roles.
- **Lookup Management**: Dynamic configuration for Ranks, Units, Cadres, Platoons, Blood Groups, Courses, MOQs, and Medical Categories.
- **Comprehensive Profile Sections**:
  - Service history & retirement tracking
  - IPFT, RET, and Speed March records
  - Four-cycle yearly plan tracker
  - Family & living status management
  - Medical, health category, and disease tracking
  - Social media handles & custom notes
  - Leave ledger and date status tracking
- **Executive Dashboard**: Real-time personnel summary, status distributions, and active leave tracking.
