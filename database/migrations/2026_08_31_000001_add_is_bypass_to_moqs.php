<?php
declare(strict_types=1);

class AddIsBypassToMoqs implements MigrationInterface
{
    public function up(PDO $db): void
    {
        // Add is_bypass to moqs if not exists
        $colMoqs = $db->query("SHOW COLUMNS FROM moqs LIKE 'is_bypass'")->fetch();
        if (!$colMoqs) {
            $db->exec("ALTER TABLE moqs ADD COLUMN is_bypass TINYINT(1) NOT NULL DEFAULT 0");
        }

        // Add is_bypass to personnel_moqs if not exists
        $colPersonnelMoqs = $db->query("SHOW COLUMNS FROM personnel_moqs LIKE 'is_bypass'")->fetch();
        if (!$colPersonnelMoqs) {
            $db->exec("ALTER TABLE personnel_moqs ADD COLUMN is_bypass TINYINT(1) NOT NULL DEFAULT 0");
        }
    }

    public function down(PDO $db): void
    {
        $colMoqs = $db->query("SHOW COLUMNS FROM moqs LIKE 'is_bypass'")->fetch();
        if ($colMoqs) {
            $db->exec("ALTER TABLE moqs DROP COLUMN is_bypass");
        }

        $colPersonnelMoqs = $db->query("SHOW COLUMNS FROM personnel_moqs LIKE 'is_bypass'")->fetch();
        if ($colPersonnelMoqs) {
            $db->exec("ALTER TABLE personnel_moqs DROP COLUMN is_bypass");
        }
    }
}
