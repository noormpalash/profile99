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
    if (!empty($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['excel_file']['tmp_name'];
        try {
            $results = Personnel::importExcel($file);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = "Please upload a valid Excel file.";
    }
}
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

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <p class="text-muted small">Upload an Excel (.xlsx) file to add or update multiple personnel records at once. If a Personal Number already exists, their information will be updated.</p>
        
        <form method="post" enctype="multipart/form-data" class="d-flex align-items-end gap-3">
            <?= Auth::csrfField() ?>
            <div>
                <label class="form-label small">Select Excel File</label>
                <input type="file" name="excel_file" class="form-control" accept=".xlsx" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-upload me-1"></i> Upload & Import
            </button>
            <a href="download_template.php" class="btn btn-outline-secondary ms-auto">
                <i class="ti ti-download me-1"></i> Download Template
            </a>
        </form>
      </div>
    </div>

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
