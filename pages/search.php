<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Personnel.php';
require_once __DIR__ . '/../classes/LookupManager.php';
require_once __DIR__ . '/../config/db.php';
Auth::requireLogin();
$withSidebar = true;

$filters = [
  'q' => trim((string) ($_GET['q'] ?? '')),
  'rank_id' => (string) ($_GET['rank_id'] ?? ''),
  'unit_id' => (string) ($_GET['unit_id'] ?? ''),
  'blood_group_id' => (string) ($_GET['blood_group_id'] ?? ''),
  'status' => trim((string) ($_GET['status'] ?? '')),
  'district' => trim((string) ($_GET['district'] ?? '')),
  'course_id' => (string) ($_GET['course_id'] ?? ''),
  'moq_id' => (string) ($_GET['moq_id'] ?? ''),
  'moq_status' => trim((string) ($_GET['moq_status'] ?? '')),
  'cadre_id' => (string) ($_GET['cadre_id'] ?? ''),
  'marital_status' => trim((string) ($_GET['marital_status'] ?? '')),
  'platoon_id' => (string) ($_GET['platoon_id'] ?? ''),
  'family_member' => trim((string) ($_GET['family_member'] ?? '')),
];

$hasFilters = implode('', $filters) !== '';
$results = $hasFilters ? Personnel::filter($filters) : [];

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
  .compact-search-card {
    border-radius: 16px;
    background: #ffffff;
    border: 1px solid rgba(148, 163, 184, 0.2);
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
    overflow: hidden;
  }

  .advanced-filters-collapse {
    border-top: 1px solid rgba(148, 163, 184, 0.15);
    background: #f8fafc;
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
  }

  .modern-search-card {
    border-radius: 20px;
    background: #ffffff;
    border: 1px solid rgba(148, 163, 184, 0.15);
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease;
    position: relative;
  }

  .modern-search-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
    border-color: rgba(59, 130, 246, 0.3);
  }

  .appointment-ribbon {
    position: absolute;
    top: 24px;
    right: -8px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    padding: 6px 16px;
    font-weight: 700;
    font-size: 0.75rem;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
    border-radius: 4px 0 0 4px;
    z-index: 10;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .appointment-ribbon::after {
    content: '';
    position: absolute;
    top: 100%;
    right: 0;
    border-top: 8px solid #1e3a8a;
    border-right: 8px solid transparent;
  }
</style>

<div class="row">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10">
    <div class="px-3 pt-4 mb-4">
      <div class="compact-search-card">
        <form method="get" class="mb-0">
          <!-- Main Search Bar -->
          <div class="p-3 d-flex align-items-center gap-2 flex-wrap">
            <div class="input-group dashboard-search-group flex-grow-1">
              <span class="input-group-text"><i class="ti ti-search"></i></span>
              <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($filters['q']) ?>"
                placeholder="Search by name or personal no..." autofocus>
            </div>
            <button class="btn btn-primary px-4 fw-medium" type="submit">Search</button>
            <button class="btn btn-light border text-secondary fw-medium px-3" type="button" data-bs-toggle="collapse"
              data-bs-target="#advancedFilters" aria-expanded="false" aria-controls="advancedFilters">
              <i class="ti ti-adjustments-horizontal me-1"></i> <span>Advanced</span>
            </button>
            <?php if ($hasFilters): ?>
              <a href="search.php" class="btn btn-outline-danger px-3 fw-medium" title="Clear all filters">
                <i class="ti ti-x"></i> <span>Clear</span>
              </a>
            <?php endif; ?>
          </div>

          <!-- Advanced Filters Collapsible -->
          <div
            class="collapse <?= ($hasFilters && $filters['q'] === '' && count($results) > 0) || ($hasFilters && ($filters['rank_id'] !== '' || $filters['unit_id'] !== '' || $filters['status'] !== '' || $filters['district'] !== '' || $filters['course_id'] !== '' || $filters['moq_id'] !== '' || $filters['moq_status'] !== '' || $filters['cadre_id'] !== '' || $filters['marital_status'] !== '' || $filters['platoon_id'] !== '' || $filters['blood_group_id'] !== '' || $filters['family_member'] !== '')) ? 'show' : '' ?>"
            id="advancedFilters">
            <div class="advanced-filters-collapse p-4">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label small text-secondary fw-medium">Rank</label>
                  <select name="rank_id" class="form-select">
                    <option value="">All ranks</option>
                    <?php foreach ($ranks as $rank): ?>
                      <option value="<?= $rank['id'] ?>" <?= $filters['rank_id'] !== '' && (int) $filters['rank_id'] === (int) $rank['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rank['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-secondary fw-medium">Unit</label>
                  <select name="unit_id" class="form-select">
                    <option value="">All units</option>
                    <?php foreach ($units as $unit): ?>
                      <option value="<?= $unit['id'] ?>" <?= $filters['unit_id'] !== '' && (int) $filters['unit_id'] === (int) $unit['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($unit['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-secondary fw-medium">Status</label>
                  <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    <?php foreach (['active', 'on_leave', 'training', 'punishment', 'retired'] as $status): ?>
                      <option value="<?= $status ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_', ' ', $status)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-secondary fw-medium">District</label>
                  <input type="text" name="district" class="form-control" list="searchDistricts"
                    value="<?= htmlspecialchars($filters['district']) ?>" placeholder="Search district">
                  <datalist id="searchDistricts">
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
                      <option value="<?= $course['id'] ?>" <?= $filters['course_id'] !== '' && (int) $filters['course_id'] === (int) $course['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($course['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-secondary fw-medium">MOQ</label>
                  <select name="moq_id" class="form-select">
                    <option value="">All MOQs</option>
                    <?php foreach ($moqs as $moq): ?>
                      <option value="<?= $moq['id'] ?>" <?= $filters['moq_id'] !== '' && (int) $filters['moq_id'] === (int) $moq['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($moq['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-secondary fw-medium">MOQ Status</label>
                  <select name="moq_status" class="form-select">
                    <option value="">Any</option>
                    <option value="qualified" <?= $filters['moq_status'] === 'qualified' ? 'selected' : '' ?>>Qualified
                    </option>
                    <option value="not_qualified" <?= $filters['moq_status'] === 'not_qualified' ? 'selected' : '' ?>>Not
                      Qualified</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-secondary fw-medium">Cadre</label>
                  <select name="cadre_id" class="form-select">
                    <option value="">All cadres</option>
                    <?php foreach ($cadres as $cadre): ?>
                      <option value="<?= $cadre['id'] ?>" <?= $filters['cadre_id'] !== '' && (int) $filters['cadre_id'] === (int) $cadre['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cadre['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-secondary fw-medium">Platoon</label>
                  <select name="platoon_id" class="form-select">
                    <option value="">All platoons</option>
                    <?php foreach ($platoons as $platoon): ?>
                      <option value="<?= $platoon['id'] ?>" <?= $filters['platoon_id'] !== '' && (int) $filters['platoon_id'] === (int) $platoon['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($platoon['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-secondary fw-medium">Marital Status</label>
                  <select name="marital_status" class="form-select">
                    <option value="">All statuses</option>
                    <?php foreach (['unmarried', 'married', 'widow', 'divorced'] as $ms): ?>
                      <option value="<?= $ms ?>" <?= $filters['marital_status'] === $ms ? 'selected' : '' ?>>
                        <?= ucfirst($ms) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-secondary fw-medium">Blood Group</label>
                  <select name="blood_group_id" class="form-select">
                    <option value="">All blood groups</option>
                    <?php foreach ($bloodGroups as $bg): ?>
                      <option value="<?= $bg['id'] ?>" <?= $filters['blood_group_id'] !== '' && (int) $filters['blood_group_id'] === (int) $bg['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($bg['name']) ?></option>
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
                <a href="search.php" class="btn btn-link text-secondary text-decoration-none me-2">Clear filters</a>
                <button type="submit" class="btn btn-dark px-4">Apply Filters</button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="px-3">
      <?php if ($hasFilters): ?>
        <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
          <h5 class="text-muted fw-semibold mb-0">
            <?php if ($filters['q'] !== ''): ?>
              Search Results for "<span class="text-dark"><?= htmlspecialchars($filters['q']) ?></span>"
            <?php else: ?>
              Advanced Filter Results
            <?php endif; ?>
          </h5>
          <span class="badge bg-secondary ms-3 rounded-pill"><?= count($results) ?> found</span>
        </div>
      <?php endif; ?>

      <div class="row g-4">
        <?php foreach ($results as $p): ?>
          <div class="col-12 col-xxl-6">
            <a href="profile.php?id=<?= $p['id'] ?>" class="text-decoration-none text-dark">
              <div class="card h-100 modern-search-card">
                <?php if (!empty($p['appointment_name'])): ?>
                  <div class="appointment-ribbon"><?= htmlspecialchars($p['appointment_name']) ?></div>
                <?php endif; ?>
                <div class="card-body p-3 d-flex align-items-center gap-3">
                  <img src="<?= htmlspecialchars(personnelPhotoUrl($p['photo_path'] ?? null)) ?>"
                    class="rounded-3 shadow-sm flex-shrink-0" width="110" height="110"
                    style="object-fit:cover; border: 3px solid #f1f5f9;">
                  <div class="flex-grow-1 overflow-hidden" style="padding-right: 60px;">
                    <h6 class="mb-0 fw-bold text-dark text-truncate" style="font-size: 1.05rem;">
                      <?= htmlspecialchars($p['name']) ?></h6>
                    <div class="d-flex align-items-center gap-2">
                      <span class="fw-bold text-primary"
                        style="font-size: 1.05rem;"><?= htmlspecialchars($p['rank_name'] ?? '') ?></span>
                      <?php if (!empty($p['batch'])): ?>
                        <span class="text-muted fw-semibold" style="font-size: 0.85rem;"><i
                            class="ti ti-users me-1"></i><?= htmlspecialchars($p['batch']) ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-3 mt-2 flex-wrap" style="font-size: 0.8rem;">
                      <?php if (!empty($p['address'])): ?>
                        <span class="text-secondary"><i
                            class="ti ti-map-pin me-1"></i><?= htmlspecialchars($p['address']) ?></span>
                      <?php endif; ?>
                      <?php if (!empty($p['blood_group_name'])): ?>
                        <span class="fw-bold text-danger"><i
                            class="ti ti-droplet-filled me-1"></i><?= htmlspecialchars($p['blood_group_name']) ?></span>
                      <?php endif; ?>
                      <?php if (!empty($p['mobile_number'])): ?>
                        <span class="text-secondary"><i
                            class="ti ti-phone me-1"></i><?= htmlspecialchars($p['mobile_number']) ?></span>
                      <?php endif; ?>
                      <div class="ms-auto d-flex align-items-center gap-2">
                        <?php if (isset($p['family_member']) && $p['family_member'] === 'Yes'): ?>
                          <span class="badge bg-info" style="font-size: 0.9rem; padding: 0.4em 0.65em;">FM</span>
                        <?php endif; ?>
                        <?php if (!empty($p['living_status'])): ?>
                          <span class="badge bg-secondary"
                            style="font-size: 0.9rem; padding: 0.4em 0.65em;"><?= htmlspecialchars($p['living_status']) ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>

        <?php if ($hasFilters && empty($results)): ?>
          <div class="col-12 text-center py-5 mt-4">
            <div class="mb-3">
              <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle"
                style="width: 100px; height: 100px;">
                <i class="ti ti-user-off text-secondary" style="font-size: 48px; opacity: 0.5;"></i>
              </div>
            </div>
            <h4 class="text-dark fw-bold mb-2">No personnel found</h4>
            <p class="text-secondary">We couldn't find anyone matching your criteria.<br>Try adjusting your search terms.
            </p>
          </div>
        <?php endif; ?>

        <?php if (!$hasFilters): ?>
          <div class="col-12 text-center py-5 mt-4">
            <div class="mb-3">
              <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle"
                style="width: 100px; height: 100px;">
                <i class="ti ti-search text-secondary" style="font-size: 48px; opacity: 0.5;"></i>
              </div>
            </div>
            <h4 class="text-dark fw-bold mb-2">Start your search</h4>
            <p class="text-secondary">Enter a name or use the advanced filters above.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>