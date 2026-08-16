<?php require_once __DIR__ . '/../config/db.php'; require_once __DIR__ . '/../classes/Auth.php'; require_once __DIR__ . '/../classes/AppSettings.php'; Auth::start(); $withSidebar = $withSidebar ?? false; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(AppSettings::get('app_title', 'Army Personnel System')) ?></title>
<link href="../assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/2.44.0/iconfont/tabler-icons.min.css" rel="stylesheet">
<link href="../assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar py-3 sticky-top" style="z-index: 1040;">
  <div class="container-fluid">
    <div class="d-flex align-items-center">
      <?php if ($withSidebar): ?>
        <button id="mobileSidebarToggle" class="border-0 bg-transparent me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Open menu" style="display:none; padding:8px; min-width:44px; min-height:44px; border-radius:8px; transition: background 0.2s;">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <style>
          @media (max-width: 991.98px) {
            #mobileSidebarToggle { display: inline-flex !important; align-items: center; justify-content: center; }
          }
          #mobileSidebarToggle:active {
            background: rgba(255, 255, 255, 0.15);
          }
        </style>
      <?php endif; ?>
      <a class="navbar-brand d-flex align-items-center gap-2" href="<?= Auth::isLoggedIn() ? (Auth::hasPermission('view_dashboard') ? 'dashboard.php' : 'search.php') : 'login.php' ?>">
        <?php $logoPath = AppSettings::get('app_logo_path'); ?>
        <?php if ($logoPath): ?>
          <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($logoPath) ?>" alt="Logo" style="height: 42px; width: auto; object-fit: contain;">
        <?php else: ?>
          <span class="navbar-brand-icon"><i class="ti <?= htmlspecialchars(AppSettings::get('app_logo', 'ti-shield-star')) ?>"></i></span>
        <?php endif; ?>
        <span><?= htmlspecialchars(AppSettings::get('app_name', 'Unit Personnel System')) ?></span>
      </a>
    </div>
    <div class="d-flex align-items-center gap-2">
      <?php if (Auth::isLoggedIn()): ?>
        <div class="dropdown">
          <button class="btn btn-link text-decoration-none p-0 d-flex align-items-center gap-2 text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="user-avatar" style="width:38px;height:38px;border-radius:50%;overflow:hidden;border:2px solid rgba(255,255,255,0.2);">
              <img src="../assets/images/default-avatar.svg" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div class="d-none d-sm-block text-white text-start">
              <div class="user-name fw-semibold lh-1 mb-1" style="font-size: 0.95rem;"><?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['username']) ?></div>
              <div class="user-role small opacity-75 lh-1 text-capitalize"><?= htmlspecialchars(Auth::role()) ?></div>
            </div>
            <i class="ti ti-chevron-down text-white opacity-75 ms-1 d-none d-sm-block"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="border-radius: 12px; min-width: 200px;">
            <li>
              <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="account_settings.php">
                <i class="ti ti-user-edit text-muted"></i> Account Settings
              </a>
            </li>
            <?php if (Auth::hasPermission('view_dashboard')): ?>
            <li>
              <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="dashboard.php">
                <i class="ti ti-layout-dashboard text-muted"></i> Dashboard
              </a>
            </li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="post" action="logout.php" class="mb-0">
                <?= Auth::csrfField() ?>
                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger">
                  <i class="ti ti-logout"></i> Logout
                </button>
              </form>
            </li>
          </ul>
        </div>
      <?php else: ?>
      <?php endif; ?>
    </div>
  </div>
</nav>
<div class="container-fluid py-4 main-content <?= $withSidebar ? 'with-sidebar' : 'no-sidebar' ?>">
