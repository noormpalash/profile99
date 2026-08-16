<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/db.php';
Auth::requirePermission('view_logs');

$withSidebar = true;
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_logs') {
    Auth::requirePermission('reset_logs');
    Auth::verifyCsrf($_POST['csrf_token'] ?? null);
    $db->exec("TRUNCATE TABLE activity_logs");
    
    require_once __DIR__ . '/../classes/Logger.php';
    Logger::log('delete', null, ['details' => 'Activity logs reset']);
    header('Location: logs.php?reset=success');
    exit;
}

$usersStmt = $db->query("SELECT id, name FROM users ORDER BY name");
$allUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

$whereClauses = [];
$params = [];

if (!empty($_GET['user_id'])) {
    $whereClauses[] = "al.user_id = ?";
    $params[] = $_GET['user_id'];
}
if (!empty($_GET['date'])) {
    $whereClauses[] = "DATE(al.created_at) = ?";
    $params[] = $_GET['date'];
}

$whereSql = "";
if (!empty($whereClauses)) {
    $whereSql = "WHERE " . implode(" AND ", $whereClauses);
}

$stmt = $db->prepare("
    SELECT al.*, u.name AS user_name, p.name AS target_name, p.personal_number
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    LEFT JOIN personnel p ON al.target_personnel_id = p.id
    $whereSql
    ORDER BY al.created_at DESC
    LIMIT 500
");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="row">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10">
    <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
      <div class="alert alert-success">Activity logs have been successfully reset.</div>
    <?php endif; ?>
    
    <div class="page-header mb-4 d-flex justify-content-between align-items-center">
      <div>
        <h4>Activity Logs</h4>
        <div class="text-muted" style="font-size: 0.85rem;">Recent user activities. Every log will remain for 29 days.</div>
      </div>
      <?php if (Auth::hasPermission('reset_logs')): ?>
        <form method="post" onsubmit="return confirm('Are you sure you want to delete all activity logs? This cannot be undone.');">
            <?= Auth::csrfField() ?>
            <button type="submit" name="action" value="reset_logs" class="btn btn-danger btn-sm">
                <i class="ti ti-trash"></i> Reset Logs
            </button>
        </form>
      <?php endif; ?>
    </div>

    <form method="get" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="user_id" class="form-select">
                <option value="">All Users</option>
                <?php foreach ($allUsers as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= (isset($_GET['user_id']) && $_GET['user_id'] == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="logs.php" class="btn btn-outline-secondary">Clear</a>
        </div>
    </form>

    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>User</th>
                <th>Action</th>
                <th>Target</th>
                <th>IP Address</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($logs)): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">No logs found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($logs as $log): ?>
                  <tr style="white-space: nowrap;">
                    <td><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></td>
                    <td><?= htmlspecialchars($log['user_name'] ?? 'Unknown') ?></td>
                    <td>
                      <?php
                        $badgeClass = [
                            'add' => 'bg-success',
                            'edit' => 'bg-warning text-dark',
                            'delete' => 'bg-danger',
                            'approve' => 'bg-primary',
                            'reject' => 'bg-secondary',
                            'login' => 'bg-info text-dark',
                            'logout' => 'bg-dark text-white'
                        ][$log['action_type']] ?? 'bg-light text-dark';
                        $actionName = strtoupper($log['action_type']);
                      ?>
                      <span class="badge <?= $badgeClass ?>"><?= $actionName ?></span>
                    </td>
                    <td>
                        <?php if ($log['target_name']): ?>
                            <?= htmlspecialchars($log['target_name']) ?>
                        <?php else: ?>
                            <span class="text-muted">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                    <td style="max-width: 400px; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($log['details']) ?>">
                        <?php 
                            $detailsArr = json_decode($log['details'], true);
                            if (is_array($detailsArr)) {
                                $detailsStr = [];
                                foreach ($detailsArr as $k => $v) {
                                    if (strpos($k, 'id') !== false) {
                                        continue;
                                    }
                                    if ($k === 'details') {
                                        $detailsStr[] = htmlspecialchars(is_array($v) ? json_encode($v) : $v);
                                    } else {
                                        $kFriendly = ucwords(str_replace('_', ' ', $k));
                                        $detailsStr[] = htmlspecialchars($kFriendly) . ': ' . htmlspecialchars(is_array($v) ? json_encode($v) : $v);
                                    }
                                }
                                echo implode(', ', $detailsStr);
                            } else {
                                echo htmlspecialchars($log['details']);
                            }
                        ?>
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
