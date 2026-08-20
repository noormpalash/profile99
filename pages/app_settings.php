<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/AppSettings.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/UpdaterService.php';
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
                    $error = "Update Error: " . $e->getMessage();
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

<div class="row">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10">
    <div class="page-header">
      <div>
        <h4>App Settings</h4>
        <div class="text-muted">Configure core application branding and visuals.</div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-6">
        <div class="card shadow-sm p-4">
          <?php if ($message): ?>
            <div class="alert alert-success py-2"><i class="ti ti-check me-2"></i><?= htmlspecialchars($message) ?></div>
          <?php endif; ?>
          <?php if ($error): ?>
            <div class="alert alert-danger py-2"><i class="ti ti-alert-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          
          <form method="post" enctype="multipart/form-data">
            <?= Auth::csrfField() ?>
            
            <div class="mb-4">
              <label class="form-label fw-medium">Application Name</label>
              <div class="text-muted small mb-2">This is the text displayed next to the logo in the top navigation bar.</div>
              <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars(AppSettings::get('app_name', 'Unit Personnel System')) ?>" required>
            </div>
            
            <div class="mb-4">
              <label class="form-label fw-medium">Application Title (Browser Tab)</label>
              <div class="text-muted small mb-2">This text appears in the browser tab and search engine results.</div>
              <input type="text" name="app_title" class="form-control" value="<?= htmlspecialchars(AppSettings::get('app_title', 'Army Personnel System')) ?>" required>
            </div>
            
            <div class="mb-4">
              <label class="form-label fw-medium">Application Logo Image</label>
              <div class="text-muted small mb-2">Upload a PNG, JPG, or SVG file. Max size 2MB. Recommended height 64px.</div>
              
              <?php if ($currentLogoPath): ?>
                <div class="mb-3 d-flex align-items-center gap-3 p-3 border rounded bg-light">
                  <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($currentLogoPath) ?>" alt="App Logo" style="height: 48px; width: auto; object-fit: contain;">
                  <div>
                    <div class="fw-medium">Current Logo</div>
                    <button type="button" name="remove_logo" value="1" class="btn btn-sm btn-outline-danger mt-1" data-confirm-click="Are you sure you want to remove the logo and use the default icon?">Remove Logo</button>
                  </div>
                </div>
              <?php else: ?>
                <div class="mb-3 d-flex align-items-center gap-3 p-3 border rounded bg-light">
                  <div class="d-flex align-items-center justify-content-center bg-white border rounded" style="width: 48px; height: 48px;">
                    <i class="ti ti-shield-star fs-3 text-primary"></i>
                  </div>
                  <div>
                    <div class="fw-medium text-muted">Using Default Icon</div>
                  </div>
                </div>
              <?php endif; ?>

              <input type="file" name="app_logo_file" class="form-control" accept="image/png, image/jpeg, image/gif, image/svg+xml">
            </div>
            
            <hr class="my-4">
            
            <div class="d-flex justify-content-end gap-2">
              <button type="submit" class="btn btn-primary px-4">Save Settings</button>
            </div>
          </form>
        </div>
      </div>
      
      <?php if (Auth::hasPermission('system_update')): ?>
      <div class="col-lg-6">
        <div class="card shadow-sm p-4">
          <h5 class="card-title fw-bold mb-3">System Updater</h5>
          <p class="text-muted small mb-4">Upload a new application ZIP to update features. Your database and data files (e.g., uploads) will be preserved.</p>
          
          <form method="post" enctype="multipart/form-data">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="run_system_update" value="1">
            
            <div class="mb-4">
              <label class="form-label fw-medium">Application ZIP File</label>
              <input type="file" name="zip_file" class="form-control" accept=".zip" required>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
              <button type="submit" class="btn btn-warning px-4" data-confirm-click="Are you sure you want to install this update?">Install Update</button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
