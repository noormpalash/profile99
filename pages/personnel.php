<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Personnel.php';
require_once __DIR__ . '/../classes/LookupManager.php';
require_once __DIR__ . '/../config/db.php';
Auth::requirePermission('view_personnel');
$withSidebar = true;

$filters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'rank_id' => (string)($_GET['rank_id'] ?? ''),
    'unit_id' => (string)($_GET['unit_id'] ?? ''),
    'blood_group_id' => (string)($_GET['blood_group_id'] ?? ''),
    'status' => trim((string)($_GET['status'] ?? '')),
    'district' => trim((string)($_GET['district'] ?? '')),
    'course_id' => (string)($_GET['course_id'] ?? ''),
    'moq_id' => (string)($_GET['moq_id'] ?? ''),
    'moq_status' => trim((string)($_GET['moq_status'] ?? '')),
    'cadre_id' => (string)($_GET['cadre_id'] ?? ''),
    'marital_status' => trim((string)($_GET['marital_status'] ?? '')),
    'platoon_id' => (string)($_GET['platoon_id'] ?? ''),
    'family_member' => trim((string)($_GET['family_member'] ?? '')),
];

$hasFilters = implode('', $filters) !== '';
$people = $hasFilters ? Personnel::filter($filters) : Personnel::all();
$ranks = LookupManager::getAll('ranks');
$units = LookupManager::getAll('units');
$bloodGroups = LookupManager::getAll('blood_groups');
$courses = LookupManager::getAll('courses');
$moqs = LookupManager::getAll('moqs');
$cadres = LookupManager::getAll('cadres');
$platoons = LookupManager::getAll('platoons');
$districts = bangladeshDistricts();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
.appointment-badge{display:inline-block;background:#eef2ff;color:#1d4ed8;padding:4px 8px;border-radius:999px;font-weight:700;font-size:0.82rem;margin-left:8px}
.name-cell{display:flex;align-items:center;gap:8px}
</style>

<div class="row">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10">
    <div class="page-header">
      <div>
        <h4>Personnel</h4>
        <div class="text-muted">Manage records and use advanced filters to narrow the list.</div>
      </div>
      <a href="add_personnel.php" class="btn btn-primary btn-sm">Add personnel</a>
    </div>

    <div class="card shadow-sm mb-4 dashboard-filter-card" style="border-radius: 16px; padding: 0;">
      <form method="get" class="mb-0">
        <!-- Main Search Bar -->
        <div class="p-3 d-flex align-items-center gap-2 flex-wrap bg-white" style="border-radius: 16px;">
          <div class="input-group dashboard-search-group flex-grow-1">
            <span class="input-group-text"><i class="ti ti-search"></i></span>
            <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Search by name or personal no..." autofocus>
          </div>
          <button class="btn btn-primary px-4 fw-medium" type="submit">Search</button>
          <button class="btn btn-light border text-secondary fw-medium px-3" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters" aria-expanded="false" aria-controls="advancedFilters">
            <i class="ti ti-adjustments-horizontal me-1"></i> <span>Advanced</span>
          </button>
          <?php if ($hasFilters): ?>
            <a href="personnel.php" class="btn btn-outline-danger px-3 fw-medium" title="Clear all filters">
              <i class="ti ti-x"></i> <span>Clear</span>
            </a>
          <?php endif; ?>
        </div>

        <!-- Advanced Filters Collapsible -->
        <div class="collapse <?= $hasFilters ? 'show' : '' ?>" id="advancedFilters">
          <div class="advanced-filters-collapse p-4 border-top" style="background: #f8fafc; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label small text-secondary fw-medium">Rank</label>
                <select name="rank_id" class="form-select">
                  <option value="">All ranks</option>
                  <?php foreach ($ranks as $rank): ?>
                    <option value="<?= $rank['id'] ?>" <?= $filters['rank_id'] !== '' && (int)$filters['rank_id'] === (int)$rank['id'] ? 'selected' : '' ?>><?= htmlspecialchars($rank['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small text-secondary fw-medium">Unit</label>
                <select name="unit_id" class="form-select">
                  <option value="">All units</option>
                  <?php foreach ($units as $unit): ?>
                    <option value="<?= $unit['id'] ?>" <?= $filters['unit_id'] !== '' && (int)$filters['unit_id'] === (int)$unit['id'] ? 'selected' : '' ?>><?= htmlspecialchars($unit['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small text-secondary fw-medium">Status</label>
                <select name="status" class="form-select">
                  <option value="">All statuses</option>
                  <?php foreach (['active','on_leave','training','punishment','retired'] as $status): ?>
                    <option value="<?= $status ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $status)) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small text-secondary fw-medium">District</label>
                <input type="text" name="district" class="form-control" list="dashboardDistricts" value="<?= htmlspecialchars($filters['district']) ?>" placeholder="Search district">
                <datalist id="dashboardDistricts">
                  <?php foreach ($districts as $district): ?>
                    <option value="<?= htmlspecialchars($district) ?>"></option>
                  <?php endforeach; ?>
                </datalist>
              </div>
              
              <!-- Additional Advanced Filters -->
              <div class="col-md-3">
                <label class="form-label small text-secondary fw-medium">Course</label>
                <select name="course_id" class="form-select">
                  <option value="">All courses</option>
                  <?php foreach ($courses as $course): ?>
                    <option value="<?= $course['id'] ?>" <?= $filters['course_id'] !== '' && (int)$filters['course_id'] === (int)$course['id'] ? 'selected' : '' ?>><?= htmlspecialchars($course['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small text-secondary fw-medium">MOQ</label>
                <select name="moq_id" class="form-select">
                  <option value="">All MOQs</option>
                  <?php foreach ($moqs as $moq): ?>
                    <option value="<?= $moq['id'] ?>" <?= $filters['moq_id'] !== '' && (int)$filters['moq_id'] === (int)$moq['id'] ? 'selected' : '' ?>><?= htmlspecialchars($moq['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small text-secondary fw-medium">MOQ Status</label>
                <select name="moq_status" class="form-select">
                  <option value="">Any</option>
                  <option value="qualified" <?= $filters['moq_status'] === 'qualified' ? 'selected' : '' ?>>Qualified</option>
                  <option value="not_qualified" <?= $filters['moq_status'] === 'not_qualified' ? 'selected' : '' ?>>Not Qualified</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small text-secondary fw-medium">Cadre</label>
                <select name="cadre_id" class="form-select">
                  <option value="">All cadres</option>
                  <?php foreach ($cadres as $cadre): ?>
                    <option value="<?= $cadre['id'] ?>" <?= $filters['cadre_id'] !== '' && (int)$filters['cadre_id'] === (int)$cadre['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cadre['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small text-secondary fw-medium">Platoon</label>
                <select name="platoon_id" class="form-select">
                  <option value="">All platoons</option>
                  <?php foreach ($platoons as $platoon): ?>
                    <option value="<?= $platoon['id'] ?>" <?= $filters['platoon_id'] !== '' && (int)$filters['platoon_id'] === (int)$platoon['id'] ? 'selected' : '' ?>><?= htmlspecialchars($platoon['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small text-secondary fw-medium">Marital Status</label>
                <select name="marital_status" class="form-select">
                  <option value="">All statuses</option>
                  <?php foreach (['unmarried','married','widow','divorced'] as $ms): ?>
                    <option value="<?= $ms ?>" <?= $filters['marital_status'] === $ms ? 'selected' : '' ?>><?= ucfirst($ms) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small text-secondary fw-medium">Blood Group</label>
                <select name="blood_group_id" class="form-select">
                  <option value="">All blood groups</option>
                  <?php foreach ($bloodGroups as $bg): ?>
                    <option value="<?= $bg['id'] ?>" <?= $filters['blood_group_id'] !== '' && (int)$filters['blood_group_id'] === (int)$bg['id'] ? 'selected' : '' ?>><?= htmlspecialchars($bg['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small text-secondary fw-medium">Family Member</label>
                <select name="family_member" class="form-select">
                  <option value="">Any</option>
                  <option value="Yes" <?= $filters['family_member'] === 'Yes' ? 'selected' : '' ?>>Yes</option>
                  <option value="No" <?= $filters['family_member'] === 'No' ? 'selected' : '' ?>>No</option>
                </select>
              </div>
            </div>
            <div class="mt-4 text-end">
              <a href="personnel.php" class="btn btn-link text-secondary text-decoration-none me-2">Clear filters</a>
              <button type="submit" class="btn btn-dark px-4">Apply Filters</button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <div class="card shadow-sm p-3 mb-4">
      <div class="table-responsive">
        <table id="personnelTable" class="table table-hover align-middle mb-0">
          <thead>
            <tr><th>Photo</th><th>Name</th><th>Personal No.</th><th>Rank</th><th class="text-end">Actions</th></tr>
          </thead>
          <tbody>
      <?php foreach ($people as $p): ?>
        <tr>
          <td><img src="<?= htmlspecialchars(personnelPhotoUrl($p['photo_path'] ?? null)) ?>" class="rounded-circle" width="36" height="36" style="object-fit:cover"></td>
          <td>
            <div class="name-cell">
              <div><?= htmlspecialchars($p['name']) ?></div>
              <?php if (!empty($p['appointment_name'])): ?><div class="appointment-badge"><?= htmlspecialchars($p['appointment_name']) ?></div><?php endif; ?>
            </div>
          </td>
          <td><?= htmlspecialchars($p['personal_number']) ?></td>
          <td><?= htmlspecialchars($p['rank_name'] ?? '-') ?></td>

          <td class="text-end">
            <a href="profile.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
            <?php if (Auth::hasAnyPermission(['edit_personnel_basic', 'edit_personnel_course', 'edit_personnel_education', 'edit_personnel_service', 'edit_personnel_family', 'edit_personnel_health', 'edit_personnel_social', 'edit_personnel_notes', 'edit_personnel_leaves', 'edit_personnel_cadres', 'edit_personnel_moqs', 'edit_personnel_ipft', 'edit_personnel_yearly_plan', 'edit_personnel_status', 'edit_personnel_family_member_status'])): ?>
                <a href="edit_personnel.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <?php endif; ?>
            <?php if (Auth::hasPermission('delete_personnel')): ?>
                <form method="post" action="delete_personnel.php" class="d-inline" onsubmit="return confirm('Delete this record permanently?')">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
