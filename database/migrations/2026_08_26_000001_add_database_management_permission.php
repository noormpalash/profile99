<?php
declare(strict_types=1);

class AddDatabaseManagementPermission implements MigrationInterface
{
    public function up(PDO $db): void
    {
        $stmt = $db->prepare("INSERT IGNORE INTO permissions (name, description) VALUES ('database_management', 'Can download and restore database backups')");
        $stmt->execute();
        
        $permId = $db->lastInsertId();
        if (!$permId) {
            $stmt = $db->prepare("SELECT id FROM permissions WHERE name = 'database_management'");
            $stmt->execute();
            $permId = $stmt->fetchColumn();
        }

        if ($permId) {
            // Assign to superadmin and techadmin
            $roles = $db->query("SELECT id FROM roles WHERE name IN ('superadmin', 'techadmin')")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($roles as $roleId) {
                $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)")
                   ->execute([$roleId, $permId]);
            }
        }
    }

    public function down(PDO $db): void
    {
        $stmt = $db->prepare("SELECT id FROM permissions WHERE name = 'database_management'");
        $stmt->execute();
        $permId = $stmt->fetchColumn();

        if ($permId) {
            $db->prepare("DELETE FROM role_permissions WHERE permission_id = ?")->execute([$permId]);
            $db->prepare("DELETE FROM permissions WHERE id = ?")->execute([$permId]);
        }
    }
}
