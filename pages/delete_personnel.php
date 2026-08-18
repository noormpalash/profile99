<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Personnel.php';
Auth::requirePermission('delete_personnel');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    die('Method not allowed.');
}

Auth::verifyCsrf($_POST['csrf_token'] ?? null);

$id = (int)($_POST['id'] ?? 0);
if ($id) {
    if (Auth::hasPermission('approval') || Auth::hasPermission('auto_approval') || in_array(Auth::role(), ['admin', 'superadmin', 'techadmin'])) {
        require_once __DIR__ . '/../classes/ApprovalFormatter.php';
        $diffText = ApprovalFormatter::renderDiffText('delete', $id, '{}');
        Personnel::delete($id);
        require_once __DIR__ . '/../classes/Logger.php';
        Logger::log('delete', null, ['deleted_id' => $id, 'details' => $diffText]);
    } else {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO personnel_approvals (action_type, personnel_id, proposed_data, requested_by) VALUES ('delete', ?, '{}', ?)");
        $stmt->execute([$id, $_SESSION['user_id']]);
        
        require_once __DIR__ . '/../classes/Logger.php';
        require_once __DIR__ . '/../classes/ApprovalFormatter.php';
        $diffText = ApprovalFormatter::renderDiffText('delete', $id, '{}');
        Logger::log('delete', $id, ['details' => "Requested approval for deletion: " . $diffText]);
        // Set success message for the next page load (assuming dashboard or personnel page checks it, but since we have no global flash, we'll just redirect)
        // Without global flash, the user just gets redirected.
    }
}

header('Location: personnel.php');
exit;
