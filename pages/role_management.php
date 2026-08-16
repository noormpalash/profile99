<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/db.php';
Auth::requirePermission('manage_roles');
$withSidebar = true;

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf($_POST['csrf_token'] ?? null);
    
    try {
        $db->beginTransaction();
        
        // Update permissions for each role
        if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
            // First clear all current permissions
            $db->exec("DELETE FROM role_permissions");
            
            $stmt = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($_POST['permissions'] as $role_id => $perms) {
                $role_id = (int)$role_id;
                foreach ($perms as $perm_id => $val) {
                    $stmt->execute([$role_id, (int)$perm_id]);
                }
            }
        } else {
            // If nothing is checked, clear everything
            $db->exec("DELETE FROM role_permissions");
        }
        
        $db->commit();
        $message = 'Permissions updated successfully.';
    } catch (Exception $e) {
        $db->rollBack();
        $message = 'Error updating permissions: ' . $e->getMessage();
    }
}

$roles = $db->query("SELECT * FROM roles ORDER BY id")->fetchAll();
$permissions = $db->query("SELECT * FROM permissions ORDER BY name")->fetchAll();

// Get current mappings
$mappings = [];
$rows = $db->query("SELECT role_id, permission_id FROM role_permissions")->fetchAll();
foreach ($rows as $row) {
    $mappings[$row['role_id']][$row['permission_id']] = true;
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
/* Glassmorphism & Modern UI */
.rm-container {
    padding-bottom: 2rem;
}
.rm-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid rgba(226, 232, 240, 0.8);
    box-shadow: 0 10px 40px -10px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}
.rm-header {
    background: linear-gradient(to right, #ffffff, #f8fafc);
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}
.rm-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.01em;
    margin: 0;
}
.rm-table-wrapper {
    max-height: 600px;
    overflow-y: auto;
}
.rm-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.06em;
    padding: 1.1rem 1rem;
    border-bottom: 2px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 10;
}
.rm-table td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.rm-table tr:last-child td {
    border-bottom: none;
}
.rm-table tbody tr {
    transition: all 0.2s ease;
}
.rm-table tbody tr:hover {
    background-color: #f8fafc;
}
.rm-perm-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.95rem;
    margin-bottom: 2px;
}
.rm-perm-desc {
    color: #64748b;
    font-size: 0.82rem;
}
/* Custom Toggle Switch */
.rm-toggle {
    position: relative;
    display: inline-block;
    width: 42px;
    height: 22px;
    margin: 0;
}
.rm-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}
.rm-slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #e2e8f0;
    transition: .3s;
    border-radius: 34px;
}
.rm-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
input:checked + .rm-slider {
    background-color: #6366f1; /* Indigo accent */
}
input:checked + .rm-slider:before {
    transform: translateX(20px);
}
input:focus + .rm-slider {
    box-shadow: 0 0 1px #6366f1;
}
/* Check All Mini Toggle */
.check-all-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 8px;
    color: #64748b;
}
.check-all-wrapper span {
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
}
.rm-toggle.mini {
    width: 32px;
    height: 18px;
}
.rm-toggle.mini .rm-slider:before {
    height: 14px;
    width: 14px;
    left: 2px;
    bottom: 2px;
}
.rm-toggle.mini input:checked + .rm-slider:before {
    transform: translateX(14px);
}
/* Save Button */
.rm-footer {
    padding: 1.25rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    text-align: right;
}
.rm-btn-save {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 0.65rem 1.75rem;
    border-radius: 8px;
    border: none;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    letter-spacing: 0.02em;
}
.rm-btn-save:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.35);
    color: white;
}
.rm-btn-save:active {
    transform: translateY(0);
}
</style>

<div class="row rm-container">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10">
    <div class="page-header mb-4">
      <div>
        <h4 class="fw-bold text-dark">Role & Permissions</h4>
        <div class="text-muted">Manage access control for different user roles.</div>
      </div>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-success border-0 shadow-sm py-2 mb-4" style="background-color: #d1fae5; color: #065f46;">
        <i class="ti ti-check me-2"></i><?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <div class="rm-card">
      <div class="rm-header">
        <h6 class="rm-title">Permission Matrix</h6>
      </div>
      <div class="p-0">
        <form method="post" action="role_management.php">
          <?= Auth::csrfField() ?>
          <div class="rm-table-wrapper table-responsive">
            <table class="table rm-table mb-0">
              <thead>
                <tr>
                  <th style="width: 35%;">Permission</th>
                  <?php foreach ($roles as $r): ?>
                    <th class="text-center" style="width: <?= 65 / count($roles) ?>%;">
                      <?= htmlspecialchars(ucfirst($r['name'])) ?>
                      <div class="check-all-wrapper">
                        <label class="rm-toggle mini" title="Check All">
                          <input type="checkbox" class="check-all-role" data-role-id="<?= $r['id'] ?>">
                          <span class="rm-slider"></span>
                        </label>
                        <span>All</span>
                      </div>
                    </th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($permissions as $p): ?>
                  <tr>
                    <td>
                      <div class="rm-perm-name"><?= htmlspecialchars($p['name']) ?></div>
                      <div class="rm-perm-desc"><?= htmlspecialchars($p['description']) ?></div>
                    </td>
                    <?php foreach ($roles as $r): ?>
                      <td class="text-center align-middle">
                        <label class="rm-toggle">
                          <input class="role-checkbox-<?= $r['id'] ?>" type="checkbox" 
                                 name="permissions[<?= $r['id'] ?>][<?= $p['id'] ?>]" 
                                 value="1"
                                 <?= isset($mappings[$r['id']][$p['id']]) ? 'checked' : '' ?>>
                          <span class="rm-slider"></span>
                        </label>
                      </td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="rm-footer">
            <button type="submit" class="btn rm-btn-save">Save Permissions</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.check-all-role').forEach(function(checkAllBtn) {
        const roleId = checkAllBtn.getAttribute('data-role-id');
        const roleCheckboxes = document.querySelectorAll('.role-checkbox-' + roleId);
        
        if (roleCheckboxes.length > 0) {
            const allChecked = Array.from(roleCheckboxes).every(cb => cb.checked);
            checkAllBtn.checked = allChecked;
        }

        checkAllBtn.addEventListener('change', function() {
            const isChecked = this.checked;
            roleCheckboxes.forEach(function(cb) {
                cb.checked = isChecked;
            });
        });

        roleCheckboxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                if (!this.checked) {
                    checkAllBtn.checked = false;
                } else {
                    const allChecked = Array.from(roleCheckboxes).every(c => c.checked);
                    checkAllBtn.checked = allChecked;
                }
            });
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
