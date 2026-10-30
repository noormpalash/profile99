<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/AppSettings.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/UpdaterService.php';
require_once __DIR__ . '/../classes/DatabaseBackupService.php';
Auth::requirePermission('app_settings');

$withSidebar = true;
$message = '';
$error = '';

$uploadDir = __DIR__ . '/../uploads/';

// Ensure uploads directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf($_POST['csrf_token'] ?? null);
    
    if (isset($_POST['run_system_update'])) {
        Auth::requirePermission('system_update');
        $file = $_FILES['zip_file'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $mime = mime_content_type($file['tmp_name']);
            if (in_array($mime, ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'])) {
                try {
                    $updater = new UpdaterService();
                    $migrationsRun = $updater->updateFromZip($file['tmp_name']);
                    $message = "Update successful! Application files updated.";
                    if (!empty($migrationsRun)) {
                        $message .= " Migrations run: " . implode(', ', $migrationsRun);
                    }
                } catch (Exception $e) {
                    error_log("System update error: " . $e->getMessage());
                    $error = "Update failed: " . htmlspecialchars($e->getMessage());
                }
            } else {
                $error = "Invalid file type. Please upload a ZIP file.";
            }
        } else {
            $error = "Upload failed.";
        }
    } elseif (isset($_POST['remove_logo'])) {
        $currentLogo = AppSettings::get('app_logo_path');
        if ($currentLogo) {
            $filePath = $uploadDir . $currentLogo;
            if (is_file($filePath)) {
                unlink($filePath);
            }
            AppSettings::set('app_logo_path', '');
            $message = 'App logo removed successfully. Restored to default icon.';
        }
    } elseif (isset($_POST['action']) && in_array($_POST['action'], ['download', 'restore'])) {
        if (!Auth::hasPermission('database_management')) {
            $error = 'Access denied for database backup/restore.';
        } else {
            $backupService = new DatabaseBackupService();
            if ($_POST['action'] === 'download') {
                try {
                    $backupFile = $backupService->export();
                    
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/sql');
                    header('Content-Disposition: attachment; filename="army_personnel_db_' . date('Y_m_d_His') . '.sql"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($backupFile));
                    
                    readfile($backupFile);
                    unlink($backupFile);
                    exit;
                } catch (Exception $e) {
                    $error = 'Failed to generate backup: ' . $e->getMessage();
                }
            } elseif ($_POST['action'] === 'restore') {
                if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Please select a valid SQL backup file to upload.';
                } else {
                    $tmpFile = $_FILES['backup_file']['tmp_name'];
                    $fileName = $_FILES['backup_file']['name'];
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    if ($ext !== 'sql') {
                        $error = 'Invalid file format. Only .sql files are allowed.';
                    } else {
                        try {
                            $backupService->import($tmpFile);
                            $message = 'Database restored successfully.';
                        } catch (Exception $e) {
                            $error = 'Restore failed: ' . $e->getMessage();
                        }
                    }
                }
            }
        }
    } else {
        $appName = trim($_POST['app_name'] ?? '');
        $appTitle = trim($_POST['app_title'] ?? '');
        
        if ($appName === '' || $appTitle === '') {
            $error = 'App Name and Title are required.';
        } else {
            try {
                AppSettings::set('app_name', $appName);
                AppSettings::set('app_title', $appTitle);
                $message = 'App settings updated successfully.';
                
                // Handle File Upload
                if (isset($_FILES['app_logo_file']) && $_FILES['app_logo_file']['error'] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['app_logo_file']['tmp_name'];
                    $fileName = $_FILES['app_logo_file']['name'];
                    $fileSize = $_FILES['app_logo_file']['size'];
                    $fileType = mime_content_type($tmpName);
                    
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
                    if (!in_array($fileType, $allowedTypes)) {
                        $error = 'Invalid file type. Only JPG, PNG, GIF, and SVG are allowed.';
                    } elseif ($fileSize > 2 * 1024 * 1024) { // 2MB limit
                        $error = 'File size exceeds the 2MB limit.';
                    } else {
                        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                        $newFileName = 'app_logo_' . time() . '.' . $ext;
                        $destPath = $uploadDir . $newFileName;
                        
                        if (move_uploaded_file($tmpName, $destPath)) {
                            // Delete old logo if exists
                            $oldLogo = AppSettings::get('app_logo_path');
                            if ($oldLogo && is_file($uploadDir . $oldLogo)) {
                                unlink($uploadDir . $oldLogo);
                            }
                            AppSettings::set('app_logo_path', $newFileName);
                            $message = 'App settings and logo updated successfully.';
                        } else {
                            $error = 'Failed to save the uploaded logo file.';
                        }
                    }
                }
            } catch (Exception $e) {
                $error = 'Failed to update settings: ' . $e->getMessage();
            }
        }
    }
}

$currentLogoPath = AppSettings::get('app_logo_path');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

  .settings-page {
    font-family: 'Plus Jakarta Sans', sans-serif;
  }
  .settings-page .page-header h4 {
    font-weight: 800;
    font-size: 1.6rem;
    letter-spacing: -0.03em;
    background: linear-gradient(135deg, #1e293b, #475569);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .settings-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, 0.12);
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    padding: 24px;
    height: 100%;
  }
  .settings-card h5 {
    font-weight: 700;
    font-size: 1.1rem;
    color: #1e293b;
  }
</style>

<div class="row settings-page">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10">
    <div class="page-header mb-4">
      <div>
        <h4>App Settings</h4>
        <div class="text-muted" style="font-size: 0.85rem;">Configure core application branding, updates, and database.</div>
      </div>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-success py-2"><i class="ti ti-check me-2"></i><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger py-2"><i class="ti ti-alert-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
      <div class="col-lg-6">
        <div class="settings-card">
          <h5 class="mb-3"><i class="ti ti-settings me-2 text-primary"></i>Application Branding</h5>
          <form method="post" enctype="multipart/form-data">
            <?= Auth::csrfField() ?>
            
            <div class="mb-3">
              <label class="form-label fw-medium text-dark" style="font-size:0.9rem;">Application Name</label>
              <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars(AppSettings::get('app_name', 'Unit Personnel System')) ?>" required>
            </div>
            
            <div class="mb-3">
              <label class="form-label fw-medium text-dark" style="font-size:0.9rem;">Application Title (Browser Tab)</label>
              <input type="text" name="app_title" class="form-control" value="<?= htmlspecialchars(AppSettings::get('app_title', 'Army Personnel System')) ?>" required>
            </div>
            
            <div class="mb-4">
              <label class="form-label fw-medium text-dark" style="font-size:0.9rem;">Application Logo Image</label>
              
              <?php if ($currentLogoPath): ?>
                <div class="mb-3 d-flex align-items-center gap-3 p-3 border rounded bg-light">
                  <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($currentLogoPath) ?>" alt="App Logo" style="height: 40px; width: auto; object-fit: contain;">
                  <div>
                    <button type="button" name="remove_logo" value="1" class="btn btn-sm btn-outline-danger" data-confirm-click="Remove logo and use default icon?">Remove Logo</button>
                  </div>
                </div>
              <?php else: ?>
                <div class="mb-3 d-flex align-items-center gap-3 p-3 border rounded bg-light">
                  <div class="d-flex align-items-center justify-content-center bg-white border rounded" style="width: 40px; height: 40px;">
                    <i class="ti ti-shield fs-4 text-primary"></i>
                  </div>
                  <div class="text-muted small">Using Default Icon</div>
                </div>
              <?php endif; ?>

              <input type="file" name="app_logo_file" class="form-control form-control-sm" accept="image/png, image/jpeg, image/gif, image/svg+xml">
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Save Settings</button>
          </form>
        </div>
      </div>
      
      <?php if (Auth::hasPermission('system_update')): ?>
      <div class="col-lg-6">
        <div class="settings-card">
          <h5 class="mb-3"><i class="ti ti-upload me-2 text-warning"></i>System Updater</h5>
          <p class="text-muted mb-4" style="font-size:0.85rem;">Upload a new application ZIP to update features. Your database and data files (e.g., uploads) will be preserved.</p>
          
          <form method="post" enctype="multipart/form-data">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="run_system_update" value="1">
            
            <div class="mb-4">
              <label class="form-label fw-medium text-dark" style="font-size:0.9rem;">Application ZIP File</label>
              <input type="file" name="zip_file" class="form-control" accept=".zip" required>
            </div>
            
            <button type="submit" class="btn btn-warning w-100" data-confirm-click="Are you sure you want to install this update?">Install Update</button>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <?php if (Auth::hasPermission('database_management')): ?>
    <div class="row g-4 mb-4">
      <div class="col-12">
        <h5 class="fw-bold mb-0 border-bottom pb-2" style="font-size: 1.2rem; color: #1e293b; font-family: 'Plus Jakarta Sans', sans-serif;">Database Management</h5>
      </div>
      
      <div class="col-lg-6">
        <div class="settings-card border-primary" style="border-left: 4px solid #0d6efd;">
          <h5 class="mb-3"><i class="ti ti-download me-2 text-primary"></i>Download Backup</h5>
          <p class="text-muted mb-4" style="font-size:0.85rem;">Generate and download a complete SQL backup of the current database.</p>
          <form method="post" action="">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="action" value="download">
            <button type="submit" class="btn btn-primary w-100">
              Download SQL Backup
            </button>
          </form>
        </div>
      </div>
      
      <div class="col-lg-6">
        <div class="settings-card border-danger" style="border-left: 4px solid #dc3545;">
          <h5 class="mb-3 text-danger"><i class="ti ti-upload me-2"></i>Restore Backup</h5>
          <p class="text-muted mb-3" style="font-size:0.85rem;">Upload an SQL backup file to restore the database. <strong class="text-danger">Warning: This overwrites all current data!</strong></p>
          <form method="post" action="" enctype="multipart/form-data">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="action" value="restore">
            <div class="mb-3">
              <input type="file" name="backup_file" class="form-control form-control-sm" accept=".sql" required>
            </div>
            <button type="submit" class="btn btn-danger w-100" data-confirm-click="Are you absolutely sure you want to restore the database? ALL CURRENT DATA WILL BE LOST!">
              Restore Database
            </button>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
