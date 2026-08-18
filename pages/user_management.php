<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/db.php';
Auth::requirePermission('manage_users'); // Superadmin bypasses permission check anyway
$withSidebar = true;

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  Auth::verifyCsrf($_POST['csrf_token'] ?? null);
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'add') {
      if (strlen($_POST['password']) < 8) {
        throw new Exception("Password must be at least 8 characters long.");
      }
      $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
      $stmt = $db->prepare("INSERT INTO users (name, username, password_hash, role_id) VALUES (?,?,?,?)");
      $stmt->execute([trim($_POST['name'] ?? ''), trim($_POST['username']), $hash, (int) $_POST['role_id']]);
    } elseif ($action === 'edit') {
      $editId = (int) $_POST['id'];
      $newName = trim($_POST['name'] ?? '');
      $newUsername = trim($_POST['username']);
      $roleId = (int) $_POST['role_id'];
      $newPass = $_POST['password'] ?? '';

      if ($newPass !== '') {
        if (strlen($newPass) < 8) {
          throw new Exception("Password must be at least 8 characters long.");
        }
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET name = ?, username = ?, role_id = ?, password_hash = ? WHERE id = ?");
        $stmt->execute([$newName, $newUsername, $roleId, $hash, $editId]);
      } else {
        $stmt = $db->prepare("UPDATE users SET name = ?, username = ?, role_id = ? WHERE id = ?");
        $stmt->execute([$newName, $newUsername, $roleId, $editId]);
      }

      if ($editId === (int) ($_SESSION['user_id'] ?? 0)) {
        $_SESSION['username'] = $newUsername;
      }
    } elseif ($action === 'toggle_status') {
      $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
      $stmt->execute([$_POST['status'], (int) $_POST['id']]);
    } elseif ($action === 'force_logout') {
      Auth::requirePermission('force_logout_user');
      $logoutId = (int) $_POST['id'];
      if ($logoutId === (int) ($_SESSION['user_id'] ?? 0)) {
        throw new Exception('You cannot force logout your own account here.');
      }
      $stmt = $db->prepare("UPDATE users SET session_token = NULL WHERE id = ?");
      $stmt->execute([$logoutId]);
      $_SESSION['user_mgmt_success'] = "User forced to log out.";
    } elseif ($action === 'delete') {
      $deleteId = (int) $_POST['id'];
      if ($deleteId === (int) ($_SESSION['user_id'] ?? 0)) {
        throw new Exception('You cannot delete your own account.');
      }
      $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
      $stmt->execute([$deleteId]);
    }
  } catch (Exception $e) {
    $_SESSION['user_mgmt_error'] = $e->getMessage();
  }
  header('Location: user_management.php');
  exit;
}

$users = $db->query("SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.username")->fetchAll();
$roles = $db->query("SELECT * FROM roles")->fetchAll();
$flashError = $_SESSION['user_mgmt_error'] ?? '';
$flashSuccess = $_SESSION['user_mgmt_success'] ?? '';
unset($_SESSION['user_mgmt_error'], $_SESSION['user_mgmt_success']);
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="row">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10">
    <?php if ($flashError): ?>
      <div class="alert alert-danger py-2 mb-3"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>
    <?php if ($flashSuccess): ?>
      <div class="alert alert-success py-2 mb-3"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <div class="page-header">
      <div>
        <h4>User Management</h4>
      </div>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal"><i
          class="ti ti-plus me-1"></i>Add user</button>
    </div>

    <div class="card shadow-sm p-3">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Name</th>
              <th>Username</th>
              <th>Role</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td><?= htmlspecialchars($u['name'] ?? '') ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><span
                    class="badge role-<?= htmlspecialchars($u['role_name']) ?>"><?= htmlspecialchars($u['role_name']) ?></span>
                </td>
                <td><span
                    class="badge status-<?= $u['status'] === 'active' ? 'active' : 'punishment' ?>"><?= htmlspecialchars($u['status']) ?></span>
                </td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                    data-bs-target="#editUserModal<?= $u['id'] ?>">Edit</button>
                  <form method="post" class="d-inline">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'disabled' : 'active' ?>">
                    <button
                      class="btn btn-sm btn-outline-secondary"><?= $u['status'] === 'active' ? 'Disable' : 'Enable' ?></button>
                  </form>
                  <?php if (Auth::hasPermission('force_logout_user')): ?>
                  <form method="post" class="d-inline" data-confirm="Force logout this user?">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="action" value="force_logout">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button class="btn btn-sm btn-outline-warning" type="submit">Logout</button>
                  </form>
                  <?php endif; ?>
                  <form method="post" class="d-inline" data-confirm="Delete this account?">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div> <!-- closes col-md-9 col-lg-10 -->
</div> <!-- closes row -->

<!-- Add user modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
          <h6 class="modal-title">Add system account</h6>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label small">Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label small">Username</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label small">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label small">Role</label>
            <select name="role_id" class="form-select">
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modals placed at root level outside of table stacking context -->
<?php foreach ($users as $u): ?>
  <div class="modal fade" id="editUserModal<?= $u['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <?= Auth::csrfField() ?>
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" value="<?= $u['id'] ?>">
          <div class="modal-header">
            <h6 class="modal-title">Edit Account: <?= htmlspecialchars($u['username']) ?></h6>
          </div>
          <div class="modal-body">
            <div class="mb-2">
              <label class="form-label small">Name</label>
              <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($u['name'] ?? '') ?>" required>
            </div>
            <div class="mb-2">
              <label class="form-label small">Username</label>
              <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($u['username']) ?>"
                required>
            </div>
            <div class="mb-2">
              <label class="form-label small">New Password (leave blank to keep current)</label>
              <input type="password" name="password" class="form-control" placeholder="Enter new password">
            </div>
            <div class="mb-2">
              <label class="form-label small">Role</label>
              <select name="role_id" class="form-select">
                <?php foreach ($roles as $r): ?>
                  <option value="<?= $r['id'] ?>" <?= $u['role_id'] == $r['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>