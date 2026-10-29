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
      $password = $_POST['password'] ?? '';
      $passErrors = Auth::getPasswordComplexityErrors($password);
      if (!empty($passErrors)) {
        $_SESSION['add_user_errors'] = $passErrors;
        $_SESSION['add_user_form'] = [
          'name' => $_POST['name'] ?? '',
          'username' => $_POST['username'] ?? '',
          'role_id' => $_POST['role_id'] ?? ''
        ];
        $_SESSION['open_add_modal'] = true;
        header('Location: user_management.php');
        exit;
      }
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $db->prepare("INSERT INTO users (name, username, password_hash, role_id) VALUES (?,?,?,?)");
      $stmt->execute([trim($_POST['name'] ?? ''), trim($_POST['username']), $hash, (int) $_POST['role_id']]);
      $_SESSION['user_mgmt_success'] = "User account created successfully.";
    } elseif ($action === 'edit') {
      $editId = (int) $_POST['id'];
      $newName = trim($_POST['name'] ?? '');
      $newUsername = trim($_POST['username']);
      $roleId = (int) $_POST['role_id'];
      $newPass = $_POST['password'] ?? '';

      if ($newPass !== '') {
        if ($err = Auth::validatePasswordComplexity($newPass)) {
          throw new Exception($err);
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
      // Security: whitelist status values to prevent arbitrary data injection
      $allowedStatuses = ['active', 'disabled'];
      $newStatus = $_POST['status'] ?? '';
      if (!in_array($newStatus, $allowedStatuses, true)) {
        throw new Exception('Invalid status value.');
      }
      $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
      $stmt->execute([$newStatus, (int) $_POST['id']]);
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
    if ($action === 'add') {
      $_SESSION['open_add_modal'] = true;
      $_SESSION['add_user_form'] = [
        'name' => $_POST['name'] ?? '',
        'username' => $_POST['username'] ?? '',
        'role_id' => $_POST['role_id'] ?? ''
      ];
      if (empty($_SESSION['add_user_errors'])) {
        $_SESSION['add_user_errors'] = [$e->getMessage()];
      }
    } else {
      $_SESSION['user_mgmt_error'] = $e->getMessage();
    }
  }
  header('Location: user_management.php');
  exit;
}

$users = $db->query("SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.username")->fetchAll();
$roles = $db->query("SELECT * FROM roles")->fetchAll();
$flashError = $_SESSION['user_mgmt_error'] ?? '';
$flashSuccess = $_SESSION['user_mgmt_success'] ?? '';
$addUserErrors = $_SESSION['add_user_errors'] ?? [];
$addUserForm = $_SESSION['add_user_form'] ?? [];
$openAddModal = !empty($_SESSION['open_add_modal']);
unset($_SESSION['user_mgmt_error'], $_SESSION['user_mgmt_success'], $_SESSION['add_user_errors'], $_SESSION['add_user_form'], $_SESSION['open_add_modal']);
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
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalTitle" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" id="addUserForm" novalidate>
        <?= Auth::csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
          <h6 class="modal-title" id="addUserModalTitle">Add system account</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <?php if (!empty($addUserErrors)): ?>
            <div class="alert alert-danger py-2 mb-3" id="serverAddUserErrorsAlert">
              <div class="fw-semibold small mb-1 d-flex align-items-center gap-1 text-danger">
                <i class="ti ti-alert-triangle"></i>
                <span>Password requirements not met:</span>
              </div>
              <ul class="mb-0 ps-3 small">
                <?php foreach ($addUserErrors as $err): ?>
                  <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <div class="mb-2">
            <label class="form-label small">Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($addUserForm['name'] ?? '') ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label small">Username</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($addUserForm['username'] ?? '') ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" name="password" id="addUserPassword" class="form-control" placeholder="Enter secure password" required autocomplete="new-password">
              <button class="btn btn-outline-secondary" type="button" id="toggleAddUserPassword" title="Show/Hide Password" tabindex="-1">
                <i class="ti ti-eye" id="toggleAddUserPasswordIcon"></i>
              </button>
            </div>

            <!-- Password Recommendation Hint Box -->
            <div class="password-guidance-box mt-2 p-2 rounded bg-light border">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="small fw-semibold text-muted">
                  <i class="ti ti-info-circle me-1"></i>Password recommendations:
                </span>
                <span id="addUserStrengthBadge" class="badge bg-secondary-subtle text-secondary small">Required</span>
              </div>

              <!-- Recommendation rules breakdown -->
              <ul class="list-unstyled mb-0 small text-muted" id="addUserPasswordRules">
                <li data-rule="length" class="d-flex align-items-center gap-1 py-0.5">
                  <i class="ti ti-circle text-muted rule-icon"></i>
                  <span>At least 8 characters long</span>
                </li>
                <li data-rule="uppercase" class="d-flex align-items-center gap-1 py-0.5">
                  <i class="ti ti-circle text-muted rule-icon"></i>
                  <span>At least one uppercase letter (A-Z)</span>
                </li>
                <li data-rule="lowercase" class="d-flex align-items-center gap-1 py-0.5">
                  <i class="ti ti-circle text-muted rule-icon"></i>
                  <span>At least one lowercase letter (a-z)</span>
                </li>
                <li data-rule="number" class="d-flex align-items-center gap-1 py-0.5">
                  <i class="ti ti-circle text-muted rule-icon"></i>
                  <span>At least one digit (0-9)</span>
                </li>
                <li data-rule="special" class="d-flex align-items-center gap-1 py-0.5">
                  <i class="ti ti-circle text-muted rule-icon"></i>
                  <span>At least one special character (!@#$%^&*...)</span>
                </li>
              </ul>
            </div>
          </div>
          <div class="mb-2">
            <label class="form-label small">Role</label>
            <select name="role_id" class="form-select">
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>" <?= ((string)($addUserForm['role_id'] ?? '') === (string)$r['id']) ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="btnCreateAccount">Create account</button>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
  const addModalEl = document.getElementById('addUserModal');
  const addForm = document.getElementById('addUserForm');
  const passInput = document.getElementById('addUserPassword');
  const toggleBtn = document.getElementById('toggleAddUserPassword');
  const toggleIcon = document.getElementById('toggleAddUserPasswordIcon');
  const strengthBadge = document.getElementById('addUserStrengthBadge');
  const rulesList = document.getElementById('addUserPasswordRules');

  // Auto-open modal if server-side validation error occurred
  <?php if ($openAddModal): ?>
  if (addModalEl && window.bootstrap && bootstrap.Modal) {
    const modalInstance = bootstrap.Modal.getOrCreateInstance(addModalEl);
    modalInstance.show();
  }
  <?php endif; ?>

  if (!passInput || !rulesList) return;

  const passwordRules = [
    { key: 'length', text: 'At least 8 characters long', test: function(p) { return p.length >= 8; } },
    { key: 'uppercase', text: 'At least one uppercase letter (A-Z)', test: function(p) { return /[A-Z]/.test(p); } },
    { key: 'lowercase', text: 'At least one lowercase letter (a-z)', test: function(p) { return /[a-z]/.test(p); } },
    { key: 'number', text: 'At least one digit (0-9)', test: function(p) { return /[0-9]/.test(p); } },
    { key: 'special', text: 'At least one special character (!@#$%^&*...)', test: function(p) { return /[^A-Za-z0-9]/.test(p); } }
  ];

  function evaluatePassword(showMissingCallout) {
    const val = passInput.value || '';
    const missing = [];
    let passedCount = 0;

    passwordRules.forEach(function(rule) {
      const isPassed = rule.test(val);
      const itemEl = rulesList.querySelector('[data-rule="' + rule.key + '"]');
      if (itemEl) {
        const icon = itemEl.querySelector('.rule-icon');
        if (isPassed) {
          passedCount++;
          itemEl.classList.remove('text-muted', 'text-danger');
          itemEl.classList.add('text-success', 'fw-medium');
          if (icon) {
            icon.className = 'ti ti-circle-check text-success rule-icon';
          }
        } else {
          missing.push(rule.text);
          itemEl.classList.remove('text-success', 'fw-medium');
          if (val.length > 0 || showMissingCallout) {
            itemEl.classList.add('text-danger');
            itemEl.classList.remove('text-muted');
            if (icon) {
              icon.className = 'ti ti-circle-x text-danger rule-icon';
            }
          } else {
            itemEl.classList.add('text-muted');
            itemEl.classList.remove('text-danger');
            if (icon) {
              icon.className = 'ti ti-circle text-muted rule-icon';
            }
          }
        }
      }
    });

    // Update strength badge
    if (strengthBadge) {
      if (val.length === 0) {
        strengthBadge.className = 'badge bg-secondary-subtle text-secondary small';
        strengthBadge.textContent = 'Required';
      } else if (passedCount === 5) {
        strengthBadge.className = 'badge bg-success-subtle text-success small';
        strengthBadge.textContent = 'Strong (All Met)';
      } else if (passedCount >= 3) {
        strengthBadge.className = 'badge bg-warning-subtle text-warning small';
        strengthBadge.textContent = 'Moderate (' + passedCount + '/5)';
      } else {
        strengthBadge.className = 'badge bg-danger-subtle text-danger small';
        strengthBadge.textContent = 'Weak (' + passedCount + '/5)';
      }
    }

    // Input validation state
    if (missing.length > 0 && (showMissingCallout || val.length > 0)) {
      passInput.classList.add('is-invalid');
      passInput.classList.remove('is-valid');
    } else if (missing.length === 0 && val.length > 0) {
      passInput.classList.remove('is-invalid');
      passInput.classList.add('is-valid');
    } else {
      passInput.classList.remove('is-invalid', 'is-valid');
    }

    return missing;
  }

  passInput.addEventListener('input', function () {
    const srvAlert = document.getElementById('serverAddUserErrorsAlert');
    if (srvAlert) srvAlert.style.display = 'none';
    evaluatePassword(false);
  });

  if (toggleBtn && toggleIcon) {
    toggleBtn.addEventListener('click', function () {
      if (passInput.type === 'password') {
        passInput.type = 'text';
        toggleIcon.className = 'ti ti-eye-off';
      } else {
        passInput.type = 'password';
        toggleIcon.className = 'ti ti-eye';
      }
    });
  }

  if (addForm) {
    addForm.addEventListener('submit', function (e) {
      const missing = evaluatePassword(true);
      if (missing.length > 0) {
        e.preventDefault();
        e.stopPropagation();
        passInput.focus();
        return false;
      }
    });
  }

  if (addModalEl) {
    addModalEl.addEventListener('hidden.bs.modal', function () {
      passInput.classList.remove('is-invalid', 'is-valid');
      if (passInput.type === 'text') {
        passInput.type = 'password';
        if (toggleIcon) toggleIcon.className = 'ti ti-eye';
      }
    });
  }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>