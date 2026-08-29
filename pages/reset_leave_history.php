<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Logger.php';
require_once __DIR__ . '/../config/db.php';

Auth::requirePermission('reset_leave_history');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: leave_history.php');
    exit;
}

Auth::verifyCsrf($_POST['csrf_token'] ?? null);

$action = trim((string) ($_POST['action'] ?? ''));
$db = getDB();

switch ($action) {
    case 'reset_individual':
        $personnelId = (int) ($_POST['personnel_id'] ?? 0);
        if ($personnelId <= 0) {
            $_SESSION['flash_error'] = 'Invalid personnel ID.';
            break;
        }

        // Get name for logging
        $stmt = $db->prepare("SELECT name, personal_number FROM personnel WHERE id = ?");
        $stmt->execute([$personnelId]);
        $person = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT COUNT(*) FROM personnel_leaves WHERE personnel_id = ?");
        $stmt->execute([$personnelId]);
        $count = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("DELETE FROM personnel_leaves WHERE personnel_id = ?");
        $stmt->execute([$personnelId]);

        Logger::log('reset_leave_history', $personnelId, [
            'name' => $person['name'] ?? 'Unknown',
            'personal_number' => $person['personal_number'] ?? '',
            'deleted_count' => $count,
        ]);

        $_SESSION['flash_success'] = "Leave history reset for {$person['name']} ({$count} records deleted).";
        break;

    case 'reset_all':
        $stmt = $db->query("SELECT COUNT(*) FROM personnel_leaves");
        $totalCount = (int) $stmt->fetchColumn();

        $db->exec("DELETE FROM personnel_leaves");

        Logger::log('reset_all_leave_history', null, [
            'deleted_count' => $totalCount,
        ]);

        $_SESSION['flash_success'] = "All leave history reset ({$totalCount} records deleted).";
        break;

    default:
        $_SESSION['flash_error'] = 'Invalid action.';
        break;
}

header('Location: leave_history.php');
exit;
