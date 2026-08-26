<?php
declare(strict_types=1);

class AddPrintPermission implements MigrationInterface
{
    public function up(PDO $db): void
    {
        // Add Print permission
        $stmt = $db->prepare("INSERT IGNORE INTO permissions (name, description) VALUES ('print_records', 'Can print personnel records')");
        $stmt->execute();
        
        $permId = $db->lastInsertId();
        if (!$permId) {
            $stmt = $db->prepare("SELECT id FROM permissions WHERE name = 'print_records'");
            $stmt->execute();
            $permId = $stmt->fetchColumn();
        }
    }

    public function down(PDO $db): void
    {
        $stmt = $db->prepare("SELECT id FROM permissions WHERE name = 'print_records'");
        $stmt->execute();
        $permId = $stmt->fetchColumn();

        if ($permId) {
            $db->prepare("DELETE FROM role_permissions WHERE permission_id = ?")->execute([$permId]);
            $db->prepare("DELETE FROM permissions WHERE id = ?")->execute([$permId]);
        }
    }
}
