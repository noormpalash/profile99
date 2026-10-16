<?php
declare(strict_types=1);

require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/LookupManager.php';
require_once __DIR__ . '/../classes/AppSettings.php';
require_once __DIR__ . '/../config/db.php';

Auth::requirePermission('leave_seniority');

$withSidebar = true;
$db = getDB();

// Filter inputs
$q = trim((string) ($_GET['q'] ?? ''));
$platoonId = trim((string) ($_GET['platoon_id'] ?? ''));
$rankId = trim((string) ($_GET['rank_id'] ?? ''));
$unitId = trim((string) ($_GET['unit_id'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$currentlyOnLeave = !empty($_GET['currently_on_leave']);
$withoutWeekend = !empty($_GET['without_weekend']);
$rawFm = trim((string) ($_GET['family_member'] ?? ''));
$familyMember = match (strtolower($rawFm)) {
  'yes', 'family member', 'family_member', '1' => 'Yes',
  'no', 'not family member', 'not_family_member', '0' => 'No',
  default => ''
};

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
if ($statusFilter !== '') {
  if ($statusFilter === 'active_only') {
    $where[] = "p.status = 'active'";
  } else {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
  }
}
if ($currentlyOnLeave) {
  $where[] = "EXISTS (SELECT 1 FROM personnel_leaves cur_l WHERE cur_l.personnel_id = p.id AND CURRENT_DATE >= cur_l.from_date AND CURRENT_DATE <= cur_l.to_date)";
}
if ($familyMember !== '') {
  if ($familyMember === 'Yes') {
    $where[] = "pf.family_member = 'Yes'";
  } elseif ($familyMember === 'No') {
    $where[] = "(pf.family_member = 'No' OR pf.family_member IS NULL)";
  }
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$whereLeave1 = $withoutWeekend ? "WHERE pl_inner.leave_type != 'Weekend Leave'" : "";
$whereLeave2 = $withoutWeekend ? "WHERE leave_type != 'Weekend Leave'" : "";
$whereAnd = $withoutWeekend ? "AND" : "WHERE";

$sql = "
SELECT 
    p.id, p.personal_number, p.name, p.photo_path, p.status,
    r.name AS rank_name, u.name AS unit_name, pl.name AS platoon_name,
    pf.family_member,
    latest_leave.leave_type AS last_leave_type,
    latest_leave.from_date AS last_leave_from,
    latest_leave.to_date AS last_leave_to,
    latest_leave.total_days AS last_leave_days,
    DATEDIFF(CURRENT_DATE, latest_leave.to_date) AS days_since_last_leave,
    EXISTS (SELECT 1 FROM personnel_leaves cur_l WHERE cur_l.personnel_id = p.id AND CURRENT_DATE >= cur_l.from_date AND CURRENT_DATE <= cur_l.to_date) AS is_currently_on_leave
FROM personnel p
LEFT JOIN ranks r ON p.rank_id = r.id
LEFT JOIN units u ON p.unit_id = u.id
LEFT JOIN platoons pl ON p.platoon_id = pl.id
LEFT JOIN personnel_family pf ON p.id = pf.personnel_id
LEFT JOIN (
    SELECT pl1.personnel_id, pl1.leave_type, pl1.from_date, pl1.to_date, pl1.total_days
    FROM personnel_leaves pl1
    INNER JOIN (
        SELECT MAX(id) AS max_id
        FROM personnel_leaves pl_inner
        $whereLeave1
        $whereAnd (personnel_id, to_date) IN (
            SELECT personnel_id, MAX(to_date)
            FROM personnel_leaves
            $whereLeave2
            GROUP BY personnel_id
        )
        GROUP BY personnel_id
    ) pl_max ON pl1.id = pl_max.max_id
) latest_leave ON p.id = latest_leave.personnel_id
$whereSql
ORDER BY 
    CASE WHEN latest_leave.to_date IS NULL THEN 0 ELSE 1 END ASC,
    latest_leave.to_date ASC,
    p.name ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$seniorityList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCount = count($seniorityList);
$currentlyOnLeaveCount = 0;
$over30DaysCount = 0;
$over60DaysCount = 0;

foreach ($seniorityList as $row) {
  if (!empty($row['last_leave_to'])) {
    if (!empty($row['is_currently_on_leave'])) {
      $currentlyOnLeaveCount++;
    }
    if ($row['days_since_last_leave'] !== null) {
      $d = (int) $row['days_since_last_leave'];
      if ($d >= 30) {
        $over30DaysCount++;
      }
      if ($d >= 60) {
        $over60DaysCount++;
      }
    }
  }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
  /* ── Stat Cards ── */
  .stat-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 22px 20px;
    border: 1px solid rgba(226, 232, 240, 0.6);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
    position: relative;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.15), 0 4px 12px -2px rgba(0, 0, 0, 0.1);
  }

  .sc-total {
    background: linear-gradient(135deg, #6366f1, #a855f7);
    border-color: transparent;
  }

  .sc-active {
    background: linear-gradient(135deg, #10b981, #34d399);
    border-color: transparent;
  }

  .sc-leave {
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
    border-color: transparent;
  }

  .sc-moq-no {
    background: linear-gradient(135deg, #ef4444, #f87171);
    border-color: transparent;
  }

  .stat-card .stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.1;
    margin-bottom: 4px;
    letter-spacing: -0.02em;
  }

  .stat-card .stat-label {
    font-size: 0.68rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    text-transform: uppercase;
    letter-spacing: 0.02em;
    line-height: 1.4;
    white-space: nowrap;
  }

  .stat-card .stat-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 3rem;
    opacity: 0.2;
    color: #ffffff;
  }

  /* ── Filter Card ── */
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

  .filter-toggle-pill {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 6px 14px;
    border-radius: 999px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    user-select: none;
    margin: 0;
  }

  .filter-toggle-pill:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
  }

  .filter-toggle-pill.active {
    background: #eff6ff;
    border-color: #93c5fd;
    box-shadow: 0 1px 3px rgba(59, 130, 246, 0.08);
  }

  .filter-toggle-pill .form-check-input {
    cursor: pointer;
    width: 2em;
    height: 1.15em;
    margin: 0;
  }

  .filter-toggle-pill .pill-text {
    font-size: 0.82rem;
    font-weight: 600;
    color: #475569;
  }

  .filter-toggle-pill.active .pill-text {
    color: #1d4ed8;
  }

  /* ── Seniority Rank Badges ── */
  .rank-badge-gold {
    background: linear-gradient(135deg, #fbbf24, #d97706);
    color: #fff;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.8rem;
    box-shadow: 0 2px 6px rgba(217, 119, 6, 0.3);
  }

  .rank-badge-silver {
    background: linear-gradient(135deg, #94a3b8, #64748b);
    color: #fff;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.8rem;
  }

  .rank-badge-bronze {
    background: linear-gradient(135deg, #b45309, #78350f);
    color: #fff;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.8rem;
  }

  .rank-badge-default {
    background: #f1f5f9;
    color: #334155;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.8rem;
    border: 1px solid #e2e8f0;
  }

  .person-avatar-sm {
    width: 38px;
    height: 38px;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #e2e8f0;
    flex-shrink: 0;
  }

  .leave-type-pill {
    font-size: 0.76rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 6px;
    background: #f8fafc;
    color: #334155;
    border: 1px solid #e2e8f0;
    display: inline-block;
  }

  .print-text-only {
    display: none;
  }

  /* ── Print Media ── */
  @media print {
    @page {
      size: landscape;
      margin: 8mm 8mm 8mm 8mm;
    }

    body {
      background: #ffffff !important;
      color: #000000 !important;
      font-size: 10pt !important;
    }

    .print-text-only {
      display: inline !important;
    }

    .no-print,
    .sidebar-column,
    .navbar,
    .page-header,
    .stat-card,
    .filter-panel,
    .btn-print,
    .app-sidebar,
    footer {
      display: none !important;
    }

    .print-table {
      width: 100% !important;
      border-collapse: collapse !important;
      margin-top: 8px;
    }

    .print-table th,
    .print-table td {
      border: 1px solid #222 !important;
      padding: 6px 8px !important;
      font-size: 9.5pt !important;
      color: #000000 !important;
      background: transparent !important;
    }

    .print-table th {
      background-color: #f1f5f9 !important;
      font-weight: bold !important;
      text-align: center;
      -webkit-print-color-adjust: exact;
    }

    .col-md-9,
    .col-lg-10 {
      width: 100% !important;
      max-width: 100% !important;
      flex: 0 0 100% !important;
    }
  }
</style>

<div class="row seniority-page">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10">
    <!-- Header -->
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
      <div>
        <h4><i class="ti ti-calendar-stats text-primary me-2"></i>Leave Seniority</h4>
        <div class="text-muted" style="font-size: 0.85rem;">
          Personnel leave priority state.
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <?php if (Auth::hasPermission('print_records')): ?>
          <button type="button" class="btn-print no-print" onclick="printLeaveSeniority()">
            <i class="ti ti-printer"></i> Print
          </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Stat Cards (Matching Dashboard Brand Aesthetic) -->
    <div class="row g-3 mb-4 no-print">
      <div class="col-xl-3 col-sm-6">
        <div class="stat-card sc-total">
          <div class="stat-value"><?= sprintf('%02d', $totalCount) ?></div>
          <div class="stat-label">TOTAL PERSONNEL</div>
          <i class="ti ti-users stat-icon"></i>
        </div>
      </div>
      <div class="col-xl-3 col-sm-6">
        <div class="stat-card sc-leave">
          <div class="stat-value"><?= sprintf('%02d', $over30DaysCount) ?></div>
          <div class="stat-label">LAST LEAVE &ge; 30 DAYS AGO</div>
          <i class="ti ti-history stat-icon"></i>
        </div>
      </div>
      <div class="col-xl-3 col-sm-6">
        <div class="stat-card sc-active">
          <div class="stat-value"><?= sprintf('%02d', $over60DaysCount) ?></div>
          <div class="stat-label">LAST LEAVE &ge; 60 DAYS AGO</div>
          <i class="ti ti-calendar-time stat-icon"></i>
        </div>
      </div>
      <div class="col-xl-3 col-sm-6">
        <div class="stat-card sc-moq-no">
          <div class="stat-value"><?= sprintf('%02d', $currentlyOnLeaveCount) ?></div>
          <div class="stat-label">CURRENTLY ON LEAVE</div>
          <i class="ti ti-calendar-off stat-icon"></i>
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-panel p-3 p-md-4 mb-4 no-print">
      <form method="get" class="mb-0">
        <!-- Main Form Inputs -->
        <div class="row g-3">
          <div class="col-xl-3 col-lg-4 col-md-6">
            <label class="form-label mb-1"><i class="ti ti-search me-1 text-muted"></i>Search</label>
            <div class="input-group">
              <span class="input-group-text bg-light text-muted border-end-0"
                style="border-top-right-radius: 0; border-bottom-right-radius: 0;">
                <i class="ti ti-search"></i>
              </span>
              <input type="text" name="q" class="form-control border-start-0 ps-0" value="<?= htmlspecialchars($q) ?>"
                placeholder="Name or personal no..." style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
            </div>
          </div>
          <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
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
          <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
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
          <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
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
          <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
            <label class="form-label mb-1"><i class="ti ti-users-group me-1 text-muted"></i>Family Member</label>
            <select name="family_member" class="form-select">
              <option value="">All</option>
              <option value="Yes" <?= $familyMember === 'Yes' ? 'selected' : '' ?>>Family Member</option>
              <option value="No" <?= $familyMember === 'No' ? 'selected' : '' ?>>Not Family Member</option>
            </select>
          </div>
        </div>

        <!-- Toggles & Action Buttons Footer -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-3 mt-3 border-top">
          <div class="d-flex flex-wrap align-items-center gap-2">
            <label class="filter-toggle-pill <?= $currentlyOnLeave ? 'active' : '' ?>">
              <div class="form-check form-switch m-0 p-0 d-flex align-items-center">
                <input class="form-check-input m-0" type="checkbox" name="currently_on_leave" value="1"
                  id="filterCurrentlyOnLeave" <?= $currentlyOnLeave ? 'checked' : '' ?> onchange="this.form.submit()">
              </div>
              <span class="pill-text">
                <i class="ti ti-calendar-off me-1"></i>Currently on leave
              </span>
            </label>

            <label class="filter-toggle-pill <?= $withoutWeekend ? 'active' : '' ?>">
              <div class="form-check form-switch m-0 p-0 d-flex align-items-center">
                <input class="form-check-input m-0" type="checkbox" name="without_weekend" value="1"
                  id="filterWithoutWeekend" <?= $withoutWeekend ? 'checked' : '' ?> onchange="this.form.submit()">
              </div>
              <span class="pill-text">
                <i class="ti ti-calendar-minus me-1"></i>Without Weekend Leave
              </span>
            </label>
          </div>

          <div class="d-flex align-items-center gap-2 ms-auto">
            <?php
            $hasActiveFilters = ($q !== '' || $platoonId !== '' || $rankId !== '' || $unitId !== '' || $statusFilter !== '' || $currentlyOnLeave || $withoutWeekend || $familyMember !== '');
            if ($hasActiveFilters):
              ?>
              <a href="leave_seniority.php"
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
        </div>
      </form>
    </div>

    <!-- Leave Seniority Table Section -->
    <div id="seniorityPrintSection">
      <div class="card shadow-sm border-0 rounded-4 overflow-hidden" style="background:#fff;">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 print-table">
            <thead class="table-light">
              <tr>
                <th style="width: 50px; text-align: center;">SL</th>
                <th class="no-print" style="width: 55px; text-align: center;">Photo</th>
                <th style="width: 100px;">P No</th>
                <th>Rank</th>
                <th>Name</th>
                <th>Last Leave Period</th>
                <th style="text-align: center;">Days</th>
                <th style="text-align: center;">Elapsed</th>
                <th style="text-align: center;">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($seniorityList)): ?>
                <tr>
                  <td colspan="9" class="text-center py-5 text-muted">
                    <i class="ti ti-calendar-off fs-2 mb-2 d-block opacity-50"></i>
                    No personnel records found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($seniorityList as $idx => $row): ?>
                  <?php
                  $sl = $idx + 1;
                  $hasLeave = !empty($row['last_leave_to']);
                  $days = (int) ($row['last_leave_days'] ?? 0);
                  $daysSince = $row['days_since_last_leave'];
                  $isCurOnLeave = !empty($row['is_currently_on_leave']);

                  $elapsedFormatted = '';
                  if ($hasLeave && $daysSince !== null && $daysSince > 0) {
                    try {
                      $fromDt = new DateTime(date('Y-m-d', strtotime($row['last_leave_to'])));
                      $todayDt = new DateTime('today');
                      $diff = $fromDt->diff($todayDt);
                      $diffMonths = ($diff->y * 12) + $diff->m;
                      $diffDays = $diff->d;
                      if ($diffMonths > 0 && $diffDays > 0) {
                        $elapsedFormatted = "{$diffMonths}m {$diffDays} d";
                      } elseif ($diffMonths > 0) {
                        $elapsedFormatted = "{$diffMonths}m";
                      } else {
                        $elapsedFormatted = "{$diffDays} d";
                      }
                    } catch (\Throwable $e) {
                      $diffMonths = intdiv((int) $daysSince, 30);
                      $diffDays = (int) $daysSince % 30;
                      if ($diffMonths > 0 && $diffDays > 0) {
                        $elapsedFormatted = "{$diffMonths}m {$diffDays} d";
                      } elseif ($diffMonths > 0) {
                        $elapsedFormatted = "{$diffMonths}m";
                      } else {
                        $elapsedFormatted = "{$diffDays} d";
                      }
                    }
                  }
                  ?>
                  <tr>
                    <!-- Seniority SL -->
                    <td class="text-center">
                      <?php if ($sl === 1): ?>
                        <span class="rank-badge-gold no-print">01</span>
                      <?php elseif ($sl === 2): ?>
                        <span class="rank-badge-silver no-print">02</span>
                      <?php elseif ($sl === 3): ?>
                        <span class="rank-badge-bronze no-print">03</span>
                      <?php else: ?>
                        <span class="rank-badge-default no-print"><?= sprintf('%02d', $sl) ?></span>
                      <?php endif; ?>
                      <span class="print-text-only"><?= sprintf('%02d', $sl) ?></span>
                    </td>

                    <!-- Photo (Screen Only) -->
                    <td class="no-print text-center">
                      <img src="<?= htmlspecialchars(personnelPhotoUrl($row['photo_path'] ?? null)) ?>"
                        class="person-avatar-sm" alt="Photo">
                    </td>

                    <!-- Personal No -->
                    <td><?= htmlspecialchars($row['personal_number']) ?></td>

                    <!-- Rank -->
                    <td><?= htmlspecialchars($row['rank_name'] ?? '-') ?></td>

                    <!-- Name -->
                    <td>
                      <a href="profile.php?id=<?= $row['id'] ?>" class="text-decoration-none text-dark no-print"
                        target="_blank">
                        <?= htmlspecialchars($row['name']) ?>
                      </a>
                      <?php if (($row['family_member'] ?? '') === 'Yes'): ?>
                        <span class="badge bg-info-subtle text-info border border-info-subtle no-print ms-1" style="font-size:0.65rem;" title="Family Member">FM</span>
                      <?php endif; ?>
                      <span class="print-text-only"><?= htmlspecialchars($row['name']) ?><?= ($row['family_member'] ?? '') === 'Yes' ? ' (FM)' : '' ?></span>
                    </td>

                    <!-- Last Leave Period -->
                    <td>
                      <?php if ($hasLeave): ?>
                        <?= date('d M Y', strtotime($row['last_leave_from'])) ?> -
                        <?= date('d M Y', strtotime($row['last_leave_to'])) ?>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>

                    <!-- Duration in Days -->
                    <td class="text-center">
                      <?php if ($hasLeave && $days > 0): ?>
                        <?= $days ?> Days
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>

                    <!-- Elapsed / Seniority Context -->
                    <td class="text-center">
                      <?php if ($hasLeave && $daysSince !== null): ?>
                        <?php if ($isCurOnLeave || $daysSince < 0): ?>
                          <span class="badge bg-warning text-dark no-print">On Leave</span>
                          <span class="print-text-only">On Leave</span>
                        <?php elseif ($daysSince === 0): ?>
                          <span class="badge bg-info text-dark no-print">Ends Today</span>
                          <span class="print-text-only">Ends Today</span>
                        <?php else: ?>
                          <span class="text-secondary small no-print" title="<?= (int) $daysSince ?> days ago"><?= htmlspecialchars($elapsedFormatted) ?></span>
                          <span class="print-text-only"><?= htmlspecialchars($elapsedFormatted) ?></span>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle no-print">Never</span>
                        <span class="print-text-only">Never</span>
                      <?php endif; ?>
                    </td>

                    <!-- Military Status -->
                    <td class="text-center">
                      <?php
                      $st = strtolower((string) $row['status']);
                      $badgeClass = match ($st) {
                        'active' => 'bg-success',
                        'on_leave' => 'bg-warning text-dark',
                        'cmh', 'suspend', 'awol' => 'bg-danger',
                        default => 'bg-secondary'
                      };
                      $customAbsentStatuses = AppSettings::getCustomAbsentStatuses();
                      $statusText = $customAbsentStatuses[$st] ?? ($st === 'others' ? (reset($customAbsentStatuses) ?: 'Others') : ucfirst(str_replace('_', ' ', $st)));
                      ?>
                      <span class="badge <?= $badgeClass ?> no-print"><?= htmlspecialchars($statusText) ?></span>
                      <span class="print-text-only"><?= htmlspecialchars($statusText) ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  const currentWithoutWeekend = <?= $withoutWeekend ? 'true' : 'false' ?>;
  const currentCurrentlyOnLeave = <?= $currentlyOnLeave ? 'true' : 'false' ?>;

  function printLeaveSeniority(defaultHeading = 'LEAVE SENIORITY STATE', defaultSubHeading = '<?= htmlspecialchars(AppSettings::get('app_title', 'Unit Personnel System')) ?>', defaultDate = '<?= date('Y-m-d') ?>', defaultPageSize = 'A4') {
    Swal.fire({
      title: 'Print Options',
      html:
        '<input id="printHeading" class="swal2-input mb-2" placeholder="Heading" value="' + defaultHeading + '" style="max-width: 90%; margin: 10px auto;">' +
        '<input id="printSubHeading" class="swal2-input" placeholder="Sub-heading (optional)" value="' + defaultSubHeading + '" style="max-width: 90%; margin: 10px auto;">' +
        '<div style="text-align:left; width: 90%; margin: 10px auto 0; font-size:14px;">Date (optional):</div>' +
        '<input type="date" id="printDate" class="swal2-input" value="' + defaultDate + '" style="max-width: 90%; margin: 5px auto 10px;">' +
        '<div style="text-align:left; width: 90%; margin: 10px auto 0; font-size:14px;">Page Size:</div>' +
        '<select id="printPageSize" class="swal2-select" style="max-width: 90%; margin: 5px auto 10px;">' +
        '<option value="A4" ' + (defaultPageSize === 'A4' ? 'selected' : '') + '>A4 Landscape</option>' +
        '<option value="Legal" ' + (defaultPageSize === 'Legal' ? 'selected' : '') + '>Legal Landscape</option>' +
        '<option value="Letter" ' + (defaultPageSize === 'Letter' ? 'selected' : '') + '>Letter Landscape</option>' +
        '</select>' +
        '<div class="form-check form-switch text-start" style="max-width: 90%; margin: 10px auto 4px;">' +
        '<input class="form-check-input" type="checkbox" id="printCurrentlyOnLeave" ' + (currentCurrentlyOnLeave ? 'checked' : '') + '>' +
        '<label class="form-check-label fw-bold small text-dark ms-1" for="printCurrentlyOnLeave">Currently on leave</label>' +
        '</div>' +
        '<div class="form-check form-switch text-start" style="max-width: 90%; margin: 6px auto 4px;">' +
        '<input class="form-check-input" type="checkbox" id="printWithoutWeekend" ' + (currentWithoutWeekend ? 'checked' : '') + '>' +
        '<label class="form-check-label fw-bold small text-dark ms-1" for="printWithoutWeekend">Without Weekend Leave</label>' +
        '</div>',
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: '<i class="ti ti-printer"></i> Print',
      preConfirm: () => {
        return {
          heading: document.getElementById('printHeading').value.trim() || 'LEAVE SENIORITY STATE',
          subHeading: document.getElementById('printSubHeading').value.trim(),
          date: document.getElementById('printDate').value,
          pageSize: document.getElementById('printPageSize').value,
          currentlyOnLeave: document.getElementById('printCurrentlyOnLeave').checked,
          withoutWeekend: document.getElementById('printWithoutWeekend').checked
        }
      }
    }).then((result) => {
      if (!result.isConfirmed) return;

      let currentlyOnLeaveVal = result.value.currentlyOnLeave;
      let withoutWeekendVal = result.value.withoutWeekend;
      let heading = result.value.heading;
      let subHeading = result.value.subHeading;
      let dateVal = result.value.date;
      let pageSize = result.value.pageSize;

      // If toggle differs from current data state, reload with new filter and auto-print
      if (currentlyOnLeaveVal !== currentCurrentlyOnLeave || withoutWeekendVal !== currentWithoutWeekend) {
        const url = new URL(window.location.href);
        if (currentlyOnLeaveVal) {
          url.searchParams.set('currently_on_leave', '1');
        } else {
          url.searchParams.delete('currently_on_leave');
        }
        if (withoutWeekendVal) {
          url.searchParams.set('without_weekend', '1');
        } else {
          url.searchParams.delete('without_weekend');
        }
        url.searchParams.set('auto_print', '1');
        url.searchParams.set('print_heading', heading);
        url.searchParams.set('print_subheading', subHeading);
        url.searchParams.set('print_date', dateVal);
        url.searchParams.set('print_page_size', pageSize);
        window.location.href = url.toString();
        return;
      }

      executeDirectPrint(heading, subHeading, dateVal, pageSize);
    });
  }

  function executeDirectPrint(heading, subHeading, dateVal, pageSize) {
    let dynStyle = document.getElementById('dynamicPrintStyle');
    if (!dynStyle) {
      dynStyle = document.createElement('style');
      dynStyle.id = 'dynamicPrintStyle';
      document.head.appendChild(dynStyle);
    }
    dynStyle.innerHTML = `@page { size: ${pageSize} landscape; margin: 0.35in 0.35in 0.35in 0.35in; }`;

    let headingEl = document.getElementById("customPrintHeading");
    if (!headingEl) {
      headingEl = document.createElement("div");
      headingEl.id = "customPrintHeading";
      headingEl.style.cssText = "text-align:center; margin-bottom: 15px; display: none;";
      headingEl.className = "print-only";
      headingEl.innerHTML = '<h3 style="font-weight: bold; margin-bottom: 3px; font-size: 16pt; text-transform: uppercase;"></h3><h5 style="font-weight: normal; font-size: 11pt; margin:0; color:#333;"></h5><div class="printDateDisplay" style="text-align:right; font-weight: bold; margin-top:6px; font-size: 9pt;"></div>';
      document.querySelector('.col-md-9, .col-lg-10').prepend(headingEl);
    }

    headingEl.querySelector('h3').innerText = heading;
    headingEl.querySelector('h5').innerText = subHeading;
    headingEl.querySelector('h5').style.display = subHeading ? 'block' : 'none';

    let dateDisplay = headingEl.querySelector('.printDateDisplay');
    if (dateVal) {
      const d = new Date(dateVal);
      dateDisplay.innerText = "Date: " + d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
      dateDisplay.style.display = 'block';
    } else {
      dateDisplay.style.display = 'none';
    }

    headingEl.style.display = 'block';

    const parent = document.querySelector('.col-md-9, .col-lg-10');
    const children = parent.children;
    const hidden = [];
    const sec = document.getElementById('seniorityPrintSection');

    for (let i = 0; i < children.length; i++) {
      const child = children[i];
      if (child.id === 'customPrintHeading' || child === sec || child.contains(sec)) continue;
      child.style.display = 'none';
      hidden.push(child);
    }

    setTimeout(() => {
      window.print();
      hidden.forEach(el => el.style.display = '');
      headingEl.style.display = 'none';
    }, 250);
  }

  // Auto-trigger print if redirected with auto_print=1
  <?php if (!empty($_GET['auto_print']) && Auth::hasPermission('print_records')): ?>
    document.addEventListener('DOMContentLoaded', () => {
      const url = new URL(window.location.href);
      const heading = <?= json_encode($_GET['print_heading'] ?? 'LEAVE SENIORITY STATE') ?>;
      const subHeading = <?= json_encode($_GET['print_subheading'] ?? AppSettings::get('app_title', 'Unit Personnel System')) ?>;
      const dateVal = <?= json_encode($_GET['print_date'] ?? date('Y-m-d')) ?>;
      const pageSize = <?= json_encode($_GET['print_page_size'] ?? 'A4') ?>;

      // Clean URL parameters
      url.searchParams.delete('auto_print');
      url.searchParams.delete('print_heading');
      url.searchParams.delete('print_subheading');
      url.searchParams.delete('print_date');
      url.searchParams.delete('print_page_size');
      window.history.replaceState({}, document.title, url.toString());

      setTimeout(() => {
        executeDirectPrint(heading, subHeading, dateVal, pageSize);
      }, 300);
    });
  <?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>