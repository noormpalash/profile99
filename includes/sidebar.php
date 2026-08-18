<?php $current = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar-column offcanvas-lg offcanvas-start" id="mobileSidebar" tabindex="-1" aria-labelledby="mobileSidebarLabel">
  <div class="offcanvas-header d-lg-none border-bottom">
    <h5 class="offcanvas-title text-dark fw-bold" id="mobileSidebarLabel">Menu</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" data-bs-target="#mobileSidebar" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body flex-column p-0">
    <div class="sidebar-panel p-3 w-100">
      <div class="list-group app-sidebar">
        <a href="search.php" class="list-group-item list-group-item-action <?= $current==='search.php'?'active':'' ?>">
          <i class="ti ti-search me-2"></i>Search
        </a>
        <?php if (Auth::hasPermission('view_dashboard')): ?>
        <a href="dashboard.php" class="list-group-item list-group-item-action <?= $current==='dashboard.php'?'active':'' ?>">
          <i class="ti ti-layout-dashboard me-2"></i>Dashboard
        </a>
        <?php endif; ?>
        
        <?php if (Auth::hasPermission('view_personnel')): ?>
        <a href="personnel.php" class="list-group-item list-group-item-action <?= $current==='personnel.php'?'active':'' ?>">
          <i class="ti ti-users me-2"></i>Personnel
        </a>
        <?php endif; ?>

        <?php if (Auth::hasPermission('bulk_import')): ?>
        <a href="import.php" class="list-group-item list-group-item-action <?= $current==='import.php'?'active':'' ?>">
          <i class="ti ti-file-import me-2"></i>Bulk Import
        </a>
        <?php endif; ?>

        <?php if (Auth::hasPermission('approval') || in_array(Auth::role(), ['admin', 'superadmin', 'techadmin'])): 
            $pendingCount = getDB()->query("SELECT COUNT(*) FROM personnel_approvals WHERE status='pending'")->fetchColumn();
        ?>
        <a href="approvals.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $current==='approvals.php'?'active':'' ?>">
          <span><i class="ti ti-clipboard-check me-2"></i>Approvals</span>
          <?php if ($pendingCount > 0): ?>
            <span class="badge bg-danger rounded-pill"><?= $pendingCount ?></span>
          <?php endif; ?>
        </a>
        <?php endif; ?>

        <?php if (Auth::hasAnyPermission(['manage_options', 'edit_manpower_state'])): ?>
        <a href="manage_options.php" class="list-group-item list-group-item-action <?= $current==='manage_options.php'?'active':'' ?>">
          <i class="ti ti-list-details me-2"></i>Manage options
        </a>
        <?php endif; ?>

        <?php if (Auth::hasPermission('manage_users')): ?>
        <a href="user_management.php" class="list-group-item list-group-item-action <?= $current==='user_management.php'?'active':'' ?>">
          <i class="ti ti-user-cog me-2"></i>User management
        </a>
        <?php endif; ?>
        
        <?php if (Auth::hasPermission('manage_roles')): ?>
        <a href="role_management.php" class="list-group-item list-group-item-action <?= $current==='role_management.php'?'active':'' ?>">
          <i class="ti ti-shield-lock me-2"></i>Role & Permissions
        </a>
        <?php endif; ?>

        <?php if (Auth::hasPermission('view_logs')): ?>
        <a href="logs.php" class="list-group-item list-group-item-action <?= $current==='logs.php'?'active':'' ?>">
          <i class="ti ti-history me-2"></i>Activity Logs
        </a>
        <?php endif; ?>

        <?php if (Auth::hasPermission('app_settings')): ?>
        <a href="app_settings.php" class="list-group-item list-group-item-action <?= $current==='app_settings.php'?'active':'' ?>">
          <i class="ti ti-settings me-2"></i>App Settings
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
