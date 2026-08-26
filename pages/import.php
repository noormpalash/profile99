<?php
declare(strict_types=1);
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Personnel.php';
Auth::requirePermission('bulk_import');
$withSidebar = true;

$results = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf($_POST['csrf_token'] ?? null);
    
    if (isset($_POST['cancel_preview'])) {
        unset($_SESSION['bulk_import_preview']);
    } elseif (isset($_POST['confirm_import'])) {
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
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (!empty($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['excel_file']['tmp_name'];
        try {
            $_SESSION['bulk_import_preview'] = Personnel::parseExcelForPreview($file);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['upload_file'])) {
        $error = "Please upload a valid Excel file.";
    }
}
$previewData = $_SESSION['bulk_import_preview'] ?? null;
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="row">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10">
    <div class="page-header mb-3">
      <div>
        <h4>Bulk Import Personnel</h4>
      </div>
    </div>

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

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
