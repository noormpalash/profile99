<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/db.php';
Auth::requireLogin(); // Accessible to every logged in user

$userId = (int)$_SESSION['user_id'];
$db = getDB();

$stmt = $db->prepare("SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch();

if (!$currentUser) die('User account not found.');

$errorMsg = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf($_POST['csrf_token'] ?? null);

    $newUsername = trim($_POST['username'] ?? '');
    $newName = trim($_POST['name'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newUsername === '') {
        $errorMsg = 'Username/Name cannot be empty.';
    } else {
        try {
            // Check username uniqueness
            $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $checkStmt->execute([$newUsername, $userId]);
            if ($checkStmt->fetch()) {
                throw new Exception('That username/name is already taken by another account.');
            }

            // Verify current password if changing password or username
            if (!password_verify($currentPassword, $currentUser['password_hash'])) {
                throw new Exception('Current password is incorrect.');
            }

            // Update username and name
            $updateUserStmt = $db->prepare("UPDATE users SET username = ?, name = ? WHERE id = ?");
            $updateUserStmt->execute([$newUsername, $newName, $userId]);
            $_SESSION['username'] = $newUsername;
            $_SESSION['name'] = $newName ?: $newUsername;
            $currentUser['username'] = $newUsername;
            $currentUser['name'] = $newName;

            // Update password if provided
            if ($newPassword !== '') {
                if (strlen($newPassword) < 8) {
                    throw new Exception('New password must be at least 8 characters long.');
                }
                if ($newPassword !== $confirmPassword) {
                    throw new Exception('New password and confirmation do not match.');
                }
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePassStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $updatePassStmt->execute([$newHash, $userId]);
                $currentUser['password_hash'] = $newHash;
            }

            $successMsg = 'Account details updated successfully!';
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container" style="max-width: 600px;">
  <div class="page-header mb-4">
    <div>
      <h4>Account Settings</h4>
      <div class="text-muted small">Update your name and account password.</div>
    </div>
    <a href="<?= Auth::role() === 'user' ? 'search.php' : 'dashboard.php' ?>" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>Back</a>
  </div>

  <?php if ($errorMsg): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($errorMsg) ?></div>
  <?php endif; ?>

  <?php if ($successMsg): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($successMsg) ?></div>
  <?php endif; ?>

  <div class="card shadow-sm border-0 rounded-4 p-4" style="background: #ffffff;">
    <form method="post">
      <?= Auth::csrfField() ?>

      <div class="mb-3">
        <label class="form-label fw-semibold small">Account Name</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($currentUser['name'] ?? '') ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold small">Username</label>
        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($currentUser['username']) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold small">Role</label>
        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars(ucfirst($currentUser['role_name'])) ?>" readonly disabled>
      </div>

      <hr class="my-4 text-muted">

      <div class="mb-3">
        <label class="form-label fw-semibold small">Current Password <span class="text-danger">*</span></label>
        <input type="password" name="current_password" class="form-control" placeholder="Enter current password to verify" required>
        <div class="form-text small text-muted">Required to confirm any changes.</div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold small">New Password (optional)</label>
        <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current password">
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold small">Confirm New Password</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password">
      </div>

      <div class="d-flex justify-content-end gap-2">
        <a href="<?= Auth::role() === 'user' ? 'search.php' : 'dashboard.php' ?>" class="btn btn-light btn-sm">Cancel</a>
        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-device-floppy me-1"></i>Save Changes</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
