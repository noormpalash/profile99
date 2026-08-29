<?php
declare(strict_types=1);

class AddLeaveHistoryPermission implements MigrationInterface
{
    public function up(PDO $db): void
    {
        // Add leave_history permission
        $db->prepare("INSERT IGNORE INTO permissions (name, description) VALUES ('leave_history', 'Can view leave history page')")
            ->execute();

        // Add reset_leave_history permission
        $db->prepare("INSERT IGNORE INTO permissions (name, description) VALUES ('reset_leave_history', 'Can reset individual or all leave records')")
            ->execute();

        // Fetch permission IDs
        $permIds = [];
        foreach (['leave_history', 'reset_leave_history'] as $permName) {
            $stmt = $db->prepare("SELECT id FROM permissions WHERE name = ?");
            $stmt->execute([$permName]);
            $id = $stmt->fetchColumn();
            if ($id) {
                $permIds[] = $id;
            }
        }

        // Assign to techadmin, superadmin, admin
        $roles = $db->query("SELECT id FROM roles WHERE name IN ('techadmin', 'superadmin', 'admin')")->fetchAll(PDO::FETCH_COLUMN);
        $insertStmt = $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($roles as $roleId) {
            foreach ($permIds as $permId) {
                $insertStmt->execute([$roleId, $permId]);
            }
        }
    }

    public function down(PDO $db): void
    {
        foreach (['leave_history', 'reset_leave_history'] as $permName) {
            $stmt = $db->prepare("SELECT id FROM permissions WHERE name = ?");
            $stmt->execute([$permName]);
            $permId = $stmt->fetchColumn();

            if ($permId) {
                $db->prepare("DELETE FROM role_permissions WHERE permission_id = ?")->execute([$permId]);
                $db->prepare("DELETE FROM permissions WHERE id = ?")->execute([$permId]);
            }
        }
    }
}
