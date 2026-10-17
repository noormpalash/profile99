<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/AppSettings.php';
require_once __DIR__ . '/../config/db.php';
Auth::requireLogin();

$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';

if (!$status || !$category) {
    echo '<div class="alert alert-warning">Missing parameters.</div>';
    exit;
}

$customAbsentStatuses = AppSettings::getCustomAbsentStatuses();

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

if ($category === 'ALL') {
    $ranks = [];
    foreach ($rankMap as $catRanks) {
        $ranks = array_merge($ranks, $catRanks);
    }
} else {
    if (!isset($rankMap[$category])) {
        echo '<div class="alert alert-danger">Invalid category.</div>';
        exit;
    }
    $ranks = $rankMap[$category];
}

$placeholders = implode(',', array_fill(0, count($ranks), '?'));

$db = getDB();

$leaveMap = [
  'Pre Leave' => 'Pre Leave',
  'C/Leave' => 'Casual Leave',
  'W/Leave' => 'Weekend Leave',
  'R/Leave' => 'Recreation Leave',
  'M/Leave' => 'Medical Leave',
  'Medical Leave' => 'Medical Leave',
];

$statusMap = [
  'CMH' => 'cmh',
  'TRG' => 'trg',
  'CMD' => 'cmd',
  'ATT' => 'att',
  'GOC GD' => 'goc_gd',
  'Suspend' => 'suspend',
  'OSL' => 'osl',
  'AWOL' => 'awol',
  'Others' => 'others',
  'others' => 'others',
];
foreach ($customAbsentStatuses as $cKey => $cLabel) {
  $statusMap[$cLabel] = $cKey;
  $statusMap[$cKey] = $cKey;
}

$baseAbsentKeys = ['cmh', 'trg', 'cmd', 'att', 'goc_gd', 'suspend', 'osl', 'awol', 'others'];
$allAbsentStatusKeys = array_unique(array_merge($baseAbsentKeys, array_keys($customAbsentStatuses)));
$allAbsentPlaceholders = "'" . implode("','", array_map('addslashes', $allAbsentStatusKeys)) . "'";

$results = [];

if ($status === 'ALL' || isset($leaveMap[$status])) {
    $sql = "SELECT p.id, p.name, p.personal_number, p.photo_path, r.name as rank_name, pl.from_date, pl.to_date
            FROM personnel p 
            JOIN ranks r ON p.rank_id = r.id 
            JOIN personnel_leaves pl ON p.id = pl.personnel_id 
            WHERE CURRENT_DATE >= pl.from_date AND CURRENT_DATE <= pl.to_date ";
    
    if ($status !== 'ALL') {
        $sql .= " AND pl.leave_type = ? AND r.name IN ($placeholders) ORDER BY p.name ASC";
        $params = array_merge([$leaveMap[$status]], $ranks);
    } else {
        $sql .= " AND r.name IN ($placeholders) ORDER BY p.name ASC";
        $params = $ranks;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = array_merge($results, $stmt->fetchAll());
}

if ($status === 'ALL' || isset($statusMap[$status])) {
    $sql = "SELECT p.id, p.name, p.personal_number, p.photo_path, r.name as rank_name, NULL as from_date, NULL as to_date
            FROM personnel p 
            JOIN ranks r ON p.rank_id = r.id 
            WHERE r.name IN ($placeholders)";
            
    if ($status !== 'ALL') {
        $sql .= " AND p.status = ? ORDER BY p.name ASC";
        $params = array_merge($ranks, [$statusMap[$status]]);
    } else {
        $sql .= " AND p.status IN ($allAbsentPlaceholders) ORDER BY p.name ASC";
        $params = $ranks;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = array_merge($results, $stmt->fetchAll());
}

if (empty($results) && $status !== 'ALL' && !isset($leaveMap[$status]) && !isset($statusMap[$status])) {
    echo '<div class="alert alert-warning">Unknown status mapping.</div>';
    exit;
}

// Sort merged results by name
usort($results, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

if (empty($results)) {
    echo '<div class="alert alert-info">No personnel found.</div>';
} else {
    echo '<ul class="list-group text-start">';
    foreach ($results as $row) {
        $link = 'profile.php?id=' . $row['id'];
        $photoSrc = htmlspecialchars(personnelPhotoUrl($row['photo_path'] ?? null));
        
        $leaveText = '';
        if (!empty($row['from_date']) && !empty($row['to_date'])) {
            $from = new DateTime($row['from_date']);
            $to = new DateTime($row['to_date']);
            $diff = $from->diff($to)->days + 1;
            $leaveText = '<span class="badge bg-light text-dark border ms-3 fw-normal" style="font-size: 0.85em;">' . $from->format('d M y') . ' - ' . $to->format('d M y') . ' = ' . $diff . ' Days</span>';
        }

        echo '<li class="list-group-item d-flex align-items-center py-2">';
        echo '<img src="' . $photoSrc . '" class="rounded-circle me-3 flex-shrink-0" width="36" height="36" style="object-fit:cover; border: 1px solid #e2e8f0;">';
        echo '<span class="text-muted me-2" style="width:120px;">' . htmlspecialchars($row['rank_name']) . '</span>';
        echo '<a href="' . htmlspecialchars($link) . '" class="text-decoration-none fw-bold text-dark text-truncate me-auto" target="_blank">' . htmlspecialchars($row['name']) . '</a>';
        echo $leaveText;
        echo '</li>';
    }
    echo '</ul>';
}
?>
