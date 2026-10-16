<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Personnel.php';
require_once __DIR__ . '/../classes/LookupManager.php';
require_once __DIR__ . '/../classes/AppSettings.php';
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
  'OFFR' => ['OFFR', 'Officer', 'Major', 'MAJ', 'Maj', 'Colonel', 'Col', 'COL', 'Captain', 'Capt', 'CAPT', 'Lieutenant', 'Lt', 'LT', '2Lt', '2LT', 'Second Lieutenant', 'Brigadier', 'Brig', 'Gen', 'General'],
  'SWO' => ['SWO', 'Senior Warrant Officer'],
  'WO' => ['WO', 'Warrant Officer'],
  'SGT' => ['SGT', 'Sgt', 'Sergeant'],
  'CPL' => ['CPL', 'Cpl', 'Corporal'],
  'LCPL' => ['LCPL', 'Lcpl', 'Lance Corporal'],
  'SNK' => ['SNK', 'SNK(GD)', 'Snk', 'Sainik', 'Soldier', 'SNK (GD)'],
  'NC(E)' => ['NC(E)', 'NCE', 'NC (E)'],
  'NC(U)' => ['NC(U)', 'NCU', 'NC (U)']
];
foreach ($rankMap as $cat => $ranks) {
  if (!isset($mpState[$cat]))
    continue;
  $present = 0;
  foreach ($ranks as $r) {
    $present += ($activeRankCounts[$r] ?? 0);
  }
  $mpState[$cat]['present'] = $present;
  $mpState[$cat]['absent'] = max(0, (int)$mpState[$cat]['posted'] - $present);
}
$totalPersonnel = (int) $db->query("SELECT COUNT(*) FROM personnel")->fetchColumn();
$activeCount = (int) $db->query("SELECT COUNT(*) FROM personnel p WHERE p.status = 'active' AND NOT EXISTS (SELECT 1 FROM personnel_leaves pl WHERE pl.personnel_id = p.id AND CURRENT_DATE >= pl.from_date AND CURRENT_DATE <= pl.to_date)")->fetchColumn();
$onLeaveCount = (int) $db->query("SELECT COUNT(*) FROM personnel p WHERE EXISTS (SELECT 1 FROM personnel_leaves pl WHERE pl.personnel_id = p.id AND CURRENT_DATE >= pl.from_date AND CURRENT_DATE <= pl.to_date)")->fetchColumn();
$familyMembers = (int) $db->query("SELECT COUNT(*) FROM personnel_family WHERE family_member = 'Yes'")->fetchColumn();

// ── MOQ Qualification Data ──
$moqSummary = Personnel::getPromotionQualificationCounts();
$moqQualifiedCount = $moqSummary['qualified_count'];
$moqNotQualifiedCount = $moqSummary['not_qualified_count'];


// ── Absent State Data ──
$customAbsentStatuses = AppSettings::getCustomAbsentStatuses();
$customLabels = array_values($customAbsentStatuses);

$baseAbsentRows = ['Pre Leave', 'C/Leave', 'W/Leave', 'R/Leave', 'M/Leave', 'CMH', 'TRG', 'CMD', 'ATT', 'GOC GD', 'Suspend', 'OSL', 'AWOL'];
$absentRows = array_merge($baseAbsentRows, $customLabels);
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

$baseStatusKeys = ['cmh', 'trg', 'cmd', 'att', 'goc_gd', 'suspend', 'osl', 'awol', 'others'];
$allAbsentStatusKeys = array_unique(array_merge($baseStatusKeys, array_keys($customAbsentStatuses)));
$inPlaceholders = "'" . implode("','", array_map('addslashes', $allAbsentStatusKeys)) . "'";

$statusCounts = $db->query("SELECT r.name as rank_name, p.status, COUNT(DISTINCT p.id) as cnt FROM personnel p JOIN ranks r ON p.rank_id = r.id WHERE p.status IN ($inPlaceholders) GROUP BY r.name, p.status")->fetchAll();

$mapStatus = [
  'cmh' => 'CMH',
  'trg' => 'TRG',
  'cmd' => 'CMD',
  'att' => 'ATT',
  'goc_gd' => 'GOC GD',
  'suspend' => 'Suspend',
  'osl' => 'OSL',
  'awol' => 'AWOL',
];
foreach ($customAbsentStatuses as $cKey => $cLabel) {
  $mapStatus[$cKey] = $cLabel;
}
if (!isset($mapStatus['others'])) {
  $mapStatus['others'] = reset($customAbsentStatuses) ?: 'Others';
}

foreach ($statusCounts as $sc) {
  $catFound = null;
  foreach ($rankMap as $cat => $ranks) {
    if (in_array($sc['rank_name'], $ranks)) {
      $catFound = $cat;
      break;
    }
  }
  if ($catFound && isset($mapStatus[$sc['status']]) && isset($absentStateData[$mapStatus[$sc['status']]])) {
    $absentStateData[$mapStatus[$sc['status']]][$catFound] += (int) $sc['cnt'];
  }
}

// ── IPFT State Data ──
$ipftPersonnel = $db->query("
    SELECT 
        p.id, p.personal_number, p.name, p.photo_path, p.status,
        r.name AS rank_name,
        COALESCE(ps.ipft_1st, '') AS ipft_1st,
        COALESCE(ps.ipft_2nd, '') AS ipft_2nd,
        ps.ret,
        ps.speed_march
    FROM personnel p
    JOIN ranks r ON p.rank_id = r.id
    LEFT JOIN personnel_service ps ON p.id = ps.personnel_id
    WHERE p.status = 'active'
")->fetchAll(PDO::FETCH_ASSOC);

$ipftCategories = ['OFFR', 'SWO', 'WO', 'SGT', 'CPL', 'LCPL', 'SNK'];
$categoryLabels = [
  'OFFR' => 'Officers',
  'SWO' => 'Senior Warrant Officers',
  'WO' => 'Warrant Officers',
  'SGT' => 'Sergeants',
  'CPL' => 'Corporals',
  'LCPL' => 'Lance Corporals',
  'SNK' => 'Soldiers'
];

$ipftCategoryData = [];
foreach ($ipftCategories as $cat) {
    $ipftCategoryData[$cat] = [
        'total' => 0,
        '1st' => ['pass' => 0, 'fail' => 0, 'na' => 0, 'pending' => 0],
        '2nd' => ['pass' => 0, 'fail' => 0, 'na' => 0, 'pending' => 0],
    ];
}

$ipftSummary = [
    '1st' => ['pass' => 0, 'fail' => 0, 'na' => 0, 'pending' => 0, 'total' => 0],
    '2nd' => ['pass' => 0, 'fail' => 0, 'na' => 0, 'pending' => 0, 'total' => 0],
    'both_pass' => 0,
    'total_active' => 0
];

foreach ($ipftPersonnel as $p) {
    $pCat = null;
    foreach ($rankMap as $cat => $ranks) {
        if (in_array($p['rank_name'], $ranks, true)) {
            $pCat = $cat;
            break;
        }
    }
    // NC(E) and NC(U) are non-combatant support trades without IPFT requirement
    if (!$pCat || !in_array($pCat, $ipftCategories, true)) {
        continue;
    }

    $ipftCategoryData[$pCat]['total']++;
    $ipftSummary['total_active']++;

    // 1st Annual
    $res1 = strtoupper(trim((string)$p['ipft_1st']));
    $type1 = match($res1) {
        'PASS' => 'pass',
        'FAIL' => 'fail',
        'NOT ATTENDING', 'NOT APPLICABLE' => 'na',
        default => 'pending'
    };
    $ipftSummary['1st'][$type1]++;
    $ipftSummary['1st']['total']++;
    $ipftCategoryData[$pCat]['1st'][$type1]++;

    // 2nd Annual
    $res2 = strtoupper(trim((string)$p['ipft_2nd']));
    $type2 = match($res2) {
        'PASS' => 'pass',
        'FAIL' => 'fail',
        'NOT ATTENDING', 'NOT APPLICABLE' => 'na',
        default => 'pending'
    };
    $ipftSummary['2nd'][$type2]++;
    $ipftSummary['2nd']['total']++;
    $ipftCategoryData[$pCat]['2nd'][$type2]++;

    if ($res1 === 'PASS' && $res2 === 'PASS') {
        $ipftSummary['both_pass']++;
    }
    if ($res1 === 'PASS' || $res2 === 'PASS') {
        $ipftSummary['any_pass'] = ($ipftSummary['any_pass'] ?? 0) + 1;
    }
}

$passRate1 = $ipftSummary['1st']['total'] > 0 ? round(($ipftSummary['1st']['pass'] / $ipftSummary['1st']['total']) * 100) : 0;
$passRate2 = $ipftSummary['2nd']['total'] > 0 ? round(($ipftSummary['2nd']['pass'] / $ipftSummary['2nd']['total']) * 100) : 0;

// ── Currently On Leave ──
$currentlyOnLeave = $db->query("SELECT p.id, p.name, p.personal_number, p.photo_path, r.name AS rank_name, pl.leave_type, pl.from_date, pl.to_date FROM personnel p JOIN personnel_leaves pl ON p.id = pl.personnel_id LEFT JOIN ranks r ON p.rank_id = r.id WHERE CURRENT_DATE >= pl.from_date AND CURRENT_DATE <= pl.to_date ORDER BY pl.to_date ASC, p.id ASC")->fetchAll();
$allOnLeave = $currentlyOnLeave;
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

  .sc-ipft {
    background: linear-gradient(135deg, #0284c7, #38bdf8);
    border-color: transparent;
    cursor: pointer;
  }

  /* ── IPFT State Section (Compact) ── */
  .ipft-summary-tile {
    background: #ffffff;
    border-radius: 12px;
    padding: 9px 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02);
    height: 100%;
    transition: all 0.2s ease;
  }
  .ipft-summary-tile:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 10px -2px rgba(15, 23, 42, 0.05);
  }

  .ipft-card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(255px, 1fr));
    gap: 10px;
  }
  .ipft-cat-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    overflow: hidden;
  }
  .ipft-cat-card:hover {
    transform: translateY(-2px);
    border-color: #cbd5e1;
    box-shadow: 0 6px 14px -3px rgba(15, 23, 42, 0.07);
  }
  .ipft-cat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 6px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 7px;
  }
  .ipft-cat-title {
    font-size: 0.88rem;
    font-weight: 800;
    color: #1e293b;
    letter-spacing: -0.01em;
  }
  .ipft-cat-sub {
    font-size: 0.68rem;
    color: #64748b;
    font-weight: 500;
  }
  .ipft-cat-total {
    background: #f8fafc;
    color: #334155;
    border: 1px solid #e2e8f0;
    font-weight: 700;
    font-size: 0.68rem;
    padding: 2px 6px;
    border-radius: 5px;
  }

  .ipft-cycle-row {
    margin-bottom: 5px;
    transition: opacity 0.2s ease, max-height 0.25s ease;
  }
  .ipft-cycle-row.hidden-cycle {
    display: none !important;
  }
  .ipft-cycle-tag {
    font-size: 0.66rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #475569;
    background: #f1f5f9;
    padding: 2px 5px;
    border-radius: 4px;
    min-width: 26px;
    text-align: center;
    line-height: 1.2;
  }
  .ipft-pills-wrap {
    display: flex;
    gap: 3px;
    flex-wrap: wrap;
  }
  .ipft-pill {
    flex: 1 1 calc(25% - 3px);
    min-width: 44px;
    padding: 2px 4px;
    border-radius: 6px;
    font-size: 0.68rem;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    text-align: center;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 2px;
    text-decoration: none;
    line-height: 1.2;
  }
  .ipft-pill:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.06);
  }
  .ipft-pill-pass {
    background: #ecfdf5;
    color: #059669;
    border-color: #a7f3d0;
  }
  .ipft-pill-pass:hover {
    background: #d1fae5;
    color: #047857;
  }
  .ipft-pill-fail {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
  }
  .ipft-pill-fail:hover {
    background: #fee2e2;
    color: #b91c1c;
  }
  .ipft-pill-na {
    background: #fffbeb;
    color: #d97706;
    border-color: #fde68a;
  }
  .ipft-pill-na:hover {
    background: #fef3c7;
    color: #b45309;
  }
  .ipft-pill-pend {
    background: #f8fafc;
    color: #64748b;
    border-color: #e2e8f0;
  }
  .ipft-pill-pend:hover {
    background: #f1f5f9;
    color: #475569;
  }
  .pill-dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    display: inline-block;
    background: currentColor;
  }

  .ipft-filter-btn {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
    transition: all 0.2s ease;
  }
  .ipft-filter-btn.active {
    background: #0284c7 !important;
    border-color: #0284c7 !important;
    color: #ffffff !important;
  }

  .stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.15), 0 4px 12px -2px rgba(0, 0, 0, 0.1);
  }

  .stat-card:hover .stat-icon {
    opacity: 0.35;
    transform: translateY(-50%) scale(1.08);
  }

  .stat-card .stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.1;
    margin-bottom: 6px;
    letter-spacing: -0.02em;
    position: relative;
    z-index: 2;
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
    position: relative;
    z-index: 2;
  }

  .stat-card .stat-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 3rem;
    opacity: 0.2;
    color: #ffffff;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: none;
    z-index: 1;
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

  @media print {
    #onLeaveSection,
    #onLeaveSection .premium-table-container,
    #onLeaveSection .premium-table-inner {
      width: 100% !important;
      max-width: 100% !important;
      overflow: visible !important;
      margin: 0 !important;
      padding: 0 !important;
    }

    #onLeaveSection table.leave-print-table {
      width: 100% !important;
      max-width: 100% !important;
      table-layout: fixed !important;
      font-size: 9.5pt !important;
      border-collapse: collapse !important;
      margin: 0 auto !important;
    }

    #onLeaveSection table.leave-print-table th,
    #onLeaveSection table.leave-print-table td {
      white-space: normal !important;
      word-break: break-word !important;
      padding: 4px 3px !important;
      font-size: 9pt !important;
      line-height: 1.25 !important;
      border: 1px solid #000 !important;
    }

    #onLeaveSection table.leave-print-table th {
      font-size: 8.5pt !important;
      font-weight: 700 !important;
      text-transform: uppercase !important;
      background: #f1f5f9 !important;
      text-align: center !important;
    }
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
      <div class="d-flex gap-2">
        <?php if (Auth::hasPermission('print_records')): ?>
        <button type="button" class="btn-print no-print" onclick="printSection(['manpowerSection', 'absentSection'])"><i class="ti ti-printer"></i> Print Paradestate</button>
        <?php endif; ?>
      </div>
    </div>

    <!-- ══════ STAT CARDS ══════ -->
    <div class="row g-3 mb-4 row-cols-2 row-cols-md-3 row-cols-xl-6">
      <div class="col">
        <div class="stat-card sc-total animate-in delay-1">
          <div class="stat-value"><?= sprintf('%02d', $totalPersonnel) ?></div>
          <div class="stat-label" title="Total Personnel">TOTAL PERSONNEL</div>
          <i class="ti ti-users stat-icon"></i>
        </div>
      </div>
      <div class="col">
        <div class="stat-card sc-active animate-in delay-2">
          <div class="stat-value"><?= sprintf('%02d', $activeCount) ?></div>
          <div class="stat-label" title="Active">ACTIVE</div>
          <i class="ti ti-user-check stat-icon"></i>
        </div>
      </div>
      <div class="col">
        <div class="stat-card sc-leave animate-in delay-3">
          <div class="stat-value"><?= sprintf('%02d', $onLeaveCount) ?></div>
          <div class="stat-label" title="On leave">ON LEAVE</div>
          <i class="ti ti-calendar-off stat-icon"></i>
        </div>
      </div>
      <div class="col">
        <div class="stat-card sc-family animate-in delay-4">
          <div class="stat-value"><?= sprintf('%02d', $familyMembers) ?></div>
          <div class="stat-label" title="FAMILY MEMBER">FAMILY MEMBER</div>
          <i class="ti ti-friends stat-icon"></i>
        </div>
      </div>
      <div class="col">
        <div class="stat-card sc-moq-no animate-in delay-5" style="cursor: pointer;" onclick="location.href='personnel.php?moq_status=not_qualified'" title="View MOQ Not Qualified Personnel">
          <div class="stat-value"><?= sprintf('%02d', $moqNotQualifiedCount) ?></div>
          <div class="stat-label" title="MOQ NOT QUAL">MOQ NOT QUAL</div>
          <i class="ti ti-alert-triangle stat-icon"></i>
        </div>
      </div>
      <div class="col">
        <div class="stat-card sc-ipft animate-in delay-6" style="cursor: pointer;" onclick="document.getElementById('ipftSection').scrollIntoView({behavior:'smooth'})" title="Click to view IPFT State">
          <div class="stat-value"><?= sprintf('%02d', $ipftSummary['any_pass'] ?? $ipftSummary['1st']['pass']) ?></div>
          <div class="stat-label" title="IPFT PASS">IPFT PASS</div>
          <i class="ti ti-activity-heartbeat stat-icon"></i>
        </div>
      </div>
    </div>

    <!-- ══════ MANPOWER STATE ══════ -->
    <div class="row g-3 mb-4">
      <div class="col-12" id="manpowerSection">
        <div class="chart-card animate-in delay-2"
          style="border: none; box-shadow: none; background: transparent; padding: 0;">
          <div class="chart-card-header px-1 pb-3 pt-0 d-flex justify-content-between align-items-center">
            <h6 style="font-size:1.1rem; letter-spacing:-0.02em;"><i class="ti ti-users-group me-2"
                style="color:#6366f1"></i>MANPOWER STATE</h6>
            <?php if (Auth::hasPermission('print_records')): ?>
            <button type="button" class="btn-print no-print" onclick="printSection('manpowerSection')"><i class="ti ti-printer"></i> Print</button>
            <?php endif; ?>
          </div>
          <div class="premium-table-container">
            <div class="premium-table-inner">
              <div class="table-responsive">
                <table class="premium-table align-middle">
                  <thead>
                    <tr>
                      <th style="width: 140px;">Details</th>
                      <?php foreach (['OFFR', 'SWO', 'WO', 'SGT', 'CPL', 'LCPL', 'SNK', 'NC(E)', 'NC(U)'] as $c): ?>
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
                        <?php foreach (['OFFR', 'SWO', 'WO', 'SGT', 'CPL', 'LCPL', 'SNK', 'NC(E)', 'NC(U)'] as $c): ?>
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
      <div class="col-12" id="absentSection">
        <div class="chart-card animate-in delay-2"
          style="border: none; box-shadow: none; background: transparent; padding: 0;">
          <div class="chart-card-header px-1 pb-3 pt-0 d-flex justify-content-between align-items-center">
            <h6 style="font-size:1.1rem; letter-spacing:-0.02em;"><i class="ti ti-user-off me-2"
                style="color:#ef4444"></i>ABSENT STATE</h6>
            <?php if (Auth::hasPermission('print_records')): ?>
            <button type="button" class="btn-print no-print" onclick="printSection('absentSection')"><i class="ti ti-printer"></i> Print</button>
            <?php endif; ?>
          </div>
          <div class="premium-table-container">
            <div class="premium-table-inner">
              <div class="table-responsive">
                <table class="premium-table absent-table align-middle">
                  <thead>
                    <tr>
                      <th style="width: 140px;">Details</th>
                      <?php foreach (['OFFR', 'SWO', 'WO', 'SGT', 'CPL', 'LCPL', 'SNK', 'NC(E)', 'NC(U)'] as $c): ?>
                        <th><?= htmlspecialchars($c) ?></th>
                      <?php endforeach; ?>
                      <th>TOTAL</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $colTotals = array_fill_keys(['OFFR', 'SWO', 'WO', 'SGT', 'CPL', 'LCPL', 'SNK', 'NC(E)', 'NC(U)'], 0);
                    $grandTotal = 0; ?>
                    <?php foreach ($absentRows as $rowLabel): ?>
                      <tr>
                        <td><?= htmlspecialchars($rowLabel) ?></td>
                        <?php $rowTotal = 0; ?>
                        <?php foreach (['OFFR', 'SWO', 'WO', 'SGT', 'CPL', 'LCPL', 'SNK', 'NC(E)', 'NC(U)'] as $c): ?>
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
                      <?php foreach (['OFFR', 'SWO', 'WO', 'SGT', 'CPL', 'LCPL', 'SNK', 'NC(E)', 'NC(U)'] as $c): ?>
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

    <!-- ══════ IPFT STATE ══════ -->
    <div class="row g-3 mb-4">
      <div class="col-12" id="ipftSection">
        <div class="chart-card animate-in delay-3" style="border: none; box-shadow: none; background: transparent; padding: 0;">
          <div class="chart-card-header px-1 pb-2 pt-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <h6 style="font-size:1.05rem; letter-spacing:-0.02em; margin-bottom: 0;">
                <i class="ti ti-activity-heartbeat me-2" style="color:#0284c7"></i>IPFT STATE
              </h6>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small px-2 py-0 no-print" style="font-size: 0.7rem;">Card View</span>
            </div>
            <div class="d-flex align-items-center gap-2 no-print">
              <div class="btn-group btn-group-sm" role="group" aria-label="IPFT Cycle Filter">
                <button type="button" class="btn btn-outline-secondary ipft-filter-btn active" data-cycle="all">All</button>
                <button type="button" class="btn btn-outline-secondary ipft-filter-btn" data-cycle="1st">1st Annual</button>
                <button type="button" class="btn btn-outline-secondary ipft-filter-btn" data-cycle="2nd">2nd Annual</button>
              </div>
              <?php if (Auth::hasPermission('print_records')): ?>
              <button type="button" class="btn-print no-print py-1 px-2" style="font-size: 0.78rem;" onclick="printSection('ipftSection', 'IPFT State')"><i class="ti ti-printer"></i> Print</button>
              <?php endif; ?>
            </div>
          </div>

          <!-- Compact Summary Tiles -->
          <div class="ipft-compact-summary mb-2 no-print">
            <div class="row g-2">
              <div class="col-12 col-md-4">
                <div class="ipft-summary-tile">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-dark" style="font-size: 0.78rem;"><i class="ti ti-medal text-success me-1"></i>1st Annual</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-2" style="font-size: 0.7rem;"><?= $passRate1 ?>% Pass</span>
                  </div>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex gap-1">
                      <button type="button" class="ipft-pill ipft-pill-pass py-0 px-2" data-annual="1st" data-status="PASS" data-category="ALL" title="1st Pass">P: <?= $ipftSummary['1st']['pass'] ?></button>
                      <button type="button" class="ipft-pill ipft-pill-fail py-0 px-2" data-annual="1st" data-status="FAIL" data-category="ALL" title="1st Fail">F: <?= $ipftSummary['1st']['fail'] ?></button>
                      <button type="button" class="ipft-pill ipft-pill-na py-0 px-2" data-annual="1st" data-status="NOT ATTENDING" data-category="ALL" title="1st N/A">NA: <?= $ipftSummary['1st']['na'] ?></button>
                      <button type="button" class="ipft-pill ipft-pill-pend py-0 px-2" data-annual="1st" data-status="PENDING" data-category="ALL" title="1st Pending">Pend: <?= $ipftSummary['1st']['pending'] ?></button>
                    </div>
                    <span class="text-muted small" style="font-size: 0.72rem;"><?= $ipftSummary['1st']['total'] ?> Total</span>
                  </div>
                </div>
              </div>

              <div class="col-12 col-md-4">
                <div class="ipft-summary-tile">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-dark" style="font-size: 0.78rem;"><i class="ti ti-medal text-primary me-1"></i>2nd Annual</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-0 px-2" style="font-size: 0.7rem;"><?= $passRate2 ?>% Pass</span>
                  </div>
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex gap-1">
                      <button type="button" class="ipft-pill ipft-pill-pass py-0 px-2" data-annual="2nd" data-status="PASS" data-category="ALL" title="2nd Pass">P: <?= $ipftSummary['2nd']['pass'] ?></button>
                      <button type="button" class="ipft-pill ipft-pill-fail py-0 px-2" data-annual="2nd" data-status="FAIL" data-category="ALL" title="2nd Fail">F: <?= $ipftSummary['2nd']['fail'] ?></button>
                      <button type="button" class="ipft-pill ipft-pill-na py-0 px-2" data-annual="2nd" data-status="NOT ATTENDING" data-category="ALL" title="2nd N/A">NA: <?= $ipftSummary['2nd']['na'] ?></button>
                      <button type="button" class="ipft-pill ipft-pill-pend py-0 px-2" data-annual="2nd" data-status="PENDING" data-category="ALL" title="2nd Pending">Pend: <?= $ipftSummary['2nd']['pending'] ?></button>
                    </div>
                    <span class="text-muted small" style="font-size: 0.72rem;"><?= $ipftSummary['2nd']['total'] ?> Total</span>
                  </div>
                </div>
              </div>

              <div class="col-12 col-md-4">
                <div class="ipft-summary-tile d-flex align-items-center justify-content-between">
                  <div>
                    <div class="fw-bold text-dark" style="font-size: 0.78rem;"><i class="ti ti-shield-check text-dark me-1"></i>Dual-Cycle Pass</div>
                    <div class="text-muted" style="font-size: 0.7rem;">Cleared Both Cycles</div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="fs-6 fw-bold text-dark"><?= sprintf('%02d', $ipftSummary['both_pass']) ?></span>
                    <button type="button" class="btn btn-sm btn-outline-dark py-0 px-2 ipft-chip" style="font-size: 0.7rem;" data-annual="ALL" data-status="PASS" data-category="ALL">View</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Compact Category Card View Grid -->
          <div class="ipft-card-grid no-print">
            <?php foreach ($ipftCategoryData as $cat => $cdata): 
              $catTotal = $cdata['total'];
              $catPass1 = $cdata['1st']['pass'];
              $pRate1 = $catTotal > 0 ? round(($catPass1 / $catTotal) * 100) : 0;
              $catPass2 = $cdata['2nd']['pass'];
              $pRate2 = $catTotal > 0 ? round(($catPass2 / $catTotal) * 100) : 0;
            ?>
            <div class="ipft-cat-card">
              <div class="ipft-cat-header">
                <div class="d-flex align-items-center gap-1">
                  <span class="ipft-cat-title"><?= htmlspecialchars($cat) ?></span>
                  <span class="ipft-cat-sub"><?= htmlspecialchars($categoryLabels[$cat] ?? '') ?></span>
                </div>
                <span class="ipft-cat-total"><?= sprintf('%02d', $catTotal) ?></span>
              </div>

              <!-- 1st Annual Row -->
              <div class="ipft-cycle-row ipft-row-1st">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <span class="ipft-cycle-tag">1st</span>
                  <div class="ipft-pills-wrap flex-grow-1 ms-1">
                    <button type="button" class="ipft-pill ipft-pill-pass" data-annual="1st" data-status="PASS" data-category="<?= htmlspecialchars($cat) ?>" title="1st Pass">P: <?= $cdata['1st']['pass'] ?></button>
                    <button type="button" class="ipft-pill ipft-pill-fail" data-annual="1st" data-status="FAIL" data-category="<?= htmlspecialchars($cat) ?>" title="1st Fail">F: <?= $cdata['1st']['fail'] ?></button>
                    <button type="button" class="ipft-pill ipft-pill-na" data-annual="1st" data-status="NOT ATTENDING" data-category="<?= htmlspecialchars($cat) ?>" title="1st N/A">NA: <?= $cdata['1st']['na'] ?></button>
                    <button type="button" class="ipft-pill ipft-pill-pend" data-annual="1st" data-status="PENDING" data-category="<?= htmlspecialchars($cat) ?>" title="1st Pending">Pend: <?= $cdata['1st']['pending'] ?></button>
                  </div>
                </div>
              </div>

              <!-- 2nd Annual Row -->
              <div class="ipft-cycle-row ipft-row-2nd">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <span class="ipft-cycle-tag">2nd</span>
                  <div class="ipft-pills-wrap flex-grow-1 ms-1">
                    <button type="button" class="ipft-pill ipft-pill-pass" data-annual="2nd" data-status="PASS" data-category="<?= htmlspecialchars($cat) ?>" title="2nd Pass">P: <?= $cdata['2nd']['pass'] ?></button>
                    <button type="button" class="ipft-pill ipft-pill-fail" data-annual="2nd" data-status="FAIL" data-category="<?= htmlspecialchars($cat) ?>" title="2nd Fail">F: <?= $cdata['2nd']['fail'] ?></button>
                    <button type="button" class="ipft-pill ipft-pill-na" data-annual="2nd" data-status="NOT ATTENDING" data-category="<?= htmlspecialchars($cat) ?>" title="2nd N/A">NA: <?= $cdata['2nd']['na'] ?></button>
                    <button type="button" class="ipft-pill ipft-pill-pend" data-annual="2nd" data-status="PENDING" data-category="<?= htmlspecialchars($cat) ?>" title="2nd Pending">Pend: <?= $cdata['2nd']['pending'] ?></button>
                  </div>
                </div>
              </div>

              <!-- Slim Progress Bar -->
              <div class="progress mt-1" style="height: 3px; border-radius: 999px; background: #f1f5f9;">
                <div class="progress-bar bg-success" style="width: <?= $pRate1 ?>%"></div>
                <div class="progress-bar bg-danger" style="width: <?= $catTotal > 0 ? round(($cdata['1st']['fail'] / $catTotal) * 100) : 0 ?>%"></div>
                <div class="progress-bar bg-warning" style="width: <?= $catTotal > 0 ? round(($cdata['1st']['na'] / $catTotal) * 100) : 0 ?>%"></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Print Only Table -->
          <div class="print-only d-none">
            <div class="premium-table-container">
              <div class="premium-table-inner">
                <table class="premium-table align-middle">
                  <thead>
                    <tr>
                      <th rowspan="2" style="width: 130px; vertical-align: middle;">Category</th>
                      <th rowspan="2" style="width: 70px; vertical-align: middle; text-align: center;">Total</th>
                      <th colspan="4" class="text-center" style="border-bottom: 1px solid #000;">1st Annual IPFT</th>
                      <th colspan="4" class="text-center" style="border-bottom: 1px solid #000;">2nd Annual IPFT</th>
                    </tr>
                    <tr>
                      <th style="text-align: center;">Pass</th>
                      <th style="text-align: center;">Fail</th>
                      <th style="text-align: center;">N/A</th>
                      <th style="text-align: center;">Pend</th>
                      <th style="text-align: center;">Pass</th>
                      <th style="text-align: center;">Fail</th>
                      <th style="text-align: center;">N/A</th>
                      <th style="text-align: center;">Pend</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($ipftCategoryData as $cat => $cd): ?>
                    <tr>
                      <td class="fw-bold"><?= htmlspecialchars($cat) ?></td>
                      <td class="text-center fw-bold"><?= sprintf('%02d', $cd['total']) ?></td>
                      <td class="text-center"><?= sprintf('%02d', $cd['1st']['pass']) ?></td>
                      <td class="text-center"><?= sprintf('%02d', $cd['1st']['fail']) ?></td>
                      <td class="text-center"><?= sprintf('%02d', $cd['1st']['na']) ?></td>
                      <td class="text-center"><?= sprintf('%02d', $cd['1st']['pending']) ?></td>
                      <td class="text-center"><?= sprintf('%02d', $cd['2nd']['pass']) ?></td>
                      <td class="text-center"><?= sprintf('%02d', $cd['2nd']['fail']) ?></td>
                      <td class="text-center"><?= sprintf('%02d', $cd['2nd']['na']) ?></td>
                      <td class="text-center"><?= sprintf('%02d', $cd['2nd']['pending']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                      <td class="fw-bold">Total</td>
                      <td class="text-center fw-bold"><?= sprintf('%02d', $ipftSummary['total_active']) ?></td>
                      <td class="text-center fw-bold"><?= sprintf('%02d', $ipftSummary['1st']['pass']) ?></td>
                      <td class="text-center fw-bold"><?= sprintf('%02d', $ipftSummary['1st']['fail']) ?></td>
                      <td class="text-center fw-bold"><?= sprintf('%02d', $ipftSummary['1st']['na']) ?></td>
                      <td class="text-center fw-bold"><?= sprintf('%02d', $ipftSummary['1st']['pending']) ?></td>
                      <td class="text-center fw-bold"><?= sprintf('%02d', $ipftSummary['2nd']['pass']) ?></td>
                      <td class="text-center fw-bold"><?= sprintf('%02d', $ipftSummary['2nd']['fail']) ?></td>
                      <td class="text-center fw-bold"><?= sprintf('%02d', $ipftSummary['2nd']['na']) ?></td>
                      <td class="text-center fw-bold"><?= sprintf('%02d', $ipftSummary['2nd']['pending']) ?></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- IPFT List Modal -->
    <div class="modal fade" id="ipftListModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold" id="ipftListTitle">IPFT Personnel List</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="ipftListContent" class="text-center">
              <div class="spinner-border text-primary my-4" role="status"><span class="visually-hidden">Loading...</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>



    <!-- ══════ TABLES ══════ -->
    <div class="row g-3 mb-4">
      <div class="col-12" id="onLeaveSection">
        <div class="chart-card animate-in delay-6" style="border: none; box-shadow: none; background: transparent; padding: 0;">
          <div class="chart-card-header d-flex align-items-center justify-content-between px-1 pb-3 pt-0">
            <h6 style="font-size:1.1rem; letter-spacing:-0.02em;">
              <i class="ti ti-calendar-off me-2" style="color:#f59e0b"></i>Currently On Leave 
              <span class="badge bg-warning-subtle text-warning border ms-1" style="font-size:0.75rem;"><?= count($currentlyOnLeave) ?></span>
            </h6>
            <div class="d-flex align-items-center gap-2">
              <?php if (Auth::hasPermission('leave_seniority')): ?>
              <a href="leave_seniority.php" class="btn-print no-print text-decoration-none"><i class="ti ti-calendar-stats"></i> Leave Seniority</a>
              <?php endif; ?>
              <?php if (Auth::hasPermission('print_records')): ?>
              <button type="button" class="btn-print no-print" onclick="printSection('onLeaveSection', 'Currently On Leave')"><i class="ti ti-printer"></i> Print</button>
              <?php endif; ?>
            </div>
          </div>
          <div class="table-responsive no-print" style="background: #fff; border-radius: 12px; border: 1px solid #eee; overflow: hidden; max-height: 520px; overflow-y: auto;">
            <table class="table table-hover mb-0">
              <thead style="position: sticky; top: 0; background: #ffffff; z-index: 2; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                <tr>
                  <th style="width: 45px; text-align: center;">SL</th>
                  <th>Photo</th>
                  <th>Name</th>
                  <th>Rank</th>
                  <th>Type</th>
                  <th>Until</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($currentlyOnLeave as $idx => $lv): ?>
                  <tr style="cursor:pointer" onclick="location.href='profile.php?id=<?= $lv['id'] ?>'">
                    <td class="text-center text-muted fw-medium" style="font-size: 0.82rem;"><?= $idx + 1 ?></td>
                    <td><img src="<?= htmlspecialchars(personnelPhotoUrl($lv['photo_path'] ?? null)) ?>"
                        class="person-avatar-sm"></td>
                    <td class="fw-semibold">
                      <?= htmlspecialchars($lv['name']) ?>
                      <div class="text-muted fw-normal" style="font-size:0.75rem;">No: <?= htmlspecialchars($lv['personal_number']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($lv['rank_name'] ?? '-') ?></td>
                    <td><span class="leave-type-badge"><?= htmlspecialchars($lv['leave_type'] === 'Pre Leave' ? 'P/L' : trim(str_ireplace('leave', '', $lv['leave_type'] ?? '-'))) ?></span></td>
                    <td class="text-muted"><?= date('d/m/y', strtotime($lv['to_date'])) ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($currentlyOnLeave)): ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">No personnel currently on leave.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          
          <!-- Print Only Table -->
          <div class="print-only d-none">
            <div class="premium-table-container">
              <div class="premium-table-inner">
                <table class="table premium-table leave-print-table align-middle">
                  <thead>
                    <tr>
                      <th style="width: 5%; text-align: center;">SL</th>
                      <th style="width: 12%; text-align: center;">P No</th>
                      <th style="width: 10%; text-align: center;">Rank</th>
                      <th style="width: 27%; text-align: left;">Name</th>
                      <th style="width: 13%; text-align: center;">From</th>
                      <th style="width: 13%; text-align: center;">To</th>
                      <th style="width: 12%; text-align: center;">Leave Type</th>
                      <th style="width: 8%; text-align: center;">Remark</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($allOnLeave as $idx => $l): ?>
                    <tr>
                      <td style="text-align: center; font-weight: 500;"><?= $idx + 1 ?></td>
                      <td style="text-align: center;"><?= htmlspecialchars($l['personal_number']) ?></td>
                      <td style="text-align: center;"><?= htmlspecialchars($l['rank_name'] ?? '-') ?></td>
                      <td style="text-align: left; font-weight: 600;"><?= htmlspecialchars($l['name']) ?></td>
                      <td style="text-align: center;"><?= htmlspecialchars(date('d M Y', strtotime($l['from_date']))) ?></td>
                      <td style="text-align: center;"><?= htmlspecialchars(date('d M Y', strtotime($l['to_date']))) ?></td>
                      <td style="text-align: center;"><?= htmlspecialchars($l['leave_type'] === 'Pre Leave' ? 'P/L' : trim(str_ireplace('leave', '', $l['leave_type'] ?? '-'))) ?></td>
                      <td style="text-align: center;"></td>
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

    // IPFT pill / chip modal handler
    const ipftBtn = e.target.closest('.ipft-pill, .ipft-chip');
    if (ipftBtn) {
      e.preventDefault();
      const annual = ipftBtn.getAttribute('data-annual') || 'ALL';
      const status = ipftBtn.getAttribute('data-status') || 'ALL';
      const category = ipftBtn.getAttribute('data-category') || 'ALL';

      const annualText = annual === '1st' ? '1st Annual' : (annual === '2nd' ? '2nd Annual' : 'Annual');
      const catText = category === 'ALL' ? 'All Personnel' : category;
      document.getElementById('ipftListTitle').textContent = `IPFT (${annualText}) - ${catText} [${status}]`;
      document.getElementById('ipftListContent').innerHTML = '<div class="spinner-border text-primary my-4" role="status"><span class="visually-hidden">Loading...</span></div>';

      const modal = new bootstrap.Modal(document.getElementById('ipftListModal'));
      modal.show();

      fetch('ajax_ipft_list.php?annual=' + encodeURIComponent(annual) + '&status=' + encodeURIComponent(status) + '&category=' + encodeURIComponent(category))
        .then(response => response.text())
        .then(html => {
          document.getElementById('ipftListContent').innerHTML = html;
        })
        .catch(err => {
          document.getElementById('ipftListContent').innerHTML = '<div class="alert alert-danger">Error loading list</div>';
        });
    }

    // IPFT cycle filter handler
    const filterBtn = e.target.closest('.ipft-filter-btn');
    if (filterBtn) {
      e.preventDefault();
      document.querySelectorAll('.ipft-filter-btn').forEach(b => b.classList.remove('active'));
      filterBtn.classList.add('active');
      const cycle = filterBtn.getAttribute('data-cycle');
      
      const row1st = document.querySelectorAll('.ipft-row-1st');
      const row2nd = document.querySelectorAll('.ipft-row-2nd');
      
      if (cycle === '1st') {
        row1st.forEach(r => r.classList.remove('hidden-cycle'));
        row2nd.forEach(r => r.classList.add('hidden-cycle'));
      } else if (cycle === '2nd') {
        row1st.forEach(r => r.classList.add('hidden-cycle'));
        row2nd.forEach(r => r.classList.remove('hidden-cycle'));
      } else {
        row1st.forEach(r => r.classList.remove('hidden-cycle'));
        row2nd.forEach(r => r.classList.remove('hidden-cycle'));
      }
    }
  });

  function printSection(sectionIds, defaultHeading = 'Paradestate') {
    if (!Array.isArray(sectionIds)) {
      sectionIds = [sectionIds];
    }
    
    Swal.fire({
        title: 'Print Options',
        html:
            '<input id="printHeading" class="swal2-input mb-2" placeholder="Heading" value="' + defaultHeading + '" style="max-width: 90%; margin: 10px auto;">' +
            '<input id="printSubHeading" class="swal2-input" placeholder="Sub-heading (optional)" style="max-width: 90%; margin: 10px auto;">' +
            '<div style="text-align:left; width: 90%; margin: 10px auto 0; font-size:14px;">Date (optional):</div>' +
            '<input type="date" id="printDate" class="swal2-input" style="max-width: 90%; margin: 5px auto 10px;">' +
            '<div style="text-align:left; width: 90%; margin: 10px auto 0; font-size:14px;">Page Size:</div>' +
            '<select id="printPageSize" class="swal2-select" style="max-width: 90%; margin: 5px auto 10px;"><option value="A4">A4</option><option value="Legal">Legal</option><option value="Letter">Letter</option></select>' +
            '<div style="text-align:left; width: 90%; margin: 10px auto 0; font-size:14px;">Orientation:</div>' +
            '<select id="printOrientation" class="swal2-select" style="max-width: 90%; margin: 5px auto 10px;"><option value="portrait">Portrait</option><option value="landscape">Landscape</option></select>',
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: '<i class="ti ti-printer"></i> Print',
        preConfirm: () => {
            return {
                heading: document.getElementById('printHeading').value,
                subHeading: document.getElementById('printSubHeading').value,
                date: document.getElementById('printDate').value,
                pageSize: document.getElementById('printPageSize').value,
                orientation: document.getElementById('printOrientation').value
            }
        }
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        let heading = result.value.heading;
        let subHeading = result.value.subHeading;
        let dateVal = result.value.date;
        let pageSize = result.value.pageSize;
        let orientation = result.value.orientation || 'portrait';
        
        let dynStyle = document.getElementById('dynamicPrintStyle');
        if(!dynStyle) {
            dynStyle = document.createElement('style');
            dynStyle.id = 'dynamicPrintStyle';
            document.head.appendChild(dynStyle);
        }
        dynStyle.innerHTML = `@page { size: ${pageSize} ${orientation}; margin: 0.4in; }`;
        
        let headingEl = document.getElementById("customPrintHeading");
        if (!headingEl) {
            headingEl = document.createElement("div");
            headingEl.id = "customPrintHeading";
            headingEl.style.cssText = "text-align:center; margin-bottom: 20px; display: none;";
            headingEl.className = "print-only";
            headingEl.innerHTML = '<h3 style="font-weight: bold; margin-bottom: 5px;"></h3><h5 style="font-weight: normal; font-size: 14pt; margin:0;"></h5><div class="printDateDisplay" style="text-align:right; font-weight: bold; margin-top:10px;"></div>';
            document.querySelector('.col-md-9, .col-lg-10').prepend(headingEl);
        }
        
        headingEl.querySelector('h3').innerText = heading;
        headingEl.querySelector('h5').innerText = subHeading;
        headingEl.querySelector('h5').style.display = subHeading ? 'block' : 'none';
        
        let dateDisplay = headingEl.querySelector('.printDateDisplay');
        if (dateVal) {
            const d = new Date(dateVal);
            dateDisplay.innerText = "Date: " + d.toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'});
            dateDisplay.style.display = 'block';
        } else {
            dateDisplay.style.display = 'none';
        }
        
        headingEl.style.display = 'block';

    const parent = document.querySelector('.col-md-9, .col-lg-10');
    const children = parent.children;
    const hidden = [];
    const sections = sectionIds.map(id => document.getElementById(id)).filter(el => el);
    
    if (sections.length === 0) return;

    for (let i = 0; i < children.length; i++) {
      const child = children[i];
      if (child.id === 'customPrintHeading') continue; // Don't hide our heading
      let containsSection = false;
      for (let sec of sections) {
        if (child.contains(sec) || child === sec.closest('.row')) {
          containsSection = true;
        }
      }
      if (!containsSection) {
        child.style.display = 'none';
        hidden.push(child);
      }
    }
    
    const rows = parent.querySelectorAll(':scope > .row');
    const hiddenRows = [];
    rows.forEach(row => {
      let containsSection = false;
      for (let sec of sections) {
        if (row.contains(sec)) {
          containsSection = true;
        }
      }
      if (!containsSection) {
        row.style.display = 'none';
        hiddenRows.push(row);
      }
    });
    
    setTimeout(() => {
        window.print();
        
        hidden.forEach(el => el.style.display = '');
        hiddenRows.forEach(el => el.style.display = '');
        headingEl.style.display = 'none';
    }, 300);
  });
}
</script>