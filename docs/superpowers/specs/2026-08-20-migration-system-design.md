# App & DB Migration System Design

## Goal
Build migration system. Apply from web app. No data loss.

## Architecture
1. **DB Table**: `migrations` (id, filename, executed_at).
2. **Folder**: `database/migrations/`. Store PHP migration files.
3. **Engine**: `MigrationService.php`.
   - Scan folder.
   - Fetch applied from DB.
   - Run new.
   - Insert to DB.
4. **UI**: `pages/migrate.php` (or similar admin page). Button to "Run Migrations".

## Code Structure (PHP 8.0 strict mode)
- `MigrationInterface.php` -> `up()` and `down()`.
- Migration files format: `YYYY_MM_DD_HHMMSS_description.php`.
- Wrap in SQL transaction (if supported by DB engine).

## Caveman note
No CLI needed. All via web click. Data safe.
