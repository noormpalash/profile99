<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Personnel.php';
require_once __DIR__ . '/../classes/AppSettings.php';
require_once __DIR__ . '/../config/db.php';
Auth::requireLogin();

$id = (int)($_GET['id'] ?? 0);
$person = Personnel::find($id);
if (!$person) {
    include __DIR__ . '/../includes/header.php';
    echo '<div class="container py-5 text-center"><div class="card shadow-sm p-5 mx-auto" style="max-width:480px;border-radius:16px;"><i class="ti ti-file-alert text-warning" style="font-size:3rem;"></i><h5 class="mt-3">Record Not Found</h5><p class="text-muted">The personnel record you are looking for does not exist or has been deleted.</p><a href="personnel.php" class="btn btn-primary btn-sm mt-2"><i class="ti ti-arrow-left me-1"></i>Back to Personnel</a></div></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$socialLinks = Personnel::getSocialLinks($id);
$service = Personnel::getService($id);
$family = Personnel::getFamily($id);
$health = Personnel::getHealth($id);
$note = Personnel::getNotes($id);
$courses = Personnel::getCourses($id);
$moqs = Personnel::getMoqs($id);
$cadres = Personnel::getCadres($id);
$leaves = Personnel::getLeaves($id);

// Promotion Qualification Logic
$hasPromotionLogic = false;
$isQualified = false;
$rankLower = strtolower(trim($person['rank_name'] ?? ''));
if (in_array($rankLower, ['sainik', 'lance corporal', 'corporal', 'sergeant', 'warrant officer'])) {
    $hasPromotionLogic = true;
    $moqNames = array_map(fn($m) => strtolower(trim($m['moq_name'] ?? '')), $moqs);
    $hasMoq = fn($name) => in_array(strtolower($name), $moqNames);

    $missingMoqs = [];

    if ($rankLower === 'sainik') {
        if (!$hasMoq('btt')) $missingMoqs[] = 'BTT';
        if (!$hasMoq('arms commando') && !$hasMoq('services commando')) $missingMoqs[] = 'Arms Commando or Services Commando';
        if (!$hasMoq('pe')) $missingMoqs[] = 'PE';
    } elseif ($rankLower === 'lance corporal') {
        if (!$hasMoq('pc')) $missingMoqs[] = 'PC';
        if (!$hasMoq('pe')) $missingMoqs[] = 'PE';
    } elseif ($rankLower === 'corporal') {
        if (!$hasMoq('att')) $missingMoqs[] = 'ATT';
        if (!$hasMoq('pe')) $missingMoqs[] = 'PE';
    } elseif ($rankLower === 'sergeant') {
        if (!$hasMoq('ncoc')) $missingMoqs[] = 'NCOC';
        if (!$hasMoq('utility course')) $missingMoqs[] = 'Utility Course';
    } elseif ($rankLower === 'warrant officer') {
        if (!$hasMoq('clm cadre') && !$hasMoq('jcoc')) $missingMoqs[] = 'CLM Cadre or JCOC';
    }

    $isQualified = empty($missingMoqs);
}

$icons = ['facebook'=>'ti ti-brand-facebook','linkedin'=>'ti ti-brand-linkedin','whatsapp'=>'ti ti-brand-whatsapp','twitter'=>'ti ti-brand-x'];
$display = static fn($value, string $fallback = '-') => ($value !== null && trim((string)$value) !== '') ? htmlspecialchars((string)$value) : $fallback;
$displayDate = static fn($value, string $fallback = '-') => ($value !== null && trim((string)$value) !== '' && $value !== '0000-00-00') ? date('d/m/y', strtotime((string)$value)) : $fallback;
$customAbsentStatuses = AppSettings::getCustomAbsentStatuses();

$statusLabelMap = [
    'active' => 'Active',
    'on_leave' => 'On Leave',
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
    $statusLabelMap[$cKey] = $cLabel;
}
if (!isset($statusLabelMap['others'])) {
    $statusLabelMap['others'] = reset($customAbsentStatuses) ?: 'Others';
}

if (!empty($person['current_leave_type'])) {
    $person['status'] = 'on_leave';
    $lt = $person['current_leave_type'];
    $displayLt = $lt === 'Pre Leave' ? 'P/L' : trim(str_ireplace('leave', '', $lt));
    $statusLabel = 'On Leave (' . $displayLt . ')';
} elseif (($person['status'] ?? '') === 'on_leave') {
    $person['status'] = 'active';
    $statusLabel = 'Active';
} else {
    $statusRawLower = strtolower(trim((string)($person['status'] ?? '')));
    if (isset($statusLabelMap[$statusRawLower])) {
        $statusLabel = $statusLabelMap[$statusRawLower];
    } else {
        $statusLabel = ucwords(str_replace('_', ' ', (string)($person['status'] ?? 'unknown')));
    }
}

// Status color class mapping
$statusRaw = strtolower(trim((string)($person['status'] ?? '')));
$statusTagClass = 'status-tag-active';
if (in_array($statusRaw, ['inactive', 'retired', 'dismissed'], true)) {
    $statusTagClass = 'status-tag-inactive';
} elseif (in_array($statusRaw, ['leave', 'on_leave'], true)) {
    $statusTagClass = 'status-tag-leave';
} elseif (in_array($statusRaw, ['mission', 'un_mission'], true)) {
    $statusTagClass = 'status-tag-mission';
} elseif (isset($customAbsentStatuses[$statusRaw]) || $statusRaw === 'others') {
    $statusTagClass = 'status-tag-others';
} elseif (in_array($statusRaw, ['cmh', 'trg', 'cmd', 'att', 'goc_gd', 'suspend', 'osl', 'awol'], true)) {
    $statusTagClass = 'status-tag-absent';
}

$canEdit = Auth::hasAnyPermission([
    'edit_personnel_basic', 'edit_personnel_course', 'edit_personnel_education', 
    'edit_personnel_service', 'edit_personnel_family', 'edit_personnel_health', 
    'edit_personnel_social', 'edit_personnel_notes', 'edit_personnel_leaves', 
    'edit_personnel_cadres', 'edit_personnel_moqs', 'edit_personnel_ipft', 
    'edit_personnel_yearly_plan', 'edit_personnel_status', 'edit_personnel_family_member_status'
]);
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
  html, body {
    overflow-x: hidden !important;
  }

  .main-content.no-sidebar {
    margin-left: 0 !important;
    width: 100% !important;
    padding-left: 20px !important;
    padding-right: 20px !important;
  }

  .leave-table-scroll {
    max-height: 145px;
    overflow-y: auto;
    overflow-x: hidden;
  }
  .leave-table-scroll thead th {
    position: sticky;
    top: 0;
    background: #f8f9fa;
    z-index: 10;
    box-shadow: 0 1px 0 rgba(0,0,0,0.05);
  }
  .leave-table-scroll tfoot td {
    position: sticky;
    bottom: -1px;
    background: #f8f9fa;
    z-index: 10;
    box-shadow: 0 -1px 0 rgba(0,0,0,0.05);
  }

  .profile-page {
    width: 100%;
    max-width: 100%;
    margin: 0;
    overflow-x: hidden;
    box-sizing: border-box;
  }

  /* ── Hero Banner Container ── */
  .profile-page .hero-card-container {
    position: relative;
    margin-bottom: 65px;
    border-radius: 20px;
  }

  .profile-page .hero-bg-layer {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 55%, #1d4ed8 100%);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 36px rgba(15, 23, 42, 0.18);
  }

  .profile-page .hero-bg-layer::before {
    content: '';
    position: absolute;
    top: -40%; right: -10%;
    width: 320px; height: 320px;
    background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
  }

  .profile-page .hero-body {
    position: relative;
    z-index: 1;
    padding: 32px 36px 60px 36px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    color: #fff;
  }

  .profile-page .hero-left {
    display: flex;
    align-items: flex-end;
    gap: 28px;
    flex: 1;
    min-width: 0;
  }

  /* 2x Bigger Overlapping Avatar Frame (210px x 210px) */
  .profile-page .hero-avatar-wrap {
    position: relative;
    margin-bottom: -115px;
    flex-shrink: 0;
    z-index: 10;
  }

  .profile-page .hero-avatar {
    width: 210px;
    height: 210px;
    border-radius: 24px;
    object-fit: cover;
    border: 5px solid #ffffff;
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.22);
    background: #fff;
    display: block;
  }

  .profile-page .hero-info {
    flex: 1;
    min-width: 0;
    padding-bottom: 6px;
  }

  .profile-page .hero-name {
    font-size: 1.75rem;
    font-weight: 800;
    margin: 0 0 4px;
    letter-spacing: -0.01em;
    line-height: 1.2;
    color: #ffffff;
  }

  .profile-page .hero-pno {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.75);
    font-weight: 500;
    margin-bottom: 12px;
  }

  .profile-page .hero-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .profile-page .hero-tag {
    padding: 5px 15px;
    border-radius: 999px;
    font-size: 0.82rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    background: rgba(255, 255, 255, 0.15);
    color: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.12);
  }

  /* ── Status Color Badges ── */
  .profile-page .hero-tag.status-tag-active {
    background: rgba(16, 185, 129, 0.25);
    color: #34d399;
    border-color: rgba(52, 211, 153, 0.4);
    box-shadow: 0 0 12px rgba(52, 211, 153, 0.25);
  }

  .profile-page .hero-tag.status-tag-inactive {
    background: rgba(239, 68, 68, 0.25);
    color: #fca5a5;
    border-color: rgba(248, 113, 113, 0.4);
    box-shadow: 0 0 12px rgba(248, 113, 113, 0.2);
  }

  .profile-page .hero-tag.status-tag-leave {
    background: rgba(245, 158, 11, 0.25);
    color: #fcd34d;
    border-color: rgba(251, 191, 36, 0.4);
    box-shadow: 0 0 12px rgba(251, 191, 36, 0.2);
  }

  .profile-page .hero-tag.status-tag-mission {
    background: rgba(6, 182, 212, 0.25);
    color: #67e8f9;
    border-color: rgba(34, 211, 238, 0.4);
    box-shadow: 0 0 12px rgba(34, 211, 238, 0.25);
  }

  .profile-page .hero-tag.status-tag-others {
    background: rgba(168, 85, 247, 0.25);
    color: #d8b4fe;
    border-color: rgba(192, 132, 252, 0.4);
    box-shadow: 0 0 12px rgba(168, 85, 247, 0.25);
  }

  .profile-page .hero-tag.status-tag-absent {
    background: rgba(249, 115, 22, 0.25);
    color: #fdba74;
    border-color: rgba(251, 146, 60, 0.4);
    box-shadow: 0 0 12px rgba(249, 115, 22, 0.25);
  }

  /* ── Glowing Appointment Tag ── */
  .profile-page .hero-tag.appointment-glow-tag {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 0 14px rgba(99, 102, 241, 0.85), 0 0 4px rgba(139, 92, 246, 0.6);
    animation: appointmentGlowPulse 2.5s infinite alternate;
  }

  @keyframes appointmentGlowPulse {
    0% { box-shadow: 0 0 8px rgba(99, 102, 241, 0.6), 0 0 3px rgba(139, 92, 246, 0.4); }
    100% { box-shadow: 0 0 18px rgba(99, 102, 241, 0.95), 0 0 8px rgba(139, 92, 246, 0.8); }
  }

  /* Social Links on Right Side of Hero Banner */
  .profile-page .hero-social {
    flex-shrink: 0;
    text-align: right;
    padding-bottom: 6px;
  }

  .profile-page .hero-social-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(255, 255, 255, 0.65);
    margin-bottom: 8px;
  }

  .profile-page .hero-social-list {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 6px;
  }

  .profile-page .hero-social-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 15px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.22);
    color: #ffffff;
    text-decoration: none;
    font-size: 0.82rem;
    font-weight: 600;
    backdrop-filter: blur(4px);
    transition: all 0.2s ease;
  }

  .profile-page .hero-social-pill:hover {
    background: rgba(255, 255, 255, 0.3);
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }

  .profile-page .hero-social-pill i { font-size: 1rem; }
  .profile-page .hero-social-empty { font-size: 0.82rem; color: rgba(255, 255, 255, 0.5); font-style: italic; }

  /* ── Quick Look Section (Matches Profile White Theme & Compact Layout) ── */
  .profile-page .quick-look-box {
    background: #ffffff;
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-radius: 18px;
    padding: 16px 18px;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
    height: 100%;
  }

  .profile-page .quick-look-header {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .profile-page .quick-look-header h5 {
    margin: 0;
    font-size: 0.82rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.07em;
  }

  /* Compact 3-col grid on desktop (6 cards total) */
  .profile-page .quick-look-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
  }

  .profile-page .stat-item-compact {
    background: #f8fafc;
    border: 1px solid rgba(148, 163, 184, 0.15);
    border-radius: 10px;
    padding: 8px 10px;
    transition: all 0.2s ease;
  }

  .profile-page .stat-item-compact:hover {
    background: #eef4ff;
    border-color: #bfdbfe;
    transform: translateY(-1px);
  }

  .profile-page .stat-label-compact {
    font-size: 0.63rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
    margin-bottom: 2px;
  }

  .profile-page .stat-val-compact {
    font-size: 0.86rem;
    font-weight: 700;
    color: #0f172a;
    word-break: break-word;
  }

  /* Special Note Card (Right Side) */
  .profile-page .sn-box {
    background: #fff;
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-radius: 18px;
    padding: 16px 18px;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
    height: 100%;
    display: flex;
    flex-direction: column;
  }

  .profile-page .sn-header {
    margin-bottom: 10px;
  }

  .profile-page .sn-header h5 {
    margin: 0;
    font-size: 0.82rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.07em;
  }

  .profile-page .sn-body {
    flex: 1;
  }

  .profile-page .sn-note {
    color: #334155;
    font-size: 0.88rem;
    line-height: 1.5;
    border-left: 4px solid #3b82f6;
    padding: 10px 12px;
    background: #f8fafc;
    border-radius: 0 10px 10px 0;
  }

  .profile-page .sn-empty {
    color: #94a3b8;
    font-size: 0.84rem;
    font-style: italic;
  }

  /* ── Info Cards ── */
  .profile-page .info-card {
    background: #fff;
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    height: 100%;
  }

  .profile-page .info-card-head {
    padding: 12px 18px 8px;
    border-bottom: 1px solid rgba(15, 23, 42, 0.05);
  }

  .profile-page .info-card-head h5 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 700;
    color: #0f172a;
  }

  .profile-page .info-card-head p {
    margin: 2px 0 0;
    font-size: 0.76rem;
    color: #94a3b8;
  }

  .profile-page .info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
  }

  .profile-page .irow {
    display: grid;
    grid-template-columns: 130px minmax(0, 1fr);
    gap: 8px;
    padding: 9px 18px;
    border-bottom: 1px solid rgba(15, 23, 42, 0.04);
    font-size: 0.86rem;
  }

  .profile-page .irow:last-child,
  .profile-page .info-grid .irow:nth-last-child(2):nth-child(odd) {
    border-bottom: none;
  }

  .profile-page .info-grid .irow:nth-child(odd) {
    border-right: 1px solid rgba(15, 23, 42, 0.04);
  }

  .profile-page .irow.full {
    grid-column: 1 / -1;
    border-right: none !important;
  }

  .profile-page .irow-l {
    color: #94a3b8;
    font-size: 0.78rem;
    font-weight: 600;
  }

  .profile-page .irow-v {
    color: #1e293b;
    font-weight: 500;
    word-break: break-word;
  }

  /* ── Compact Courses & Cadres Card View ── */
  .profile-page .compact-cc-card {
    background: #fff;
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
    padding: 14px 18px;
  }

  .profile-page .compact-cc-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 6px;
    border-bottom: 1px solid rgba(15, 23, 42, 0.05);
  }

  .profile-page .compact-cc-head h5 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 700;
    color: #0f172a;
  }

  .profile-page .cadre-compact-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 10px;
  }

  .profile-page .cadre-tag-compact {
    display: inline-block;
    background: #e0e7ff;
    color: #3730a3;
    padding: 3px 10px;
    border-radius: 999px;
    font-weight: 600;
    font-size: 0.78rem;
    border: 1px solid #c7d2fe;
  }

  .profile-page .course-compact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 6px;
  }

  .profile-page .course-compact-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8fafc;
    border: 1px solid rgba(148, 163, 184, 0.12);
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 0.82rem;
  }

  .profile-page .course-compact-name {
    color: #1e293b;
    font-weight: 600;
  }

  .profile-page .course-compact-result {
    color: #475569;
    font-size: 0.75rem;
    font-weight: 600;
    background: #ffffff;
    padding: 2px 8px;
    border-radius: 6px;
    border: 1px solid rgba(148, 163, 184, 0.2);
  }

  .profile-page .empty-msg {
    color: #94a3b8;
    font-size: 0.82rem;
    font-style: italic;
  }

  /* ── Responsive ── */
  @media (max-width: 991px) {
    .profile-page .hero-body {
      flex-direction: column;
      align-items: flex-start;
      gap: 18px;
    }
    .profile-page .hero-social {
      text-align: left;
      width: 100%;
    }
    .profile-page .hero-social-list {
      justify-content: flex-start;
    }
    .profile-page .quick-look-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (max-width: 767px) {
    .profile-page .hero-card-container {
      margin-bottom: 20px;
    }
    .profile-page .hero-body {
      padding: 22px 20px 40px 20px;
      text-align: center;
    }
    .profile-page .hero-left {
      flex-direction: column;
      align-items: center;
      gap: 14px;
      width: 100%;
    }
    .profile-page .hero-info {
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 100%;
    }
    .profile-page .hero-tags {
      justify-content: center;
    }
    .profile-page .hero-social {
      text-align: center;
      width: 100%;
    }
    .profile-page .hero-social-list {
      justify-content: center;
    }
    .profile-page .hero-avatar-wrap {
      margin-bottom: 5px;
    }
    .profile-page .hero-avatar {
      width: 130px;
      height: 130px;
    }
    .profile-page .hero-name { font-size: 1.35rem; }
    .profile-page .quick-look-grid { grid-template-columns: 1fr; }
    .profile-page .stat-item-compact[style*="grid-column"] { grid-column: 1 / -1 !important; }
    .profile-page .info-grid { grid-template-columns: 1fr; }
    .profile-page .info-grid .irow:nth-child(odd) { border-right: none; }
    .profile-page .irow { grid-template-columns: 1fr; gap: 3px; }
  }

  /* ── Editorial Bio Data Print Styling ── */
  @media screen {
    .bio-print-container { display: none !important; }
  }
  @media print {
    @page { margin: 12mm; size: A4; }
    html, body, .main-content, .container-fluid, .bio-print-container, div[class^="print-sec-"] { display: block !important; }
    body { background: #fff !important; margin: 0; padding: 0; color: #000; }
    #sidebar, .navbar, .page-header, .profile-page, .no-print { display: none !important; }
    .main-content, .container-fluid { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
    
    .bio-print-container { width: 100%; font-family: "Times New Roman", Times, serif; font-size: 10pt; line-height: 1.4; }
    .bio-header-wrap { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 8px; }
    .bio-header-text { flex: 1; text-align: center; }
    .bio-header-title { font-size: 18pt; font-weight: bold; text-transform: uppercase; margin: 0; letter-spacing: 2px; }
    .bio-header-sub { font-size: 9pt; font-style: italic; color: #333; margin-top: 5px; }
    
    .bio-photo-box { width: 85px; height: 105px; border: 2px solid #000; padding: 2px; flex-shrink: 0; }
    .bio-photo-box img { width: 100%; height: 100%; object-fit: cover; }
    .bio-photo-placeholder { width: 85px; height: 105px; border: 2px solid #000; flex-shrink: 0; }

    .bio-section-title { display: inline-block; width: 100%; box-sizing: border-box; font-size: 11pt; font-weight: bold; text-transform: uppercase; margin: 8px 0 3px; background: #e0e0e0 !important; -webkit-print-color-adjust: exact; padding: 3px 6px; border: 1px solid #000; page-break-after: avoid; break-after: avoid; }
    
    .bio-table { width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 0; margin-bottom: 4px; border-top: 1px solid #000; border-left: 1px solid #000; }
    .bio-table td, .bio-table th { border-bottom: 1px solid #000; border-right: 1px solid #000; border-top: none; border-left: none; padding: 3px 6px; vertical-align: middle; page-break-inside: avoid !important; break-inside: avoid !important; word-wrap: break-word; word-break: break-word; }
    .bio-table tr { page-break-inside: avoid !important; break-inside: avoid !important; page-break-after: auto !important; }
    .bio-table .lbl { width: 18%; font-weight: bold; background: #f7f7f7 !important; -webkit-print-color-adjust: exact; }
    .bio-table td.val:not([colspan]) { width: 32%; }
    
    .bio-table-list th { font-weight: bold; background: #f7f7f7 !important; -webkit-print-color-adjust: exact; text-align: center; }
    .bio-table-list td { text-align: center; }
    
    div[class^="print-sec-"] { page-break-inside: avoid !important; break-inside: avoid !important; }
    .avoid-break { page-break-inside: avoid !important; break-inside: avoid !important; }
    .page-break { page-break-before: always !important; break-before: page !important; }
  }
</style>

<div class="profile-page">
  <div class="page-header">
    <div>
      <h4>Profile</h4>
      <div class="text-muted">Personnel details and record information.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="<?= Auth::role() === 'user' ? 'search.php' : 'dashboard.php' ?>" class="btn btn-outline-secondary btn-sm no-print"><i class="ti ti-arrow-left me-1"></i>Back to list</a>
      <?php if ($canEdit): ?>
        <a href="edit_personnel.php?id=<?= $id ?>" class="btn btn-primary btn-sm no-print"><i class="ti ti-edit me-1"></i>Edit profile</a>
      <?php endif; ?>
      <?php if (Auth::hasPermission('print_records')): ?>
      <button type="button" class="btn-print no-print" onclick="printBioData()"><i class="ti ti-printer"></i> Print Profile</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="row g-3">

    <!-- Hero Banner with Clipped Pseudo Background & Overlapping Avatar Frame -->
    <div class="col-12">
      <div class="hero-card-container">
        <div class="hero-bg-layer"></div>
        <div class="hero-body">
          <div class="hero-left">
            <div class="hero-avatar-wrap">
              <img src="<?= htmlspecialchars(personnelPhotoUrl($person['photo_path'] ?? null)) ?>" class="hero-avatar" alt="Photo">
            </div>
            <div class="hero-info">
              <h1 class="hero-name"><?= htmlspecialchars($person['name']) ?></h1>
              <div class="hero-pno">Personal No. <?= htmlspecialchars($person['personal_number']) ?></div>
              <div class="hero-tags">
                <span class="hero-tag"><?= $display($person['rank_name'] ?? null) ?></span>
                <span class="hero-tag"><?= $display($person['unit_name'] ?? null) ?></span>
                <span class="hero-tag <?= htmlspecialchars($statusTagClass) ?>"><?= htmlspecialchars($statusLabel) ?></span>
                <?php if (!empty($person['appointment_name'])): ?>
                  <span class="hero-tag appointment-glow-tag"><?= htmlspecialchars($person['appointment_name']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <!-- Social Links on Right Side of Hero Banner -->
          <div class="hero-social">
            <div class="hero-social-label">Social Links</div>
            <?php if ($socialLinks): ?>
              <div class="hero-social-list">
                <?php foreach ($socialLinks as $link): 
                    $displayUrl = $link['url'];
                    if (strtolower($link['platform']) === 'whatsapp') {
                        $displayUrl = preg_replace('/[^0-9+]/', '', $displayUrl);
                        if (empty($displayUrl)) $displayUrl = $link['url'];
                    } else {
                        $displayUrl = preg_replace('#^https?://(www\.)?#i', '', $displayUrl);
                        $displayUrl = rtrim($displayUrl, '/');
                    }
                ?>
                  <div class="hero-social-pill" title="<?= htmlspecialchars(ucfirst($link['platform'])) ?>">
                    <i class="<?= $icons[strtolower($link['platform'])] ?? 'ti ti-link' ?>"></i>
                    <span><?= htmlspecialchars($displayUrl) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="hero-social-empty">No social links</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Look & Special Note Section -->
    <div class="col-12">
      <div class="row g-3">
        <!-- Quick Look Section (Left side: 6 stats including Platoon & Marital Status) -->
        <div class="col-lg-4">
          <div class="quick-look-box h-100">
            <div class="quick-look-header">
              <h5>Quick Look</h5>
            </div>
            <div class="quick-look-grid">
              <div class="stat-item-compact">
                <div class="stat-label-compact">Blood Group</div>
                <div class="stat-val-compact"><?= $display($person['blood_group_name'] ?? null) ?></div>
              </div>
              <div class="stat-item-compact">
                <div class="stat-label-compact">District</div>
                <div class="stat-val-compact"><?= $display($person['address'] ?? null) ?></div>
              </div>
              <div class="stat-item-compact">
                <div class="stat-label-compact">Platoon</div>
                <div class="stat-val-compact"><?= $display($person['platoon_name'] ?? null) ?></div>
              </div>
              <div class="stat-item-compact" style="grid-column: span 2;">
                <div class="stat-label-compact">MOQ Status</div>
                <div class="stat-val-compact" style="line-height:1.2;">
                  <?php if ($hasPromotionLogic): ?>
                    <?php if ($isQualified): ?>
                      <span class="text-success fw-bold">Qualified</span>
                    <?php else: ?>
                      <span class="text-danger fw-bold">Not Qualified</span>
                      <div class="text-danger fw-bold" style="margin-top:2px;">Need: <?= implode(', ', $missingMoqs) ?></div>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-muted">N/A</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Special Note Section (Middle) -->
        <div class="col-lg-4">
          <div class="sn-box h-100">
            <div class="sn-header">
              <h5>Special Note</h5>
            </div>
            <div class="sn-body">
              <?php if (!empty($note['note'])): ?>
                <div class="sn-note"><?= htmlspecialchars($note['note']) ?></div>
              <?php else: ?>
                <div class="sn-empty">No special notes recorded.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Leave Status Section (Right side) -->
        <div class="col-lg-4">
          <div class="sn-box h-100">
            <div class="sn-header d-flex justify-content-between align-items-center">
              <h5>Leave Status <span class="text-muted fw-normal" style="font-size: 0.75rem;">(<?= date('Y') ?>)</span></h5>
              <?php if (Auth::hasPermission('leave_history')): ?>
                <a href="leave_history.php?q=<?= urlencode($person['personal_number'] ?? '') ?>" class="btn btn-sm btn-outline-primary py-0 px-2 no-print" style="font-size: 0.72rem; border-radius: 999px;">
                  <i class="ti ti-history me-1"></i>All History
                </a>
              <?php endif; ?>
            </div>
            <div class="sn-body p-2">
              <?php
                $currentYear = (int) date('Y');
                $currentYearLeaves = array_filter($leaves ?? [], static function (array $l) use ($currentYear): bool {
                    $fromDate = $l['from_date'] ?? '';
                    if ($fromDate === '') {
                        return false;
                    }
                    return (int) date('Y', strtotime($fromDate)) === $currentYear;
                });
              ?>
              <?php if (!empty($currentYearLeaves)): ?>
                <?php
                   $totalLeaveDays = 0;
                   foreach ($currentYearLeaves as $l) {
                       $totalLeaveDays += (int)($l['total_days'] ?? 0);
                   }
                ?>
                <div class="table-responsive leave-table-scroll">
                  <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 0.75rem;">
                    <thead class="table-light text-secondary">
                      <tr>
                        <th>From</th>
                        <th>To</th>
                        <th>Days</th>
                        <th>Type</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($currentYearLeaves as $leave): ?>
                        <tr>
                          <td><?= $displayDate($leave['from_date']) ?></td>
                          <td><?= $displayDate($leave['to_date']) ?></td>
                          <td><?= $display($leave['total_days']) ?></td>
                          <td><?= htmlspecialchars($leave['leave_type'] === 'Pre Leave' ? 'P/L' : trim(str_ireplace('leave', '', $leave['leave_type'] ?? '-'))) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                      <tr>
                        <td colspan="2" class="text-end fw-bold">Total:</td>
                        <td colspan="2" class="fw-bold"><?= $totalLeaveDays ?> Days</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              <?php else: ?>
                <div class="sn-empty">No leave records found.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Yearly Plan -->
    <div class="col-12 mb-3">
      <div class="compact-cc-card">
        <div class="compact-cc-head">
          <h5>Yearly Plan</h5>
          <span class="text-muted" style="font-size:0.76rem;">Training & administration cycle plan</span>
        </div>
        <div class="row g-3 text-center mt-1">
          <?php foreach (['1st', '2nd', '3rd', '4th'] as $idx => $cycle): 
            $val = $service['cycle_' . ($idx+1)] ?? null;
          ?>
          <div class="col-md-3">
            <div style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px;"><?= $cycle ?> Cycle</div>
            <div style="font-size:1rem; font-weight:600; color:#1e293b;"><?= $val ? htmlspecialchars($val) : '-' ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Contact Information -->
    <div class="col-lg-6">
      <section class="info-card">
        <div class="info-card-head">
          <h5>Contact Information</h5>
          <p>Primary contact and address details.</p>
        </div>
        <div class="info-grid">
          <div class="irow">
            <div class="irow-l">Mobile Number</div>
            <div class="irow-v"><?= $display($person['mobile_number'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Unit</div>
            <div class="irow-v"><?= $display($person['unit_name'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Vill</div>
            <div class="irow-v"><?= $display($person['vill'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">PO</div>
            <div class="irow-v"><?= $display($person['po'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">PS</div>
            <div class="irow-v"><?= $display($person['ps'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">District</div>
            <div class="irow-v"><?= $display($person['address'] ?? null) ?></div>
          </div>
        </div>
      </section>
    </div>

    <!-- Health Information -->
    <div class="col-lg-6">
      <section class="info-card">
        <div class="info-card-head">
          <h5>Health Information</h5>
          <p>Medical and physical record summary.</p>
        </div>
        <div class="info-grid">
          <div class="irow">
            <div class="irow-l">Status</div>
            <div class="irow-v"><?= htmlspecialchars($statusLabel) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Medical Category</div>
            <div class="irow-v"><?= $display($health['medical_category_name'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Height</div>
            <div class="irow-v"><?= $display(isset($health['height_cm']) && $health['height_cm'] !== '' ? $health['height_cm'] . ' cm' : null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Weight</div>
            <div class="irow-v"><?= $display(isset($health['weight_kg']) && $health['weight_kg'] !== '' ? $health['weight_kg'] . ' kg' : null) ?></div>
          </div>
          <div class="irow full">
            <div class="irow-l">Known Condition</div>
            <div class="irow-v"><?= $display($health['any_disease'] ?? null) ?></div>
          </div>
        </div>
      </section>
    </div>

    <!-- Family Information -->
    <div class="col-lg-6">
      <section class="info-card">
        <div class="info-card-head">
          <h5>Family Information</h5>
          <p>Household and relationship details.</p>
        </div>
        <div class="info-grid">
          <div class="irow">
            <div class="irow-l">Father</div>
            <div class="irow-v"><?= $display($family['father_name'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Father Mobile</div>
            <div class="irow-v"><?= $display($family['father_mobile'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Mother</div>
            <div class="irow-v"><?= $display($family['mother_name'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Mother Mobile</div>
            <div class="irow-v"><?= $display($family['mother_mobile'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Spouse</div>
            <div class="irow-v"><?= $display($family['spouse_name'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Spouse Mobile</div>
            <div class="irow-v"><?= $display($family['spouse_mobile'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Marital Status</div>
            <div class="irow-v"><?= $display(!empty($family['marital_status']) ? ucfirst($family['marital_status']) : null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Children Count</div>
            <div class="irow-v"><?= $display($family['children_count'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Family Member</div>
            <div class="irow-v">
              <?= $display($family['family_member'] ?? 'No') ?>
              <?php if (($family['family_member'] ?? 'No') === 'Yes' && (!empty($family['fm_date_from']) || !empty($family['fm_date_to']))): ?>
                <span class="text-muted ms-2 small">(<?= $displayDate($family['fm_date_from'], '') ?> to <?= $displayDate($family['fm_date_to'], '') ?>)</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="irow">
            <div class="irow-l">Living Status</div>
            <div class="irow-v"><?= $display($family['living_status'] ?? null) ?></div>
          </div>
          <?php if (($family['family_member'] ?? 'No') === 'Yes' && !empty($family['fm_current_address'])): ?>
          <div class="irow full">
            <div class="irow-l">Currently Living Address</div>
            <div class="irow-v"><?= $display($family['fm_current_address'] ?? null) ?></div>
          </div>
          <?php endif; ?>
        </div>
      </section>
    </div>

    <!-- Service Information -->
    <div class="col-lg-6">
      <section class="info-card">
        <div class="info-card-head">
          <h5>Service Information</h5>
          <p>Career, assignment, and service details.</p>
        </div>
        <div class="info-grid">
          <div class="irow">
            <div class="irow-l">NID</div>
            <div class="irow-v"><?= $display($person['nid'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Admission Date</div>
            <div class="irow-v"><?= $displayDate($service['admission_date'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Retirement Date</div>
            <div class="irow-v"><?= $displayDate($service['retirement_date'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Batch</div>
            <div class="irow-v"><?= $display($person['batch'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Rank</div>
            <div class="irow-v"><?= $display($person['rank_name'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Platoon</div>
            <div class="irow-v"><?= $display($person['platoon_name'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">UN Mission</div>
            <div class="irow-v"><?= $display($service['un_mission'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">IPFT (1st Annual)</div>
            <div class="irow-v"><?= $display($service['ipft_1st'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">IPFT (2nd Annual)</div>
            <div class="irow-v"><?= $display($service['ipft_2nd'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">RET</div>
            <div class="irow-v"><?= $display($service['ret'] ?? null) ?></div>
          </div>
          <div class="irow">
            <div class="irow-l">Speed March</div>
            <div class="irow-v"><?= $display($service['speed_march'] ?? null) ?></div>
          </div>
          <div class="irow full">
            <div class="irow-l">Punishment Note</div>
            <div class="irow-v"><?= $display($service['punishment_note'] ?? null) ?></div>
          </div>
        </div>
      </section>
    </div>

    <!-- Courses, Cadres & MOQs -->
    <div class="col-12 mt-3">
      <div class="row g-3">
        <!-- Cadres -->
        <div class="col-md-4">
          <div class="compact-cc-card h-100">
            <div class="compact-cc-head">
              <h5>Cadres</h5>
              <span class="text-muted" style="font-size:0.76rem;">Recorded cadres</span>
            </div>
            <?php if ($cadres && count($cadres) > 0): ?>
              <div class="cadre-compact-wrap">
                <?php foreach ($cadres as $c): ?>
                  <span class="cadre-tag-compact"><?= htmlspecialchars($c['name']) ?></span>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="empty-msg" style="padding:0;">No cadres recorded.</div>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- Courses -->
        <div class="col-md-4">
          <div class="compact-cc-card h-100">
            <div class="compact-cc-head">
              <h5>Courses</h5>
              <span class="text-muted" style="font-size:0.76rem;">Recorded courses & results</span>
            </div>
            <?php if ($courses): ?>
              <div class="course-compact-grid">
                <?php foreach ($courses as $course): ?>
                  <div class="course-compact-item">
                    <span class="course-compact-name"><?= htmlspecialchars($course['course_name']) ?></span>
                    <span class="course-compact-result"><?= $display($course['result'] ?? null) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="empty-msg" style="padding:0;">No courses recorded yet.</div>
            <?php endif; ?>
          </div>
        </div>

        <!-- MOQs -->
        <div class="col-md-4">
          <div class="compact-cc-card h-100">
            <div class="compact-cc-head">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h5>MOQs</h5>
                  <span class="text-muted" style="font-size:0.76rem;">Recorded MOQs & results</span>
                </div>
                <?php if ($hasPromotionLogic): ?>
                  <div class="ms-2">
                    <span class="badge <?= $isQualified ? 'bg-success' : 'bg-danger' ?>" style="font-size:0.7rem; padding: 4px 6px;">
                      <?= $isQualified ? 'Qualified for Promotion' : 'Not Qualified for Promotion' ?>
                    </span>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            <?php if ($moqs): ?>
              <div class="course-compact-grid">
                <?php foreach ($moqs as $moq): ?>
                  <div class="course-compact-item">
                    <span class="course-compact-name"><?= htmlspecialchars($moq['moq_name']) ?></span>
                    <span class="course-compact-result"><?= $display($moq['result'] ?? null) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="empty-msg" style="padding:0;">No MOQs recorded yet.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>


  </div>
</div>

<!-- Print Only Bio Data Container -->
<div class="bio-print-container">
  
  <div class="bio-header-wrap avoid-break">
    <div style="width: 85px;"></div> <!-- Spacer for symmetry -->
    <div class="bio-header-text">
      <h1 class="bio-header-title">Bio Data</h1>
    </div>
    <?php if(!empty($person['photo_path'])): ?>
      <div class="bio-photo-box">
        <img src="<?= htmlspecialchars(personnelPhotoUrl($person['photo_path'])) ?>" alt="Photo">
      </div>
    <?php else: ?>
      <div class="bio-photo-placeholder"></div>
    <?php endif; ?>
  </div>

  <div class="print-sec-personal">
    <div class="bio-section-title avoid-break">1. Personal & Service Details</div>
    <table class="bio-table avoid-break">
      <tr>
        <td class="lbl">Name</td>
        <td class="val" colspan="3"><strong><?= htmlspecialchars($person['name']) ?></strong></td>
      </tr>
      <tr>
        <td class="lbl">Personal No</td>
        <td class="val"><?= htmlspecialchars($person['personal_number']) ?></td>
        <td class="lbl">Rank</td>
        <td class="val"><?= $display($person['rank_name'] ?? null) ?></td>
      </tr>
      <tr>
        <td class="lbl">Unit</td>
        <td class="val"><?= $display($person['unit_name'] ?? null) ?></td>
        <td class="lbl">Platoon</td>
        <td class="val"><?= $display($person['platoon_name'] ?? null) ?></td>
      </tr>
      <tr>
        <td class="lbl">Appointment</td>
        <td class="val"><?= $display($person['appointment_name'] ?? null) ?></td>
        <td class="lbl">Batch</td>
        <td class="val"><?= $display($person['batch'] ?? null) ?></td>
      </tr>
      <tr>
        <td class="lbl">Status</td>
        <td class="val"><?= htmlspecialchars($statusLabel) ?></td>
        <td class="lbl">Admission Date</td>
        <td class="val"><?= $displayDate($service['admission_date'] ?? null) ?></td>
      </tr>
      <tr>
        <td class="lbl">UN Mission</td>
        <td class="val"><?= $display($service['un_mission'] ?? null) ?></td>
        <td class="lbl">Retirement Date</td>
        <td class="val"><?= $displayDate($service['retirement_date'] ?? null) ?></td>
      </tr>
      <tr>
        <td class="lbl">Blood Group</td>
        <td class="val"><?= $display($person['blood_group_name'] ?? null) ?></td>
        <td class="lbl">NID</td>
        <td class="val"><?= $display($person['nid'] ?? null) ?></td>
      </tr>
    </table>
  </div>

  <div class="print-sec-contact">
    <div class="bio-section-title avoid-break">2. Contact & Address</div>
    <table class="bio-table avoid-break">
      <tr>
        <td class="lbl">Mobile</td>
        <td class="val"><?= $display($person['mobile_number'] ?? null) ?></td>
        <td class="lbl">District</td>
        <td class="val"><?= $display($person['address'] ?? null) ?></td>
      </tr>
      <tr>
        <td class="lbl">Vill</td>
        <td class="val"><?= $display($person['vill'] ?? null) ?></td>
        <td class="lbl">PO</td>
        <td class="val"><?= $display($person['po'] ?? null) ?></td>
      </tr>
      <tr>
        <td class="lbl">PS</td>
        <td class="val" colspan="3"><?= $display($person['ps'] ?? null) ?></td>
      </tr>
    </table>
  </div>

  <div class="print-sec-family">
    <div class="bio-section-title avoid-break">3. Family Information</div>
    <table class="bio-table avoid-break">
      <tr>
        <td class="lbl">Father's Name</td>
        <td class="val"><?= $display($family['father_name'] ?? null) ?></td>
        <td class="lbl">Mobile</td>
        <td class="val"><?= $display($family['father_mobile'] ?? null) ?></td>
      </tr>
      <tr>
        <td class="lbl">Mother's Name</td>
        <td class="val"><?= $display($family['mother_name'] ?? null) ?></td>
        <td class="lbl">Mobile</td>
        <td class="val"><?= $display($family['mother_mobile'] ?? null) ?></td>
      </tr>
      <tr>
        <td class="lbl">Spouse's Name</td>
        <td class="val"><?= $display($family['spouse_name'] ?? null) ?></td>
        <td class="lbl">Mobile</td>
        <td class="val"><?= $display($family['spouse_mobile'] ?? null) ?></td>
      </tr>
      <tr>
        <td class="lbl">Marital Status</td>
        <td class="val"><?= $display(!empty($family['marital_status']) ? ucfirst($family['marital_status']) : null) ?></td>
        <td class="lbl">Children Count</td>
        <td class="val"><?= $display($family['children_count'] ?? null) ?></td>
      </tr>
      <tr>
        <td class="lbl">Living Status</td>
        <td class="val"><?= $display($family['living_status'] ?? null) ?></td>
        <td class="lbl">Family Member</td>
        <td class="val">
          <?= $display($family['family_member'] ?? 'No') ?>
          <?php if (($family['family_member'] ?? 'No') === 'Yes' && (!empty($family['fm_date_from']) || !empty($family['fm_date_to']))): ?>
            <br>(<?= $displayDate($family['fm_date_from'] ?? null, '') ?> - <?= $displayDate($family['fm_date_to'] ?? null, '') ?>)
          <?php endif; ?>
        </td>
      </tr>
      <?php if (($family['family_member'] ?? 'No') === 'Yes' && !empty($family['fm_current_address'])): ?>
      <tr>
        <td class="lbl">FM Address</td>
        <td class="val" colspan="3"><?= $display($family['fm_current_address']) ?></td>
      </tr>
      <?php endif; ?>
    </table>
  </div>

  <div class="print-sec-health">
    <div class="bio-section-title avoid-break">4. Health Details</div>
    <table class="bio-table avoid-break">
      <tr>
        <td class="lbl">Height</td>
        <td class="val"><?= $display(isset($health['height_cm']) && $health['height_cm'] !== '' ? $health['height_cm'] . ' cm' : null) ?></td>
        <td class="lbl">Weight</td>
        <td class="val"><?= $display(isset($health['weight_kg']) && $health['weight_kg'] !== '' ? $health['weight_kg'] . ' kg' : null) ?></td>
      </tr>
      <tr>
        <td class="lbl">Medical Cat</td>
        <td class="val"><?= $display($health['medical_category_name'] ?? null) ?></td>
        <td class="lbl">Known Disease</td>
        <td class="val"><?= $display($health['any_disease'] ?? null) ?></td>
      </tr>
    </table>
  </div>

  <div class="print-sec-training page-break">
    <div class="bio-section-title avoid-break">5. Training & Qualifications</div>
    <table class="bio-table avoid-break">
      <tr>
        <td class="lbl">Cadres</td>
        <td class="val" colspan="3"><?php if($cadres) { echo implode(', ', array_map(fn($c) => htmlspecialchars($c['name']), $cadres)); } else { echo '-'; } ?></td>
      </tr>
      <tr>
        <td class="lbl">Courses</td>
        <td class="val" colspan="3"><?php if($courses) { echo implode(', ', array_map(fn($c) => htmlspecialchars($c['course_name']) . ' (' . htmlspecialchars($c['result'] ?? '') . ')', $courses)); } else { echo '-'; } ?></td>
      </tr>
      <tr>
        <td class="lbl">MOQs</td>
        <td class="val" colspan="3"><?php if($moqs) { echo implode(', ', array_map(fn($c) => htmlspecialchars($c['moq_name']) . ' (' . htmlspecialchars($c['result'] ?? '') . ')', $moqs)); } else { echo '-'; } ?></td>
      </tr>
    </table>
  </div>

  <div class="print-sec-perf">
    <div class="bio-section-title avoid-break">6. Performance & Checks</div>
    <table class="bio-table avoid-break">
      <tr>
        <td class="lbl">IPFT (1st)</td>
        <td class="val"><?= $display($service['ipft_1st'] ?? null) ?></td>
        <td class="lbl">IPFT (2nd)</td>
        <td class="val"><?= $display($service['ipft_2nd'] ?? null) ?></td>
      </tr>
      <tr>
        <td class="lbl">RET</td>
        <td class="val"><?= $display($service['ret'] ?? null) ?></td>
        <td class="lbl">Speed March</td>
        <td class="val"><?= $display($service['speed_march'] ?? null) ?></td>
      </tr>
      <tr>
        <td class="lbl">Yearly Plan</td>
        <td class="val" colspan="3">
          <?= $display($service['cycle_1'] ?? null) ?> | 
          <?= $display($service['cycle_2'] ?? null) ?> | 
          <?= $display($service['cycle_3'] ?? null) ?> | 
          <?= $display($service['cycle_4'] ?? null) ?>
        </td>
      </tr>
      <tr>
        <td class="lbl">Punishment</td>
        <td class="val" colspan="3"><?= $display($service['punishment_note'] ?? null) ?></td>
      </tr>
    </table>
  </div>

  <div class="print-sec-special">
    <div class="bio-section-title avoid-break">7. Special Note</div>
    <table class="bio-table avoid-break">
      <tr>
        <td class="lbl">Special Note</td>
        <td class="val"><?= $display($note['note'] ?? null) ?></td>
      </tr>
    </table>
  </div>

  <?php if (!empty($currentYearLeaves)): ?>
  <div class="print-sec-leave">
    <div class="bio-section-title avoid-break">8. Leave Records (<?= date('Y') ?>)</div>
    <table class="bio-table bio-table-list avoid-break">
      <tr>
        <th>From</th>
        <th>To</th>
        <th>Type</th>
        <th>Days</th>
      </tr>
      <?php foreach ($currentYearLeaves as $leave): ?>
        <tr>
          <td><?= $displayDate($leave['from_date'] ?? null) ?></td>
          <td><?= $displayDate($leave['to_date'] ?? null) ?></td>
          <td><?= htmlspecialchars($leave['leave_type'] === 'Pre Leave' ? 'P/L' : trim(str_ireplace('leave', '', $leave['leave_type'] ?? '-'))) ?></td>
          <td><?= $display($leave['total_days'] ?? null) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>

  <?php if ($socialLinks): ?>
  <div class="print-sec-social">
    <div class="bio-section-title avoid-break">9. Social Links</div>
    <table class="bio-table avoid-break">
      <?php foreach ($socialLinks as $link): 
          $displayUrl = $link['url'];
          if (strtolower($link['platform']) === 'whatsapp') {
              $displayUrl = preg_replace('/[^0-9+]/', '', $displayUrl);
              if (empty($displayUrl)) $displayUrl = $link['url'];
          } else {
              $displayUrl = preg_replace('#^https?://(www\.)?#i', '', $displayUrl);
              $displayUrl = rtrim($displayUrl, '/');
          }
      ?>
        <tr>
          <td class="lbl" style="width:20%;">
            <i class="<?= $icons[strtolower($link['platform'])] ?? 'ti ti-link' ?>"></i> 
            <?= htmlspecialchars(ucfirst($link['platform'])) ?>
          </td>
          <td><?= htmlspecialchars($displayUrl) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>

  <div class="print-signature" style="margin-top: 60px; text-align: right; padding-right: 30px; page-break-inside: avoid; break-inside: avoid;">
    <div style="display: inline-block; text-align: center;">
      <div style="border-top: 1px dashed #000; width: 220px; padding-top: 5px; font-weight: bold;">
        Signature
      </div>
    </div>
  </div>

</div>

<script>
function printBioData() {
  Swal.fire({
    title: 'Print Options',
    html: `
      <div style="text-align: left; font-size: 0.9rem;">
        <label class="form-label fw-bold">Page Size</label>
        <select id="printPageSize" class="form-select form-select-sm mb-3">
          <option value="A4">A4</option>
          <option value="Letter">Letter</option>
          <option value="Legal">Legal</option>
        </select>
        <label class="form-label fw-bold">General Options</label>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="ps_photo" checked>
          <label class="form-check-label" for="ps_photo">Include Photo</label>
        </div>
        <label class="form-label fw-bold">Sections to Print</label>
        <div class="form-check mb-1">
          <input class="form-check-input" type="checkbox" id="ps_contact" checked>
          <label class="form-check-label" for="ps_contact">2. Contact & Address</label>
        </div>
        <div class="form-check mb-1">
          <input class="form-check-input" type="checkbox" id="ps_family" checked>
          <label class="form-check-label" for="ps_family">3. Family Information</label>
        </div>
        <div class="form-check mb-1">
          <input class="form-check-input" type="checkbox" id="ps_health" checked>
          <label class="form-check-label" for="ps_health">4. Health Details</label>
        </div>
        <div class="form-check mb-1">
          <input class="form-check-input" type="checkbox" id="ps_training" checked>
          <label class="form-check-label" for="ps_training">5. Training & Qualifications</label>
        </div>
        <div class="form-check mb-1">
          <input class="form-check-input" type="checkbox" id="ps_perf" checked>
          <label class="form-check-label" for="ps_perf">6. Performance & Checks</label>
        </div>
        <div class="form-check mb-1">
          <input class="form-check-input" type="checkbox" id="ps_special" checked>
          <label class="form-check-label" for="ps_special">7. Special Note</label>
        </div>
        <?php if ($leaves && count($leaves) > 0): ?>
        <div class="form-check mb-1">
          <input class="form-check-input" type="checkbox" id="ps_leave" checked>
          <label class="form-check-label" for="ps_leave">8. Leave Records</label>
        </div>
        <?php endif; ?>
        <?php if ($socialLinks): ?>
        <div class="form-check mb-1">
          <input class="form-check-input" type="checkbox" id="ps_social" checked>
          <label class="form-check-label" for="ps_social">9. Social Links</label>
        </div>
        <?php endif; ?>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: '<i class="ti ti-printer"></i> Print',
    confirmButtonColor: '#0d6efd',
    focusConfirm: false,
    preConfirm: () => {
      return {
        pageSize: document.getElementById('printPageSize').value,
        photo: document.getElementById('ps_photo').checked,
        contact: document.getElementById('ps_contact').checked,
        family: document.getElementById('ps_family').checked,
        health: document.getElementById('ps_health').checked,
        training: document.getElementById('ps_training').checked,
        perf: document.getElementById('ps_perf').checked,
        special: document.getElementById('ps_special').checked,
        leave: document.getElementById('ps_leave') ? document.getElementById('ps_leave').checked : false,
        social: document.getElementById('ps_social') ? document.getElementById('ps_social').checked : false
      }
    }
  }).then((result) => {
    if (result.isConfirmed) {
      setTimeout(() => {
        executePrint(result.value);
      }, 500);
    }
  });
}

function executePrint(options) {
  let styleTag = document.getElementById('dynamic-print-styles');
  if (!styleTag) {
    styleTag = document.createElement('style');
    styleTag.id = 'dynamic-print-styles';
    document.body.appendChild(styleTag);
  }
  
  let css = '@page { size: ' + options.pageSize + '; margin: 12mm; }';
  if (!options.photo) css += ' @media print { .bio-photo-box img { display: none !important; } }';
  if (!options.contact) css += ' @media print { .print-sec-contact { display: none !important; } }';
  if (!options.family) css += ' @media print { .print-sec-family { display: none !important; } }';
  if (!options.health) css += ' @media print { .print-sec-health { display: none !important; } }';
  if (!options.training) css += ' @media print { .print-sec-training { display: none !important; } }';
  if (!options.perf) css += ' @media print { .print-sec-perf { display: none !important; } }';
  if (!options.special) css += ' @media print { .print-sec-special { display: none !important; } }';
  if (!options.leave) css += ' @media print { .print-sec-leave { display: none !important; } }';
  if (!options.social) css += ' @media print { .print-sec-social { display: none !important; } }';
  
  styleTag.innerHTML = css;
  
  var originalTitle = document.title;
  document.title = "<?= addslashes(htmlspecialchars($person['personal_number'] . ' ' . ($person['rank_name'] ?? '') . ' ' . $person['name'])) ?>";
  
  window.print();
  
  setTimeout(function() {
    document.title = originalTitle;
  }, 1000);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
