<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Personnel.php';
require_once __DIR__ . '/../classes/LookupManager.php';
require_once __DIR__ . '/../config/db.php';
Auth::requirePermission('view_dashboard');
$withSidebar = true;

$db = getDB();


// ── Manpower State Data ──
$mpRows = $db->query("SELECT category, auth, posted, att FROM manpower_state")->fetchAll(PDO::FETCH_ASSOC);
$mpState = [];
foreach ($mpRows as $r) {
  $mpState[$r['category']] = ['auth' => $r['auth'], 'posted' => $r['posted'], 'att' => $r['att'], 'present' => 0, 'absent' => 0];
}
$activeRanks = $db->query("SELECT r.name, COUNT(p.id) AS cnt FROM personnel p JOIN ranks r ON p.rank_id = r.id WHERE p.status = 'active' AND NOT EXISTS (SELECT 1 FROM personnel_leaves pl WHERE pl.personnel_id = p.id AND CURRENT_DATE >= pl.from_date AND CURRENT_DATE <= pl.to_date) GROUP BY r.name")->fetchAll(PDO::FETCH_ASSOC);
$activeRankCounts = [];
foreach ($activeRanks as $r) {
  $activeRankCounts[$r['name']] = $r['cnt'];
}
$rankMap = [
  'OFFR' => ['Major', 'Colonel'],
  'SWO' => ['Senior Warrant Officer'],
  'WO' => ['Warrant Officer'],
  'SGT' => ['Sergeant'],
  'CPL' => ['Corporal'],
  'LCPL' => ['Lance Corporal'],
  'SNK(GD)' => ['Sainik'],
  'NC(E)' => ['NC(E)'],
  'NC(U)' => ['NC(U)']
];
foreach ($rankMap as $cat => $ranks) {
  if (!isset($mpState[$cat]))
    continue;
  $present = 0;
  foreach ($ranks as $r) {
    $present += ($activeRankCounts[$r] ?? 0);
  }
  $mpState[$cat]['present'] = $present;
  $mpState[$cat]['absent'] = $mpState[$cat]['posted'] - $present;
}
$totalPersonnel = (int) $db->query("SELECT COUNT(*) FROM personnel")->fetchColumn();
$activeCount = (int) $db->query("SELECT COUNT(*) FROM personnel p WHERE p.status = 'active' AND NOT EXISTS (SELECT 1 FROM personnel_leaves pl WHERE pl.personnel_id = p.id AND CURRENT_DATE >= pl.from_date AND CURRENT_DATE <= pl.to_date)")->fetchColumn();
$onLeaveCount = (int) $db->query("SELECT COUNT(*) FROM personnel p WHERE EXISTS (SELECT 1 FROM personnel_leaves pl WHERE pl.personnel_id = p.id AND CURRENT_DATE >= pl.from_date AND CURRENT_DATE <= pl.to_date)")->fetchColumn();
$familyMembers = (int) $db->query("SELECT COUNT(*) FROM personnel_family WHERE family_member = 'Yes'")->fetchColumn();

// ── MOQ Qualification Data ──
$moqQualifiedCount = 0;
$moqNotQualifiedCount = 0;

$personnelRanks = $db->query("SELECT p.id, r.name AS rank_name FROM personnel p JOIN ranks r ON p.rank_id = r.id")->fetchAll(PDO::FETCH_ASSOC);
$allMoqs = $db->query("SELECT pm.personnel_id, m.name AS moq_name FROM personnel_moqs pm JOIN moqs m ON pm.moq_id = m.id")->fetchAll(PDO::FETCH_ASSOC);

$personnelMoqMap = [];
foreach ($allMoqs as $row) {
  $personnelMoqMap[$row['personnel_id']][] = strtolower(trim($row['moq_name']));
}

foreach ($personnelRanks as $p) {
  $rankLower = strtolower(trim($p['rank_name']));
  if (in_array($rankLower, ['sainik', 'lance corporal', 'corporal', 'sergeant', 'warrant officer'])) {
    $pMoqs = $personnelMoqMap[$p['id']] ?? [];
    $hasMoq = fn($name) => in_array(strtolower($name), $pMoqs);

    $missingMoqs = [];
    if ($rankLower === 'sainik') {
      if (!$hasMoq('btt'))
        $missingMoqs[] = 'BTT';
      if (!$hasMoq('arms commando') && !$hasMoq('services commando'))
        $missingMoqs[] = 'Arms Commando or Services Commando';
      if (!$hasMoq('pe'))
        $missingMoqs[] = 'PE';
    } elseif ($rankLower === 'lance corporal') {
      if (!$hasMoq('pc'))
        $missingMoqs[] = 'PC';
      if (!$hasMoq('pe'))
        $missingMoqs[] = 'PE';
    } elseif ($rankLower === 'corporal') {
      if (!$hasMoq('att'))
        $missingMoqs[] = 'ATT';
      if (!$hasMoq('pe'))
        $missingMoqs[] = 'PE';
    } elseif ($rankLower === 'sergeant') {
      if (!$hasMoq('ncoc'))
        $missingMoqs[] = 'NCOC';
      if (!$hasMoq('utility course'))
        $missingMoqs[] = 'Utility Course';
    } elseif ($rankLower === 'warrant officer') {
      if (!$hasMoq('clm cadre') && !$hasMoq('jcoc'))
        $missingMoqs[] = 'CLM Cadre or JCOC';
    }

    if (empty($missingMoqs)) {
      $moqQualifiedCount++;
    } else {
      $moqNotQualifiedCount++;
    }
  }
}


// ── Absent State Data ──
$absentRows = ['Pre Leave', 'C/Leave', 'W/Leave', 'R/Leave', 'M/Leave', 'CMH', 'TRG', 'CMD', 'ATT', 'GOC GD', 'Suspend', 'OSL', 'AWOL'];
$absentStateData = [];
foreach ($absentRows as $ar) {
  $absentStateData[$ar] = array_fill_keys(array_keys($rankMap), 0);
}
$leaveMap = [
  'Pre Leave' => 'Pre Leave',
  'Casual Leave' => 'C/Leave',
  'Weekend Leave' => 'W/Leave',
  'Recreation Leave' => 'R/Leave',
  'Medical Leave' => 'M/Leave',
];
$leaveCounts = $db->query("SELECT r.name as rank_name, pl.leave_type, COUNT(DISTINCT p.id) as cnt FROM personnel p JOIN ranks r ON p.rank_id = r.id JOIN personnel_leaves pl ON p.id = pl.personnel_id WHERE CURRENT_DATE >= pl.from_date AND CURRENT_DATE <= pl.to_date GROUP BY r.name, pl.leave_type")->fetchAll();
foreach ($leaveCounts as $lc) {
  $catFound = null;
  foreach ($rankMap as $cat => $ranks) {
    if (in_array($lc['rank_name'], $ranks)) {
      $catFound = $cat;
      break;
    }
  }
  if ($catFound && isset($leaveMap[$lc['leave_type']])) {
    $absentStateData[$leaveMap[$lc['leave_type']]][$catFound] += (int) $lc['cnt'];
  }
}
$statusCounts = $db->query("SELECT r.name as rank_name, p.status, COUNT(DISTINCT p.id) as cnt FROM personnel p JOIN ranks r ON p.rank_id = r.id WHERE p.status IN ('cmh', 'trg', 'cmd', 'att', 'goc_gd', 'suspend', 'osl', 'awol') GROUP BY r.name, p.status")->fetchAll();
foreach ($statusCounts as $sc) {
  $catFound = null;
  foreach ($rankMap as $cat => $ranks) {
    if (in_array($sc['rank_name'], $ranks)) {
      $catFound = $cat;
      break;
    }
  }
  if ($catFound) {
    $mapStatus = [
      'cmh' => 'CMH',
      'trg' => 'TRG',
      'cmd' => 'CMD',
      'att' => 'ATT',
      'goc_gd' => 'GOC GD',
      'suspend' => 'Suspend',
      'osl' => 'OSL',
      'awol' => 'AWOL'
    ];
    if (isset($mapStatus[$sc['status']])) {
      $absentStateData[$mapStatus[$sc['status']]][$catFound] += (int) $sc['cnt'];
    }
  }
}




// ── Currently On Leave ──
$currentlyOnLeave = $db->query("SELECT p.id, p.name, p.personal_number, p.photo_path, r.name AS rank_name, pl.leave_type, pl.from_date, pl.to_date FROM personnel p JOIN personnel_leaves pl ON p.id = pl.personnel_id JOIN ranks r ON p.rank_id = r.id WHERE CURRENT_DATE >= pl.from_date AND CURRENT_DATE <= pl.to_date ORDER BY pl.to_date LIMIT 5")->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

  .dashboard-page {
    font-family: 'Plus Jakarta Sans', sans-serif;
  }

  .dashboard-page .page-header h4 {
    font-weight: 800;
    font-size: 1.6rem;
    letter-spacing: -0.03em;
    background: linear-gradient(135deg, #1e293b, #475569);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  /* ── Stat Cards ── */
  .stat-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 24px 20px;
    border: 1px solid rgba(226, 232, 240, 0.6);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
    position: relative;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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

  .sc-family {
    background: linear-gradient(135deg, #ec4899, #f472b6);
    border-color: transparent;
  }

  .sc-moq-no {
    background: linear-gradient(135deg, #ef4444, #f87171);
    border-color: transparent;
  }


  .stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.15), 0 4px 12px -2px rgba(0, 0, 0, 0.1);
  }

  .stat-card .stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.1;
    margin-bottom: 6px;
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
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* ── Chart Cards ── */
  .chart-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, 0.12);
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    transition: all 0.3s ease;
  }

  .chart-card:hover {
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
  }

  .chart-card-header {
    padding: 16px 20px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .chart-card-header h6 {
    font-weight: 700;
    font-size: 0.92rem;
    color: #1e293b;
    margin: 0;
  }

  .chart-card-header .badge {
    font-size: 0.68rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 6px;
  }

  .chart-card-body {
    padding: 12px 16px 16px;
  }

  /* ── Recent Tables ── */
  .dash-table-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, 0.12);
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    overflow: hidden;
  }

  .dash-table-card .card-header {
    background: transparent;
    border-bottom: 1px solid rgba(148, 163, 184, 0.1);
    padding: 14px 18px;
  }

  .dash-table-card .card-header h6 {
    font-weight: 700;
    font-size: 0.9rem;
    color: #1e293b;
    margin: 0;
  }

  .dash-table-card .table {
    margin: 0;
    font-size: 0.82rem;
  }

  .dash-table-card .table th {
    background: #f8fafc;
    color: #64748b;
    font-weight: 600;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid rgba(148, 163, 184, 0.1);
  }

  .dash-table-card .table td {
    color: #475569;
    vertical-align: middle;
  }

  /* ── High-End Table Design (Double Bezel) ── */
  .premium-table-container {
    background: #f8fafc;
    padding: 6px;
    border-radius: 1.5rem;
    border: 1px solid rgba(0, 0, 0, 0.05);
  }

  .premium-table-inner {
    background: #ffffff;
    border-radius: calc(1.5rem - 6px);
    box-shadow: inset 0 1px 1px rgba(255, 255, 255, 1), 0 2px 10px rgba(15, 23, 42, 0.03);
    overflow: hidden;
  }

  .premium-table {
    margin: 0;
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.8rem;
    font-variant-numeric: tabular-nums;
  }

  .premium-table th {
    background: #ffffff;
    color: #64748b;
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 800;
    padding: 14px 10px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.1);
    text-align: center;
  }

  .premium-table td {
    padding: 12px 10px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.06);
    color: #334155;
    font-weight: 500;
    text-align: center;
    transition: background-color 0.4s cubic-bezier(0.32, 0.72, 0, 1);
  }

  .premium-table tr:hover td {
    background-color: rgba(248, 250, 252, 0.8);
  }

  .premium-table tr:last-child td {
    border-bottom: none;
  }

  .premium-table th:first-child,
  .premium-table td:first-child {
    text-align: left;
    padding-left: 24px;
    color: #0f172a;
    font-weight: 700;
  }

  .premium-table td.fw-bold {
    font-weight: 800;
  }

  .premium-table .total-row td {
    background: #f8fafc;
    font-weight: 800;
    color: #0f172a;
  }

  /* ── Colorful Rows (Manpower State) ── */
  .premium-table tr.row-auth td {
    background: rgba(59, 130, 246, 0.04);
    color: #1e3a8a;
  }

  .premium-table tr.row-auth:hover td {
    background: rgba(59, 130, 246, 0.08);
  }

  .premium-table tr.row-posted td {
    background: rgba(139, 92, 246, 0.04);
    color: #4c1d95;
  }

  .premium-table tr.row-posted:hover td {
    background: rgba(139, 92, 246, 0.08);
  }

  .premium-table tr.row-att td {
    background: rgba(245, 158, 11, 0.04);
    color: #78350f;
  }

  .premium-table tr.row-att:hover td {
    background: rgba(245, 158, 11, 0.08);
  }

  .premium-table tr.row-absent td {
    background: rgba(239, 68, 68, 0.04);
    color: #7f1d1d;
  }

  .premium-table tr.row-absent:hover td {
    background: rgba(239, 68, 68, 0.08);
  }

  .premium-table tr.row-present td {
    background: rgba(16, 185, 129, 0.08);
    color: #064e3b;
    font-weight: 800;
  }

  .premium-table tr.row-present:hover td {
    background: rgba(16, 185, 129, 0.12);
  }

  /* ── Colorful Rows (Absent State) ── */
  .premium-table.absent-table tbody tr:nth-child(4n+1) td {
    background: rgba(56, 189, 248, 0.04);
    color: #0369a1;
  }

  .premium-table.absent-table tbody tr:nth-child(4n+2) td {
    background: rgba(167, 139, 250, 0.04);
    color: #5b21b6;
  }

  .premium-table.absent-table tbody tr:nth-child(4n+3) td {
    background: rgba(251, 146, 60, 0.04);
    color: #9a3412;
  }

  .premium-table.absent-table tbody tr:nth-child(4n+4) td {
    background: rgba(52, 211, 153, 0.04);
    color: #065f46;
  }

  .premium-table.absent-table tbody tr:nth-child(4n+1):hover td {
    background: rgba(56, 189, 248, 0.08);
  }

  .premium-table.absent-table tbody tr:nth-child(4n+2):hover td {
    background: rgba(167, 139, 250, 0.08);
  }

  .premium-table.absent-table tbody tr:nth-child(4n+3):hover td {
    background: rgba(251, 146, 60, 0.08);
  }

  .premium-table.absent-table tbody tr:nth-child(4n+4):hover td {
    background: rgba(52, 211, 153, 0.08);
  }

  .premium-table.absent-table tbody tr.total-row td,
  .premium-table.absent-table tbody tr.total-row:hover td {
    background: #f8fafc;
    color: #0f172a;
  }


  .dash-table-card .table tbody tr:hover {
    background: rgba(99, 102, 241, 0.03);
  }

  .person-avatar-sm {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    object-fit: cover;
    border: 2px solid #f1f5f9;
  }

  .leave-type-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
  }

  /* Animations */
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(15px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .animate-in {
    animation: fadeInUp 0.5s ease-out forwards;
    opacity: 0;
  }

  .delay-1 {
    animation-delay: 0.05s;
  }

  .delay-2 {
    animation-delay: 0.1s;
  }

  .delay-3 {
    animation-delay: 0.15s;
  }

  .delay-4 {
    animation-delay: 0.2s;
  }

  .delay-5 {
    animation-delay: 0.25s;
  }

  .delay-6 {
    animation-delay: 0.3s;
  }

  .delay-7 {
    animation-delay: 0.35s;
  }

  .delay-8 {
    animation-delay: 0.4s;
  }

  .delay-9 {
    animation-delay: 0.45s;
  }

  .delay-10 {
    animation-delay: 0.5s;
  }
</style>

<div class="row dashboard-page">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10">
    <div class="page-header mb-4">
      <div>
        <h4>Dashboard</h4>
        <div class="text-muted" style="font-size: 0.85rem;">Overview of personnel data &amp; analytics</div>
      </div>
    </div>

    <!-- ══════ STAT CARDS ══════ -->
    <div class="row g-3 mb-4 row-cols-2 row-cols-md-3 row-cols-xl-5">
      <div class="col">
        <div class="stat-card sc-total animate-in delay-1">
          <div class="stat-value"><?= $totalPersonnel ?></div>
          <div class="stat-label" title="Total Personnel">Total Personnel</div>
        </div>
      </div>
      <div class="col">
        <div class="stat-card sc-active animate-in delay-2">
          <div class="stat-value"><?= $activeCount ?></div>
          <div class="stat-label" title="Active">Active</div>
        </div>
      </div>
      <div class="col">
        <div class="stat-card sc-leave animate-in delay-3">
          <div class="stat-value"><?= $onLeaveCount ?></div>
          <div class="stat-label" title="On leave">On leave</div>
        </div>
      </div>
      <div class="col">
        <div class="stat-card sc-family animate-in delay-4">
          <div class="stat-value"><?= $familyMembers ?></div>
          <div class="stat-label" title="FAMILY MEMBER">FAMILY MEMBER</div>
        </div>
      </div>
      <div class="col">
        <div class="stat-card sc-moq-no animate-in delay-5">
          <div class="stat-value"><?= $moqNotQualifiedCount ?></div>
          <div class="stat-label" title="MOQ NOT QUAL">MOQ NOT QUAL</div>
        </div>
      </div>
    </div>

    <!-- ══════ MANPOWER STATE ══════ -->
    <div class="row g-3 mb-4">
      <div class="col-12">
        <div class="chart-card animate-in delay-2"
          style="border: none; box-shadow: none; background: transparent; padding: 0;">
          <div class="chart-card-header px-1 pb-3 pt-0 d-flex justify-content-between align-items-center">
            <h6 style="font-size:1.1rem; letter-spacing:-0.02em;"><i class="ti ti-users-group me-2"
                style="color:#6366f1"></i>MANPOWER STATE</h6>
          </div>
          <div class="premium-table-container">
            <div class="premium-table-inner">
              <div class="table-responsive">
                <table class="premium-table align-middle">
                  <thead>
                    <tr>
                      <th style="width: 140px;">Details</th>
                      <?php foreach (['OFFR', 'SWO', 'WO', 'SGT', 'CPL', 'LCPL', 'SNK(GD)', 'NC(E)', 'NC(U)'] as $c): ?>
                        <th><?= htmlspecialchars($c) ?></th>
                      <?php endforeach; ?>
                      <th>TOTAL</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach (['auth' => 'AUTH', 'posted' => 'POSTED', 'att' => 'ATT', 'absent' => 'ABSENT', 'present' => 'PRESENT'] as $key => $label): ?>
                      <tr class="row-<?= $key ?>">
                        <td><?= $label ?></td>
                        <?php $total = 0; ?>
                        <?php foreach (['OFFR', 'SWO', 'WO', 'SGT', 'CPL', 'LCPL', 'SNK(GD)', 'NC(E)', 'NC(U)'] as $c): ?>
                          <?php $val = $mpState[$c][$key] ?? 0;
                          $total += $val; ?>
                          <td>
                            <?php if ($key === 'absent' && $val > 0): ?>
                              <a href="#" class="absent-link text-decoration-none fw-semibold" data-status="ALL"
                                data-category="<?= htmlspecialchars($c) ?>">
                                <?= sprintf('%02d', $val) ?>
                              </a>
                            <?php else: ?>
                              <?= $val > 0 ? sprintf('%02d', $val) : '<span class="opacity-25">-</span>' ?>
                            <?php endif; ?>
                          </td>
                        <?php endforeach; ?>
                        <td class="fw-bold">
                          <?php if ($key === 'absent' && $total > 0): ?>
                            <a href="#" class="absent-link text-decoration-none text-dark" data-status="ALL"
                              data-category="ALL">
                              <?= sprintf('%02d', $total) ?>
                            </a>
                          <?php else: ?>
                            <?= $total > 0 ? sprintf('%02d', $total) : '<span class="opacity-25">-</span>' ?>
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
      </div>
    </div>

    <!-- ══════ ABSENT STATE ══════ -->
    <div class="row g-3 mb-4">
      <div class="col-12">
        <div class="chart-card animate-in delay-2"
          style="border: none; box-shadow: none; background: transparent; padding: 0;">
          <div class="chart-card-header px-1 pb-3 pt-0 d-flex justify-content-between align-items-center">
            <h6 style="font-size:1.1rem; letter-spacing:-0.02em;"><i class="ti ti-user-off me-2"
                style="color:#ef4444"></i>ABSENT STATE</h6>
          </div>
          <div class="premium-table-container">
            <div class="premium-table-inner">
              <div class="table-responsive">
                <table class="premium-table absent-table align-middle">
                  <thead>
                    <tr>
                      <th style="width: 140px;">Details</th>
                      <?php foreach (['OFFR', 'SWO', 'WO', 'SGT', 'CPL', 'LCPL', 'SNK(GD)', 'NC(E)', 'NC(U)'] as $c): ?>
                        <th><?= htmlspecialchars($c) ?></th>
                      <?php endforeach; ?>
                      <th>TOTAL</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $colTotals = array_fill_keys(['OFFR', 'SWO', 'WO', 'SGT', 'CPL', 'LCPL', 'SNK(GD)', 'NC(E)', 'NC(U)'], 0);
                    $grandTotal = 0; ?>
                    <?php foreach ($absentRows as $rowLabel): ?>
                      <tr>
                        <td><?= htmlspecialchars($rowLabel) ?></td>
                        <?php $rowTotal = 0; ?>
                        <?php foreach (['OFFR', 'SWO', 'WO', 'SGT', 'CPL', 'LCPL', 'SNK(GD)', 'NC(E)', 'NC(U)'] as $c): ?>
                          <?php $val = $absentStateData[$rowLabel][$c] ?? 0;
                          $rowTotal += $val;
                          $colTotals[$c] += $val; ?>
                          <td>
                            <?php if ($val > 0): ?>
                              <a href="#" class="absent-link text-decoration-none fw-semibold"
                                data-status="<?= htmlspecialchars($rowLabel) ?>" data-category="<?= htmlspecialchars($c) ?>">
                                <?= sprintf('%02d', $val) ?>
                              </a>
                            <?php else: ?>
                              <span class="opacity-25">-</span>
                            <?php endif; ?>
                          </td>
                        <?php endforeach; ?>
                        <?php $grandTotal += $rowTotal; ?>
                        <td class="fw-bold">
                          <?php if ($rowTotal > 0): ?>
                            <a href="#" class="absent-link text-decoration-none text-dark"
                              data-status="<?= htmlspecialchars($rowLabel) ?>" data-category="ALL">
                              <?= sprintf('%02d', $rowTotal) ?>
                            </a>
                          <?php else: ?>
                            <span class="opacity-25">-</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                      <td>Total</td>
                      <?php foreach (['OFFR', 'SWO', 'WO', 'SGT', 'CPL', 'LCPL', 'SNK(GD)', 'NC(E)', 'NC(U)'] as $c): ?>
                        <td class="fw-bold">
                          <?php if ($colTotals[$c] > 0): ?>
                            <a href="#" class="absent-link text-decoration-none text-dark" data-status="ALL"
                              data-category="<?= htmlspecialchars($c) ?>">
                              <?= sprintf('%02d', $colTotals[$c]) ?>
                            </a>
                          <?php else: ?>
                            <span class="opacity-25">-</span>
                          <?php endif; ?>
                        </td>
                      <?php endforeach; ?>
                      <td class="fw-bold">
                        <?php if ($grandTotal > 0): ?>
                          <a href="#" class="absent-link text-decoration-none text-dark" data-status="ALL"
                            data-category="ALL">
                            <?= sprintf('%02d', $grandTotal) ?>
                          </a>
                        <?php else: ?>
                          <span class="opacity-25">-</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Absent List Modal -->
    <div class="modal fade" id="absentListModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold" id="absentListTitle">Absent List</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="absentListContent" class="text-center">
              <div class="spinner-border text-primary my-4" role="status"><span
                  class="visually-hidden">Loading...</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>



    <!-- ══════ TABLES ══════ -->
    <div class="row g-3 mb-4">
      <div class="col-12">
        <div class="dash-table-card animate-in delay-6">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h6><i class="ti ti-calendar-off me-2" style="color:#f59e0b"></i>Currently On Leave</h6>
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Photo</th>
                  <th>Name</th>
                  <th>Rank</th>
                  <th>Type</th>
                  <th>Until</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($currentlyOnLeave as $lv): ?>
                  <tr style="cursor:pointer" onclick="location.href='profile.php?id=<?= $lv['id'] ?>'">
                    <td><img src="<?= htmlspecialchars(personnelPhotoUrl($lv['photo_path'] ?? null)) ?>"
                        class="person-avatar-sm"></td>
                    <td class="fw-semibold"><?= htmlspecialchars($lv['name']) ?></td>
                    <td><?= htmlspecialchars($lv['rank_name'] ?? '-') ?></td>
                    <td><span class="leave-type-badge"><?= htmlspecialchars($lv['leave_type'] ?? '-') ?></span></td>
                    <td class="text-muted"><?= date('d/m/y', strtotime($lv['to_date'])) ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($currentlyOnLeave)): ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted py-3">No one on leave</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<script>
  const palette = {
    indigo: '#6366f1', emerald: '#10b981', amber: '#f59e0b',
    cyan: '#06b6d4', rose: '#f43f5e', violet: '#8b5cf6',
    sky: '#0ea5e9', pink: '#ec4899', teal: '#14b8a6',
    orange: '#f97316', lime: '#84cc16', fuchsia: '#d946ef'
  };
  const colors = Object.values(palette);

  const chartDefaults = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        labels: { font: { family: "'Plus Jakarta Sans', sans-serif", size: 11, weight: '600' }, padding: 12, usePointStyle: true, pointStyleWidth: 8 }
      },
      tooltip: {
        backgroundColor: '#1e293b', titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: '700' },
        bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 11 }, cornerRadius: 8, padding: 10
      }
    }
  };




  document.addEventListener('click', function (e) {
    const link = e.target.closest('.absent-link');
    if (link) {
      e.preventDefault();
      const status = link.getAttribute('data-status');
      const category = link.getAttribute('data-category');

      document.getElementById('absentListTitle').textContent = category + ' - ' + status;
      document.getElementById('absentListContent').innerHTML = '<div class="spinner-border text-primary my-4" role="status"><span class="visually-hidden">Loading...</span></div>';

      const modal = new bootstrap.Modal(document.getElementById('absentListModal'));
      modal.show();

      fetch('ajax_absent_list.php?status=' + encodeURIComponent(status) + '&category=' + encodeURIComponent(category))
        .then(response => response.text())
        .then(html => {
          document.getElementById('absentListContent').innerHTML = html;
        })
        .catch(err => {
          document.getElementById('absentListContent').innerHTML = '<div class="alert alert-danger">Error loading list</div>';
        });
    }
  });
</script>