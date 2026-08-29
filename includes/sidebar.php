<?php $current = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar-column no-print offcanvas-lg offcanvas-start" id="mobileSidebar" tabindex="-1" aria-labelledby="mobileSidebarLabel">
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
        <?php 
        $isLeaveMatrixActive = in_array($current, ['leave_seniority.php', 'leave_history.php']);
        if (Auth::hasAnyPermission(['leave_seniority', 'leave_history'])): 
        ?>
        <div class="sidebar-dropdown-wrapper">
          <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $isLeaveMatrixActive ? 'active' : '' ?>" 
             data-bs-toggle="collapse" 
             href="#leaveMatrixSubmenu" 
             role="button" 
             aria-expanded="<?= $isLeaveMatrixActive ? 'true' : 'false' ?>" 
             aria-controls="leaveMatrixSubmenu">
            <span><i class="ti ti-calendar-stats me-2"></i>Leave Matrix</span>
            <i class="ti ti-chevron-down submenu-chevron" style="font-size: 0.85rem; transition: transform 0.2s;"></i>
          </a>
          <div class="collapse <?= $isLeaveMatrixActive ? 'show' : '' ?> px-2 pt-1 pb-1" id="leaveMatrixSubmenu">
            <?php if (Auth::hasPermission('leave_seniority')): ?>
              <a href="leave_seniority.php" class="list-group-item list-group-item-action submenu-item <?= $current==='leave_seniority.php'?'active':'' ?>" style="font-size: 0.88rem; padding: 0.6rem 0.9rem;">
                <i class="ti ti-calendar-time me-2"></i>Leave Seniority
              </a>
            <?php endif; ?>
            <?php if (Auth::hasPermission('leave_history')): ?>
              <a href="leave_history.php" class="list-group-item list-group-item-action submenu-item <?= $current==='leave_history.php'?'active':'' ?>" style="font-size: 0.88rem; padding: 0.6rem 0.9rem;">
                <i class="ti ti-history me-2"></i>Leave History
              </a>
            <?php endif; ?>
          </div>
        </div>
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

        <?php 
        $isUserAccessActive = in_array($current, ['user_management.php', 'role_management.php']);
        if (Auth::hasAnyPermission(['manage_users', 'manage_roles'])): 
        ?>
        <div class="sidebar-dropdown-wrapper">
          <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $isUserAccessActive ? 'active' : '' ?>" 
             data-bs-toggle="collapse" 
             href="#userAccessSubmenu" 
             role="button" 
             aria-expanded="<?= $isUserAccessActive ? 'true' : 'false' ?>" 
             aria-controls="userAccessSubmenu">
            <span><i class="ti ti-user-shield me-2"></i>User Access</span>
            <i class="ti ti-chevron-down submenu-chevron" style="font-size: 0.85rem; transition: transform 0.2s;"></i>
          </a>
          <div class="collapse <?= $isUserAccessActive ? 'show' : '' ?> px-2 pt-1 pb-1" id="userAccessSubmenu">
            <?php if (Auth::hasPermission('manage_users')): ?>
              <a href="user_management.php" class="list-group-item list-group-item-action submenu-item <?= $current==='user_management.php'?'active':'' ?>" style="font-size: 0.88rem; padding: 0.6rem 0.9rem;">
                <i class="ti ti-user-cog me-2"></i>User Management
              </a>
            <?php endif; ?>
            <?php if (Auth::hasPermission('manage_roles')): ?>
              <a href="role_management.php" class="list-group-item list-group-item-action submenu-item <?= $current==='role_management.php'?'active':'' ?>" style="font-size: 0.88rem; padding: 0.6rem 0.9rem;">
                <i class="ti ti-shield-lock me-2"></i>Roles & Permissions
              </a>
            <?php endif; ?>
          </div>
        </div>
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
