<?php
declare(strict_types=1);

class AddSystemUpdatePermission implements MigrationInterface
{
    public function up(PDO $db): void
    {
        // Add permission
        $stmt = $db->prepare("INSERT IGNORE INTO permissions (name, description) VALUES ('system_update', 'Can upload and run system updates via ZIP')");
        $stmt->execute();
        
        $permId = $db->lastInsertId();
        if (!$permId) {
            $stmt = $db->prepare("SELECT id FROM permissions WHERE name = 'system_update'");
            $stmt->execute();
            $permId = $stmt->fetchColumn();
        }

        if ($permId) {
            // Assign to superadmin and techadmin if those roles exist
            $roles = $db->query("SELECT id FROM roles WHERE name IN ('superadmin', 'techadmin')")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($roles as $roleId) {
                $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)")
                   ->execute([$roleId, $permId]);
            }
        }
    }

    public function down(PDO $db): void
    {
        $stmt = $db->prepare("SELECT id FROM permissions WHERE name = 'system_update'");
        $stmt->execute();
        $permId = $stmt->fetchColumn();

        if ($permId) {
            $db->prepare("DELETE FROM role_permissions WHERE permission_id = ?")->execute([$permId]);
            $db->prepare("DELETE FROM permissions WHERE id = ?")->execute([$permId]);
        }
    }
}
