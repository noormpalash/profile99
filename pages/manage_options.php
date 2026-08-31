<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/LookupManager.php';
require_once __DIR__ . '/../classes/Personnel.php';
require_once __DIR__ . '/../classes/AppSettings.php';
Auth::requireAnyPermission(['manage_options', 'edit_manpower_state', 'bulk_import']);
$withSidebar = true;

$categories = [
    'ranks' => 'Ranks', 'units' => 'Units', 'cadres' => 'Cadres',
    'platoons' => 'Platoons', 'blood_groups' => 'Blood groups',
  'courses' => 'Courses', 'moqs' => 'MOQs', 'medical_categories' => 'Medical categories',
  'appointments' => 'Appointments',
];

$table = $_GET['table'] ?? 'ranks';
if (!array_key_exists($table, $categories)) $table = 'ranks';

$isBulkImport = isset($_GET['action']) && $_GET['action'] === 'bulk_import';
$results = [];
$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf($_POST['csrf_token'] ?? null);
    
    if (isset($_POST['cancel_preview'])) {
        unset($_SESSION['bulk_import_preview']);
        header("Location: manage_options.php?action=bulk_import");
        exit;
    } elseif (isset($_POST['confirm_import'])) {
        Auth::requirePermission('bulk_import');
        $sessionData = $_SESSION['bulk_import_preview'] ?? [];
        $selectedRows = $_POST['import_rows'] ?? [];
        $editedData = $_POST['edited_data'] ?? [];
        
        $rowsToImport = [];
        foreach ($selectedRows as $index) {
            if (isset($sessionData[$index])) {
                $row = $sessionData[$index];
                if (isset($editedData[$index]['personal_number'])) {
                    $row['personal_number'] = trim($editedData[$index]['personal_number']);
                    $row['data']['personal_number'] = $row['personal_number'];
                }
                if (isset($editedData[$index]['name'])) {
                    $row['name'] = trim($editedData[$index]['name']);
                    $row['data']['name'] = $row['name'];
                }
                $rowsToImport[] = $row;
            }
        }
        
        try {
            $results = Personnel::saveImportData($rowsToImport);
            unset($_SESSION['bulk_import_preview']);
            $isBulkImport = true;
        } catch (Exception $e) {
            $error = $e->getMessage();
            $isBulkImport = true;
        }
    } elseif (!empty($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        Auth::requirePermission('bulk_import');
        $file = $_FILES['excel_file']['tmp_name'];
        try {
            $_SESSION['bulk_import_preview'] = Personnel::parseExcelForPreview($file);
            $isBulkImport = true;
        } catch (Exception $e) {
            $error = $e->getMessage();
            $isBulkImport = true;
        }
    } elseif (isset($_POST['upload_file'])) {
        $error = "Please upload a valid Excel file.";
        $isBulkImport = true;
    } else {
        try {
            $action = $_POST['action'] ?? '';
            if ($action === 'add') {
                Auth::requirePermission('manage_options');
                $name = trim($_POST['name'] ?? '');
                if ($name === '') {
                    throw new Exception('Name cannot be blank.');
                }
                $isBypass = !empty($_POST['is_bypass']);
                LookupManager::add($table, $name, $isBypass);
            } elseif ($action === 'edit') {
                Auth::requirePermission('manage_options');
                $name = trim($_POST['name'] ?? '');
                if ($name === '') {
                    throw new Exception('Name cannot be blank.');
                }
                $isBypass = ($table === 'moqs') ? !empty($_POST['is_bypass']) : null;
                LookupManager::update($table, (int)$_POST['id'], $name, $isBypass);
            } elseif ($action === 'delete') {
                Auth::requirePermission('manage_options');
                LookupManager::delete($table, (int)$_POST['id']);
            } elseif ($action === 'update_manpower') {
                Auth::requirePermission('edit_manpower_state');
                $db = getDB();
                $stmt = $db->prepare("UPDATE manpower_state SET auth = ?, posted = ?, att = ? WHERE category = ?");
                foreach (['OFFR','SWO','WO','SGT','CPL','LCPL','SNK','NC(E)','NC(U)'] as $cat) {
                    $a = (int)($_POST['auth'][$cat] ?? 0);
                    $p = (int)($_POST['posted'][$cat] ?? 0);
                    $at = (int)($_POST['att'][$cat] ?? 0);
                    $stmt->execute([$a, $p, $at, $cat]);
                }
                $customKeys = $_POST['custom_absent_keys'] ?? [];
                $customLabels = $_POST['custom_absent_labels'] ?? [];
                $customStatuses = [];
                if (is_array($customLabels)) {
                    foreach ($customLabels as $idx => $cLabel) {
                        $cLabel = trim((string)$cLabel);
                        if ($cLabel !== '') {
                            $cKey = trim((string)($customKeys[$idx] ?? ''));
                            $customStatuses[] = [
                                'key' => $cKey,
                                'label' => $cLabel
                            ];
                        }
                    }
                }
                AppSettings::setCustomAbsentStatuses($customStatuses);
                header("Location: manage_options.php?table=$table");
                exit;
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
        }
        header("Location: manage_options.php?table=$table" . ($message ? '&error=' . urlencode($message) : ''));
        exit;
    }
}

$previewData = $_SESSION['bulk_import_preview'] ?? null;
if (!$isBulkImport) {
    $message = $_GET['error'] ?? '';
    $items = LookupManager::getAll($table);

    $db = getDB();
    $mpRows = $db->query("SELECT category, auth, posted, att FROM manpower_state")->fetchAll(PDO::FETCH_ASSOC);
    $mpState = [];
    foreach ($mpRows as $r) {
        $mpState[$r['category']] = ['auth' => $r['auth'], 'posted' => $r['posted'], 'att' => $r['att']];
    }
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
              <a href="manage_options.php?table=<?= $key ?>" class="list-group-item list-group-item-action <?= (!$isBulkImport && $table===$key)?'active':'' ?>"><?= $label ?></a>
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
        
        <?php if (Auth::hasPermission('bulk_import')): ?>
        <div class="card sidebar-card p-3 mb-4">
          <div class="fw-semibold mb-3">Bulk Import</div>
          <a href="manage_options.php?action=bulk_import" class="btn w-100 <?= $isBulkImport ? 'btn-primary' : 'btn-outline-primary' ?>">Import Personnel</a>
        </div>
        <?php endif; ?>
      </div>

      <div class="col-md-9">
        <?php if ($isBulkImport): ?>
            <!-- Bulk Import UI -->
            <?php if ($error): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($previewData !== null): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Preview Import Data</h6>
                        <form method="post" class="m-0">
                            <?= Auth::csrfField() ?>
                            <button type="submit" name="cancel_preview" class="btn btn-sm btn-outline-danger">Cancel</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Review the extracted data below. You can edit the Personal Number and Name directly. Uncheck rows you do not want to import.</p>
                        <form method="post">
                            <?= Auth::csrfField() ?>
                            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                <table class="table table-sm table-bordered table-hover mb-3">
                                    <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                        <tr>
                                            <th style="width: 50px;"><input type="checkbox" id="checkAllPreview" checked></th>
                                            <th>Row</th>
                                            <th>Personal Number</th>
                                            <th>Name</th>
                                            <th>Status Message</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($previewData as $index => $r): ?>
                                            <tr class="<?= $r['status'] === 'error' ? 'table-danger' : '' ?>">
                                                <td>
                                                    <?php if ($r['status'] !== 'error'): ?>
                                                        <input type="checkbox" name="import_rows[]" class="preview-check" value="<?= $index ?>" checked>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $r['row'] ?></td>
                                                <td>
                                                    <?php if ($r['status'] !== 'error'): ?>
                                                        <input type="text" name="edited_data[<?= $index ?>][personal_number]" class="form-control form-control-sm" value="<?= htmlspecialchars($r['personal_number'] ?? '') ?>">
                                                    <?php else: ?>
                                                        <?= htmlspecialchars($r['personal_number'] ?? '') ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($r['status'] !== 'error'): ?>
                                                        <input type="text" name="edited_data[<?= $index ?>][name]" class="form-control form-control-sm" value="<?= htmlspecialchars($r['name'] ?? '') ?>">
                                                    <?php else: ?>
                                                        <?= htmlspecialchars($r['name'] ?? '') ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small text-muted"><?= htmlspecialchars($r['message']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" name="confirm_import" class="btn btn-success">
                                <i class="ti ti-check me-1"></i> Confirm Import
                            </button>
                        </form>
                    </div>
                </div>
                <script>
                    document.getElementById('checkAllPreview')?.addEventListener('change', function() {
                        document.querySelectorAll('.preview-check').forEach(cb => cb.checked = this.checked);
                    });
                </script>
            <?php else: ?>
                <div class="card shadow-sm mb-4">
                  <div class="card-body">
                    <p class="text-muted small">Upload an Excel (.xlsx) file to add or update multiple personnel records at once. You will be able to preview and edit the data before confirming the import.</p>
                    
                    <form method="post" enctype="multipart/form-data" class="d-flex align-items-end gap-3">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="upload_file" value="1">
                        <div>
                            <label class="form-label small">Select Excel File</label>
                            <input type="file" name="excel_file" class="form-control" accept=".xlsx" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-eye me-1"></i> Preview Data
                        </button>
                        <a href="download_template.php" class="btn btn-outline-secondary ms-auto">
                            <i class="ti ti-download me-1"></i> Download Template
                        </a>
                    </form>
                  </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($results)): ?>
            <div class="card shadow-sm">
              <div class="card-header bg-white">
                <h6 class="mb-0">Import Results</h6>
              </div>
              <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Row</th>
                      <th>Personal Number</th>
                      <th>Name</th>
                      <th>Status</th>
                      <th>Message</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr class="<?= $r['status'] === 'success' ? 'table-success' : 'table-danger' ?>">
                      <td><?= $r['row'] ?></td>
                      <td><?= htmlspecialchars($r['personal_number'] ?? '') ?></td>
                      <td><?= htmlspecialchars($r['name'] ?? '') ?></td>
                      <td>
                        <?php if ($r['status'] === 'success'): ?>
                            <span class="badge bg-success">Success</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Failed</span>
                        <?php endif; ?>
                      </td>
                      <td class="small"><?= htmlspecialchars($r['message']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Manage Options UI -->
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
                  <?php if ($table === 'moqs'): ?>
                    <div class="col-md-6">
                      <input type="text" name="name" class="form-control" placeholder="New <?= strtolower(rtrim($categories[$table],'s')) ?> name" required>
                    </div>
                    <div class="col-md-3">
                      <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_bypass" value="1" id="add_moq_bypass">
                        <label class="form-check-label small" for="add_moq_bypass">Bypass (Auto-qualify)</label>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <button class="btn btn-primary w-100"><i class="ti ti-plus me-1"></i>Add</button>
                    </div>
                  <?php else: ?>
                    <div class="col-md-9">
                      <input type="text" name="name" class="form-control" placeholder="New <?= strtolower(rtrim($categories[$table],'s')) ?> name" required>
                    </div>
                    <div class="col-md-3">
                      <button class="btn btn-primary w-100"><i class="ti ti-plus me-1"></i>Add</button>
                    </div>
                  <?php endif; ?>
                </form>
              </div>
            </div>

            <div class="card shadow-sm">
              <div class="card-body">
                <div class="list-group options-list">
                  <?php foreach ($items as $item): ?>
                    <div class="list-group-item py-3">
                      <div class="d-flex gap-2 align-items-center">
                        <form method="post" class="flex-grow-1 d-flex gap-2 align-items-center flex-wrap">
                          <?= Auth::csrfField() ?>
                          <input type="hidden" name="action" value="edit">
                          <input type="hidden" name="id" value="<?= $item['id'] ?>">
                          <input type="text" name="name" class="form-control form-control-sm" style="flex: 1 1 200px;" value="<?= htmlspecialchars($item['name']) ?>">
                          <?php if ($table === 'moqs'): ?>
                            <div class="form-check form-switch mb-0 d-flex align-items-center gap-1" title="Enable to treat this MOQ as automatically bypassed/waived for promotion qualification">
                              <input class="form-check-input" type="checkbox" name="is_bypass" value="1" id="bypass_<?= $item['id'] ?>" <?= !empty($item['is_bypass']) ? 'checked' : '' ?>>
                              <label class="form-check-label small <?= !empty($item['is_bypass']) ? 'text-warning fw-bold' : 'text-muted' ?>" for="bypass_<?= $item['id'] ?>" style="font-size: 0.78rem;">
                                Bypass
                              </label>
                            </div>
                          <?php endif; ?>
                          <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                        </form>
                        <form method="post" data-confirm="Delete this option?">
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
        <div class="row g-3 mb-3">
          <div class="col-12 border-bottom pb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div>
                <label class="form-label fw-bold mb-0">Custom Absent State Status Names</label>
                <div class="form-text text-muted small mt-0">Add custom absent status names shown in Absent State table, personnel status dropdowns & profile.</div>
              </div>
              <button type="button" class="btn btn-sm btn-outline-primary" id="addCustomAbsentBtn">
                <i class="ti ti-plus me-1"></i>Add Status Field
              </button>
            </div>
            
            <div id="customAbsentFieldsContainer" class="d-flex flex-column gap-2 mt-2">
              <?php 
              $customAbsentStatuses = AppSettings::getCustomAbsentStatuses();
              foreach ($customAbsentStatuses as $cKey => $cLabel): 
              ?>
                <div class="input-group input-group-sm custom-absent-row" style="max-width: 460px;">
                  <input type="hidden" name="custom_absent_keys[]" value="<?= htmlspecialchars($cKey) ?>">
                  <input type="text" name="custom_absent_labels[]" class="form-control" value="<?= htmlspecialchars($cLabel) ?>" placeholder="e.g. Others, Detention, Hospitalized" required>
                  <button type="button" class="btn btn-outline-danger remove-custom-absent-btn" title="Remove field">
                    <i class="ti ti-trash"></i>
                  </button>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="row g-3">
          <?php foreach (['OFFR','SWO','WO','SGT','CPL','LCPL','SNK','NC(E)','NC(U)'] as $c): ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
  const addBtn = document.getElementById('addCustomAbsentBtn');
  const container = document.getElementById('customAbsentFieldsContainer');

  if (addBtn && container) {
    addBtn.addEventListener('click', function() {
      const div = document.createElement('div');
      div.className = 'input-group input-group-sm custom-absent-row';
      div.style.maxWidth = '460px';
      div.innerHTML = `
        <input type="hidden" name="custom_absent_keys[]" value="">
        <input type="text" name="custom_absent_labels[]" class="form-control" placeholder="e.g. Detention, ERE, Hospitalized" required>
        <button type="button" class="btn btn-outline-danger remove-custom-absent-btn" title="Remove field">
          <i class="ti ti-trash"></i>
        </button>
      `;
      container.appendChild(div);
      div.querySelector('input[type="text"]').focus();
    });

    container.addEventListener('click', function(e) {
      const btn = e.target.closest('.remove-custom-absent-btn');
      if (btn) {
        const rows = container.querySelectorAll('.custom-absent-row');
        if (rows.length > 1) {
          btn.closest('.custom-absent-row').remove();
        } else {
          const input = btn.closest('.custom-absent-row').querySelector('input[type="text"]');
          if (input) input.value = '';
        }
      }
    });
  }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
