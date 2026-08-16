<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/LookupManager.php';
Auth::requireAnyPermission(['manage_options', 'edit_manpower_state']);
$withSidebar = true;

$categories = [
    'ranks' => 'Ranks', 'units' => 'Units', 'cadres' => 'Cadres',
    'platoons' => 'Platoons', 'blood_groups' => 'Blood groups',
  'courses' => 'Courses', 'moqs' => 'MOQs', 'medical_categories' => 'Medical categories',
  'appointments' => 'Appointments',
];

$table = $_GET['table'] ?? 'ranks';
if (!array_key_exists($table, $categories)) $table = 'ranks';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf($_POST['csrf_token'] ?? null);
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            Auth::requirePermission('manage_options');
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                throw new Exception('Name cannot be blank.');
            }
            LookupManager::add($table, $name);
        } elseif ($action === 'edit') {
            Auth::requirePermission('manage_options');
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                throw new Exception('Name cannot be blank.');
            }
            LookupManager::update($table, (int)$_POST['id'], $name);
        } elseif ($action === 'delete') {
            Auth::requirePermission('manage_options');
            LookupManager::delete($table, (int)$_POST['id']);
        } elseif ($action === 'update_manpower') {
            Auth::requirePermission('edit_manpower_state');
            $db = getDB();
            $stmt = $db->prepare("UPDATE manpower_state SET auth = ?, posted = ?, att = ? WHERE category = ?");
            foreach (['OFFR','SWO','WO','SGT','CPL','LCPL','SNK(GD)','NC(E)','NC(U)'] as $cat) {
                $a = (int)($_POST['auth'][$cat] ?? 0);
                $p = (int)($_POST['posted'][$cat] ?? 0);
                $at = (int)($_POST['att'][$cat] ?? 0);
                $stmt->execute([$a, $p, $at, $cat]);
            }
            header("Location: manage_options.php?table=$table");
            exit;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
    header("Location: manage_options.php?table=$table" . ($message ? '&error=' . urlencode($message) : ''));
    exit;
}

$message = $_GET['error'] ?? '';
$items = LookupManager::getAll($table);

$db = getDB();
$mpRows = $db->query("SELECT category, auth, posted, att FROM manpower_state")->fetchAll(PDO::FETCH_ASSOC);
$mpState = [];
foreach ($mpRows as $r) {
    $mpState[$r['category']] = ['auth' => $r['auth'], 'posted' => $r['posted'], 'att' => $r['att']];
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="row">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10">
    <div class="page-header">
      <div>
        <h4>Manage Options</h4>
      </div>
    </div>

    <div class="row">
      <div class="col-md-3">
        <?php if (Auth::hasPermission('manage_options')): ?>
        <div class="card sidebar-card p-3 mb-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="fw-semibold">Categories</div>
            <span class="badge bg-primary badge-pill"><?= count($categories) ?></span>
          </div>
          <div class="list-group options-list">
            <?php foreach ($categories as $key => $label): ?>
              <a href="manage_options.php?table=<?= $key ?>" class="list-group-item list-group-item-action <?= $table===$key?'active':'' ?>"><?= $label ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if (Auth::hasPermission('edit_manpower_state')): ?>
        <div class="card sidebar-card p-3 mb-4">
          <div class="fw-semibold mb-3">Manpower State</div>
          <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#manpowerModal">Edit Manpower</button>
        </div>
        <?php endif; ?>
      </div>

      <div class="col-md-9">
        <?php if ($message): ?><div class="alert alert-danger rounded-pill py-2 px-3 mb-4"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <?php if (Auth::hasPermission('manage_options')): ?>
        <div class="card shadow-sm mb-4">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div>
                <h6 class="mb-1">Manage <?= htmlspecialchars($categories[$table]) ?></h6>
                <p class="text-muted small mb-0">Add, edit, or remove values for this lookup category.</p>
              </div>
            </div>
            <form method="post" class="row g-2 align-items-center">
              <?= Auth::csrfField() ?>
              <input type="hidden" name="action" value="add">
              <div class="col-md-9">
                <input type="text" name="name" class="form-control" placeholder="New <?= strtolower(rtrim($categories[$table],'s')) ?> name" required>
              </div>
              <div class="col-md-3">
                <button class="btn btn-primary w-100"><i class="ti ti-plus me-1"></i>Add</button>
              </div>
            </form>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-body">
            <div class="list-group options-list">
              <?php foreach ($items as $item): ?>
                <div class="list-group-item py-3">
                  <div class="d-flex gap-2 align-items-center">
                    <form method="post" class="flex-grow-1 d-flex gap-2 align-items-center">
                      <?= Auth::csrfField() ?>
                      <input type="hidden" name="action" value="edit">
                      <input type="hidden" name="id" value="<?= $item['id'] ?>">
                      <input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($item['name']) ?>">
                      <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                    </form>
                    <form method="post" onsubmit="return confirm('Delete this option?')">
                      <?= Auth::csrfField() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $item['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
              <?php if (empty($items)): ?><p class="text-muted small mb-0">No entries yet.</p><?php endif; ?>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">Select an option from the sidebar.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Edit Manpower Modal -->
<?php if (Auth::hasPermission('edit_manpower_state')): ?>
<div class="modal fade" id="manpowerModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="POST">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="action" value="update_manpower">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Edit Manpower State (Auth, Posted & ATT)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <?php foreach (['OFFR','SWO','WO','SGT','CPL','LCPL','SNK(GD)','NC(E)','NC(U)'] as $c): ?>
            <div class="col-md-3 col-6">
              <label class="form-label fw-bold mb-1"><?= htmlspecialchars($c) ?></label>
              <div class="input-group input-group-sm mb-1">
                <span class="input-group-text" style="width:60px;">Auth</span>
                <input type="number" name="auth[<?= $c ?>]" class="form-control" value="<?= $mpState[$c]['auth'] ?? 0 ?>" min="0">
              </div>
              <div class="input-group input-group-sm mb-1">
                <span class="input-group-text" style="width:60px;">Posted</span>
                <input type="number" name="posted[<?= $c ?>]" class="form-control" value="<?= $mpState[$c]['posted'] ?? 0 ?>" min="0">
              </div>
              <div class="input-group input-group-sm">
                <span class="input-group-text" style="width:60px;">ATT</span>
                <input type="number" name="att[<?= $c ?>]" class="form-control" value="<?= $mpState[$c]['att'] ?? 0 ?>" min="0">
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
