<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/AppSettings.php';
Auth::start();

// Read and clear logout reason cookie
if (isset($_COOKIE['logout_reason'])) {
    if (!isset($_GET['reason'])) {
        $_GET['reason'] = $_COOKIE['logout_reason'];
    }
    setcookie('logout_reason', '', time() - 3600, '/');
}

// Force logout if tab was closed
if (isset($_GET['reason']) && $_GET['reason'] === 'tabclosed') {
    Auth::logout();
    header('Location: login.php?reason=tabclosed_msg');
    exit;
}

// Redirect if already logged in
if (Auth::isLoggedIn()) {
    $location = Auth::hasPermission('view_dashboard') ? 'dashboard.php' : 'search.php';
    header('Location: ' . $location);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf($_POST['csrf_token'] ?? null);
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        try {
            if (Auth::attemptLogin($username, $password)) {
                $location = Auth::hasPermission('view_dashboard') ? 'dashboard.php' : 'search.php';
                header('Location: ' . $location);
                exit;
            } else {
                $error = 'Invalid credentials.';
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An unexpected error occurred. Please try again.';
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
  <div class="card login-card shadow-sm p-4" style="max-width: 340px; width: 100%;">
    <div class="text-center mb-4">
      <?php $logoPath = AppSettings::get('app_logo_path'); ?>
      <?php if ($logoPath): ?>
        <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($logoPath) ?>" alt="Logo" style="height: 48px; width: auto; object-fit: contain;">
      <?php else: ?>
        <div class="navbar-brand-icon mx-auto bg-primary text-white mb-2" style="width: 48px; height: 48px; border-radius: 14px;">
          <i class="ti <?= htmlspecialchars(AppSettings::get('app_logo', 'ti-shield-star')) ?> fs-3"></i>
        </div>
      <?php endif; ?>
      <h5 class="fw-bold mb-0 text-dark">Sign In</h5>
    </div>

    <?php if (isset($_GET['reason'])): ?>
      <?php if ($_GET['reason'] === 'timeout'): ?>
        <div class="alert alert-warning py-2 small rounded-3"><i class="ti ti-clock-off me-1"></i>You were logged out due to 5 minutes of inactivity.</div>
      <?php elseif ($_GET['reason'] === 'new_device'): ?>
        <div class="alert alert-danger py-2 small rounded-3"><i class="ti ti-device-mobile me-1"></i>New device login detected. You have been logged out.</div>
      <?php elseif ($_GET['reason'] === 'tabclosed_msg'): ?>
        <div class="alert alert-info py-2 small rounded-3"><i class="ti ti-info-circle me-1"></i>You were logged out because your session was closed.</div>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger py-2 small rounded-3"><i class="ti ti-alert-circle me-1"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <?= Auth::csrfField() ?>
      <div class="mb-3">
        <label class="form-label small text-muted fw-semibold">Username</label>
        <input type="text" name="username" class="form-control" required autocomplete="username" maxlength="50">
      </div>
      <div class="mb-4">
        <label class="form-label small text-muted fw-semibold">Password</label>
        <input type="password" name="password" class="form-control" required autocomplete="current-password" maxlength="128">
      </div>
      <button type="submit" class="btn btn-primary w-100 fw-semibold">Sign in</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
