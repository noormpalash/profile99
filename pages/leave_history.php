<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Personnel.php';
require_once __DIR__ . '/../classes/LookupManager.php';
require_once __DIR__ . '/../classes/AppSettings.php';
require_once __DIR__ . '/../config/db.php';

Auth::requirePermission('leave_history');

$withSidebar = true;
$db = getDB();

// Flash messages
$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Filter inputs
$q = trim((string) ($_GET['q'] ?? ''));
$platoonId = trim((string) ($_GET['platoon_id'] ?? ''));
$rankId = trim((string) ($_GET['rank_id'] ?? ''));
$unitId = trim((string) ($_GET['unit_id'] ?? ''));

// Lookup data
$platoons = LookupManager::getAll('platoons');
$ranks = LookupManager::getAll('ranks');
$units = LookupManager::getAll('units');

// Build query
$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(p.name LIKE ? OR p.personal_number LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($platoonId !== '') {
    $where[] = "p.platoon_id = ?";
    $params[] = (int) $platoonId;
}
if ($rankId !== '') {
    $where[] = "p.rank_id = ?";
    $params[] = (int) $rankId;
}
if ($unitId !== '') {
    $where[] = "p.unit_id = ?";
    $params[] = (int) $unitId;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$currentYear = (int) date('Y');

$sql = "
SELECT 
    p.id, p.personal_number, p.name, p.photo_path, p.status,
    r.name AS rank_name, u.name AS unit_name,
    COALESCE(all_leaves.total_days_all, 0) AS total_days_all,
    COALESCE(all_leaves.leave_count_all, 0) AS leave_count_all,
    COALESCE(year_leaves.total_days_year, 0) AS total_days_year,
    COALESCE(year_leaves.leave_count_year, 0) AS leave_count_year
FROM personnel p
LEFT JOIN ranks r ON p.rank_id = r.id
LEFT JOIN units u ON p.unit_id = u.id
LEFT JOIN (
    SELECT personnel_id, 
           SUM(total_days) AS total_days_all,
           COUNT(*) AS leave_count_all
    FROM personnel_leaves
    GROUP BY personnel_id
) all_leaves ON p.id = all_leaves.personnel_id
LEFT JOIN (
    SELECT personnel_id, 
           SUM(total_days) AS total_days_year,
           COUNT(*) AS leave_count_year
    FROM personnel_leaves
    WHERE YEAR(from_date) = ?
    GROUP BY personnel_id
) year_leaves ON p.id = year_leaves.personnel_id
$whereSql
ORDER BY p.name ASC
";

// Prepend current year param
array_unshift($params, $currentYear);

$stmt = $db->prepare($sql);
$stmt->execute($params);
$personnelList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prefetch all leaves grouped by personnel for expandable rows
$allLeavesStmt = $db->query("SELECT personnel_id, from_date, to_date, total_days, leave_type FROM personnel_leaves ORDER BY from_date DESC");
$allLeavesRaw = $allLeavesStmt->fetchAll(PDO::FETCH_ASSOC);

$leavesByPersonnel = [];
foreach ($allLeavesRaw as $leave) {
    $pid = (int) $leave['personnel_id'];
    $leavesByPersonnel[$pid][] = $leave;
}

$canReset = Auth::hasPermission('reset_leave_history');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
  .lh-expand-btn {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    transition: all 0.15s ease;
  }

  .lh-expand-btn:hover {
    background: #f1f5f9;
    color: #1e293b;
  }

  .lh-expand-btn.open {
    color: #3b82f6;
  }

  .lh-detail-row td {
    padding: 0 !important;
    border-top: none !important;
  }

  .lh-detail-inner {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 16px 20px;
  }

  .lh-year-group {
    margin-bottom: 12px;
  }

  .lh-year-label {
    font-weight: 700;
    font-size: 0.82rem;
    color: #475569;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .lh-year-label .year-total {
    font-weight: 600;
    font-size: 0.75rem;
    color: #3b82f6;
    background: #eff6ff;
    padding: 2px 8px;
    border-radius: 999px;
  }

  .lh-leave-table {
    width: 100%;
    font-size: 0.78rem;
    border-collapse: collapse;
  }

  .lh-leave-table th {
    background: #e2e8f0;
    color: #475569;
    font-weight: 600;
    padding: 6px 10px;
    text-align: left;
  }

  .lh-leave-table td {
    padding: 5px 10px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
  }

  .lh-leave-table tr:last-child td {
    border-bottom: none;
  }

  .days-badge {
    display: inline-block;
    min-width: 28px;
    text-align: center;
    padding: 2px 8px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.78rem;
  }

  .days-badge-current {
    background: #dbeafe;
    color: #1d4ed8;
  }

  .days-badge-all {
    background: #f1f5f9;
    color: #475569;
  }

  .reset-btn-danger {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
  }

  .reset-btn-danger:hover {
    background: #fee2e2;
    border-color: #fca5a5;
  }

  .person-avatar-sm {
    width: 36px;
    height: 36px;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #e2e8f0;
    flex-shrink: 0;
  }

  .filter-panel {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, 0.16);
    box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04), 0 2px 6px -1px rgba(15, 23, 42, 0.02);
  }

  .filter-panel .form-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: #475569;
    letter-spacing: 0.01em;
  }

  .filter-panel .form-control,
  .filter-panel .form-select,
  .filter-panel .input-group-text {
    border-radius: 10px;
    border-color: #e2e8f0;
    font-size: 0.88rem;
  }

  .filter-panel .form-control:focus,
  .filter-panel .form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
  }

  .no-leaves-row {
    color: #94a3b8;
    font-style: italic;
    font-size: 0.82rem;
  }
</style>

<div class="row seniority-page">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10">
    <!-- Header -->
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
      <div>
        <h4><i class="ti ti-history text-primary me-2"></i>Leave History</h4>
        <div class="text-muted" style="font-size: 0.85rem;">
          Full leave records for all personnel across all years.
        </div>
      </div>
      <?php if ($canReset): ?>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="resetAllLeaves()">
            <i class="ti ti-trash me-1"></i> Reset All
          </button>
        </div>
      <?php endif; ?>
    </div>

    <!-- Flash Messages -->
    <?php if ($flashSuccess !== ''): ?>
      <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="ti ti-check me-1"></i> <?= htmlspecialchars($flashSuccess) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if ($flashError !== ''): ?>
      <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="ti ti-alert-circle me-1"></i> <?= htmlspecialchars($flashError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <div class="filter-panel p-3 p-md-4 mb-4 no-print">
      <form method="get" class="mb-0">
        <div class="row g-3">
          <div class="col-lg-3 col-md-6">
            <label class="form-label mb-1"><i class="ti ti-search me-1 text-muted"></i>Search</label>
            <div class="input-group">
              <span class="input-group-text bg-light text-muted border-end-0"
                style="border-top-right-radius: 0; border-bottom-right-radius: 0;">
                <i class="ti ti-search"></i>
              </span>
              <input type="text" name="q" class="form-control border-start-0 ps-0" value="<?= htmlspecialchars($q) ?>"
                placeholder="Name or personal no..."
                style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
            </div>
          </div>
          <div class="col-lg-3 col-md-6 col-sm-6">
            <label class="form-label mb-1"><i class="ti ti-users me-1 text-muted"></i>Platoon</label>
            <select name="platoon_id" class="form-select">
              <option value="">All Platoons</option>
              <?php foreach ($platoons as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $platoonId === (string) $p['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-lg-3 col-md-6 col-sm-6">
            <label class="form-label mb-1"><i class="ti ti-military-rank me-1 text-muted"></i>Rank</label>
            <select name="rank_id" class="form-select">
              <option value="">All Ranks</option>
              <?php foreach ($ranks as $r): ?>
                <option value="<?= $r['id'] ?>" <?= $rankId === (string) $r['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($r['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-lg-3 col-md-6 col-sm-6">
            <label class="form-label mb-1"><i class="ti ti-building me-1 text-muted"></i>Unit</label>
            <select name="unit_id" class="form-select">
              <option value="">All Units</option>
              <?php foreach ($units as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $unitId === (string) $u['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($u['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
          <?php if ($q !== '' || $platoonId !== '' || $rankId !== '' || $unitId !== ''): ?>
            <a href="leave_history.php"
              class="btn btn-light border px-3 rounded-pill text-secondary fw-medium shadow-none d-inline-flex align-items-center gap-1"
              style="font-size: 0.86rem;">
              <i class="ti ti-rotate-clockwise"></i> Reset
            </a>
          <?php endif; ?>
          <button type="submit"
            class="btn btn-primary px-4 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center gap-1"
            style="font-size: 0.86rem;">
            <i class="ti ti-filter"></i> Apply Filters
          </button>
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4" style="background:#fff;">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 40px; text-align: center;">SL</th>
              <th style="width: 50px; text-align: center;">Photo</th>
              <th>P No</th>
              <th>Rank</th>
              <th>Name</th>
              <th style="text-align: center;"><?= $currentYear ?></th>
              <th style="text-align: center;">All Time</th>
              <th style="text-align: center; width: 80px;">Detail</th>
              <?php if ($canReset): ?>
                <th style="text-align: center; width: 80px;">Action</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($personnelList)): ?>
              <tr>
                <td colspan="<?= $canReset ? 9 : 8 ?>" class="text-center py-5 text-muted">
                  <i class="ti ti-history-off fs-2 mb-2 d-block opacity-50"></i>
                  No personnel records found.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($personnelList as $idx => $row): ?>
                <tr>
                  <td class="text-center text-muted"><?= $idx + 1 ?></td>
                  <td class="text-center">
                    <img src="<?= htmlspecialchars(personnelPhotoUrl($row['photo_path'] ?? null)) ?>"
                      class="person-avatar-sm" alt="Photo">
                  </td>
                  <td><?= htmlspecialchars($row['personal_number']) ?></td>
                  <td><?= htmlspecialchars($row['rank_name'] ?? '-') ?></td>
                  <td>
                    <a href="profile.php?id=<?= $row['id'] ?>" class="text-decoration-none text-dark" target="_blank">
                      <?= htmlspecialchars($row['name']) ?>
                    </a>
                  </td>
                  <td class="text-center">
                    <?php if ((int) $row['total_days_year'] > 0): ?>
                      <span class="days-badge days-badge-current"><?= (int) $row['total_days_year'] ?> d</span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <?php if ((int) $row['total_days_all'] > 0): ?>
                      <span class="days-badge days-badge-all"><?= (int) $row['total_days_all'] ?> d</span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <?php if ((int) $row['leave_count_all'] > 0): ?>
                      <button type="button" class="lh-expand-btn" data-pid="<?= $row['id'] ?>"
                        onclick="toggleDetail(this, <?= $row['id'] ?>)">
                        <i class="ti ti-chevron-down"></i>
                      </button>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <?php if ($canReset): ?>
                    <td class="text-center">
                      <?php if ((int) $row['leave_count_all'] > 0): ?>
                        <button type="button" class="reset-btn-danger"
                          onclick="resetIndividual(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['name']), ENT_QUOTES) ?>')">
                          <i class="ti ti-trash"></i>
                        </button>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                  <?php endif; ?>
                </tr>
                <!-- Expandable detail row (hidden by default) -->
                <?php if ((int) $row['leave_count_all'] > 0): ?>
                  <tr class="lh-detail-row" id="detail-<?= $row['id'] ?>" style="display: none;">
                    <td colspan="<?= $canReset ? 9 : 8 ?>">
                      <div class="lh-detail-inner">
                        <?php
                        $personLeaves = $leavesByPersonnel[(int) $row['id']] ?? [];
                        $byYear = [];
                        foreach ($personLeaves as $lv) {
                            $yr = $lv['from_date'] ? (int) date('Y', strtotime($lv['from_date'])) : 0;
                            $byYear[$yr][] = $lv;
                        }
                        krsort($byYear);

                        foreach ($byYear as $year => $yearLeaves):
                            $yearTotal = 0;
                            foreach ($yearLeaves as $yl) {
                                $yearTotal += (int) ($yl['total_days'] ?? 0);
                            }
                        ?>
                          <div class="lh-year-group">
                            <div class="lh-year-label">
                              <i class="ti ti-calendar"></i> <?= $year ?: 'Unknown' ?>
                              <span class="year-total"><?= $yearTotal ?> days</span>
                            </div>
                            <table class="lh-leave-table">
                              <thead>
                                <tr>
                                  <th>From</th>
                                  <th>To</th>
                                  <th>Days</th>
                                  <th>Type</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($yearLeaves as $lv): ?>
                                  <tr>
                                    <td><?= $lv['from_date'] ? date('d M Y', strtotime($lv['from_date'])) : '-' ?></td>
                                    <td><?= $lv['to_date'] ? date('d M Y', strtotime($lv['to_date'])) : '-' ?></td>
                                    <td><?= (int) ($lv['total_days'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars($lv['leave_type'] ?? '-') ?></td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  function toggleDetail(btn, pid) {
    const row = document.getElementById('detail-' + pid);
    if (!row) return;
    const isOpen = row.style.display !== 'none';
    row.style.display = isOpen ? 'none' : 'table-row';
    btn.classList.toggle('open', !isOpen);
    btn.querySelector('i').className = isOpen ? 'ti ti-chevron-down' : 'ti ti-chevron-up';
  }

  function resetIndividual(pid, name) {
    Swal.fire({
      title: 'Reset Leave History?',
      html: `This will permanently delete <strong>all</strong> leave records for <strong>${name}</strong>.<br>This action cannot be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      confirmButtonText: 'Yes, Reset',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (!result.isConfirmed) return;
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'reset_leave_history.php';
      form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="action" value="reset_individual">
        <input type="hidden" name="personnel_id" value="${pid}">
      `;
      document.body.appendChild(form);
      form.submit();
    });
  }

  function resetAllLeaves() {
    Swal.fire({
      title: 'Reset ALL Leave History?',
      html: 'This will permanently delete <strong>every leave record</strong> for <strong>all personnel</strong>.<br><br>Type <strong>RESET ALL</strong> to confirm.',
      icon: 'error',
      input: 'text',
      inputPlaceholder: 'Type RESET ALL',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      confirmButtonText: 'Delete Everything',
      cancelButtonText: 'Cancel',
      preConfirm: (value) => {
        if (value !== 'RESET ALL') {
          Swal.showValidationMessage('Type RESET ALL to confirm');
          return false;
        }
        return true;
      }
    }).then((result) => {
      if (!result.isConfirmed) return;
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'reset_leave_history.php';
      form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="action" value="reset_all">
      `;
      document.body.appendChild(form);
      form.submit();
    });
  }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
