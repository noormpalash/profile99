<?php
declare(strict_types=1);

class AddLeaveSeniorityPermission implements MigrationInterface
{
    public function up(PDO $db): void
    {
        // Add Leave Seniority permission
        $stmt = $db->prepare("INSERT IGNORE INTO permissions (name, description) VALUES ('leave_seniority', 'Can view and access leave seniority state')");
        $stmt->execute();
        
        $stmt = $db->prepare("SELECT id FROM permissions WHERE name = 'leave_seniority'");
        $stmt->execute();
        $permId = $stmt->fetchColumn();

        if ($permId) {
            // Assign to techadmin, superadmin, admin by default
            $roles = $db->query("SELECT id FROM roles WHERE name IN ('techadmin', 'superadmin', 'admin')")->fetchAll(PDO::FETCH_COLUMN);
            $insertStmt = $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($roles as $roleId) {
                $insertStmt->execute([$roleId, $permId]);
            }
        }
    }

    public function down(PDO $db): void
    {
        $stmt = $db->prepare("SELECT id FROM permissions WHERE name = 'leave_seniority'");
        $stmt->execute();
        $permId = $stmt->fetchColumn();

        if ($permId) {
            $db->prepare("DELETE FROM role_permissions WHERE permission_id = ?")->execute([$permId]);
            $db->prepare("DELETE FROM permissions WHERE id = ?")->execute([$permId]);
        }
    }
}
