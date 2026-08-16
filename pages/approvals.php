<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Personnel.php';
require_once __DIR__ . '/../classes/LookupManager.php';
require_once __DIR__ . '/../classes/ApprovalFormatter.php';
require_once __DIR__ . '/../config/db.php';

Auth::requireLogin();
if (!Auth::hasPermission('approval') && !Auth::hasAnyPermission(['admin', 'superadmin', 'techadmin'])) {
    http_response_code(403);
    die('Access denied.');
}

$withSidebar = true;
$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf($_POST['csrf_token'] ?? null);
    $approvalId = (int)($_POST['approval_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($approvalId && in_array($action, ['approve', 'reject'])) {
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("SELECT pa.*, u.name AS requester_name FROM personnel_approvals pa LEFT JOIN users u ON pa.requested_by = u.id WHERE pa.id = ? AND pa.status = 'pending' FOR UPDATE");
            $stmt->execute([$approvalId]);
            $req = $stmt->fetch();

            if (!$req) {
                throw new Exception("Request not found or already processed.");
            }

            require_once __DIR__ . '/../classes/Logger.php';

            if ($action === 'approve') {
                $data = json_decode($req['proposed_data'], true) ?: [];
                $targetId = $req['personnel_id'];
                
                require_once __DIR__ . '/../classes/ApprovalFormatter.php';
                $diffText = ApprovalFormatter::renderDiffText($req['action_type'], $targetId, $req['proposed_data']);

                $permsStmt = $db->prepare("SELECT p.name FROM permissions p JOIN role_permissions rp ON p.id = rp.permission_id JOIN users u ON u.role_id = rp.role_id WHERE u.id = ?");
                $permsStmt->execute([$req['requested_by']]);
                $requesterPerms = $permsStmt->fetchAll(PDO::FETCH_COLUMN);

                if ($req['action_type'] === 'add') {
                    $targetId = Personnel::create($data, [], $requesterPerms);
                } elseif ($req['action_type'] === 'edit') {
                    Personnel::update($req['personnel_id'], $data, null, $requesterPerms);
                } elseif ($req['action_type'] === 'delete') {
                    Personnel::delete($req['personnel_id']);
                }
                
                $status = 'approved';
                $success = "Request approved successfully.";
                Logger::log('approve', $targetId, ['approval_id' => $approvalId, 'type' => $req['action_type'], 'requested_by' => $req['requester_name'], 'details' => $diffText]);
            } else {
                require_once __DIR__ . '/../classes/ApprovalFormatter.php';
                $diffText = ApprovalFormatter::renderDiffText($req['action_type'], $req['personnel_id'], $req['proposed_data']);

                $status = 'rejected';
                $success = "Request rejected.";
                Logger::log('reject', $req['personnel_id'], ['approval_id' => $approvalId, 'type' => $req['action_type'], 'requested_by' => $req['requester_name'], 'details' => $diffText]);
            }

            $update = $db->prepare("UPDATE personnel_approvals SET status = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?");
            $update->execute([$status, $_SESSION['user_id'], $approvalId]);

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = "Error processing request: " . $e->getMessage();
        }
    }
}

// Fetch pending approvals
$stmt = $db->query("SELECT pa.*, u.name AS requester_name, p.name AS personnel_name, p.personal_number 
                    FROM personnel_approvals pa 
                    JOIN users u ON pa.requested_by = u.id 
                    LEFT JOIN personnel p ON pa.personnel_id = p.id 
                    WHERE pa.status = 'pending' 
                    ORDER BY pa.requested_at ASC");
$approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="row">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10">
    <div class="page-header mb-4">
      <div>
        <h4>Pending Approvals</h4>
        <div class="text-muted" style="font-size: 0.85rem;">Review changes requested by operators and users</div>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Action</th>
                <th>Target Personnel</th>
                <th>Requested By</th>
                <th>Date</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($approvals)): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">No pending approvals.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($approvals as $a): ?>
                  <tr>
                    <td>
                      <?php 
                        $badgeClass = ['add' => 'bg-success', 'edit' => 'bg-warning text-dark', 'delete' => 'bg-danger'][$a['action_type']];
                        $actionName = strtoupper($a['action_type']);
                      ?>
                      <span class="badge <?= $badgeClass ?>"><?= $actionName ?></span>
                    </td>
                    <td>
                      <?php if ($a['action_type'] === 'add'): ?>
                        <?php 
                          $data = json_decode($a['proposed_data'], true); 
                          echo htmlspecialchars($data['name'] ?? 'Unknown') . " (" . htmlspecialchars($data['personal_number'] ?? 'N/A') . ")";
                        ?>
                      <?php else: ?>
                        <?= htmlspecialchars($a['personnel_name'] . ' (' . $a['personal_number'] . ')') ?>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($a['requester_name']) ?></td>
                    <td><?= date('d M Y, h:i A', strtotime($a['requested_at'])) ?></td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#details-<?= $a['id'] ?>">Details</button>
                      <form method="post" class="d-inline ms-1">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="approval_id" value="<?= $a['id'] ?>">
                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                      </form>
                    </td>
                  </tr>
                  <tr class="collapse" id="details-<?= $a['id'] ?>">
                    <td colspan="5" class="bg-light">
                      <div class="p-3">
                        <?= ApprovalFormatter::renderDiff($a['action_type'], $a['personnel_id'] ?? 0, $a['proposed_data'], $a['requested_by'] ?? null) ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
