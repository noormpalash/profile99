<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Personnel.php';
require_once __DIR__ . '/../config/db.php';
Auth::requireLogin();

$annual = trim($_GET['annual'] ?? 'ALL');
$status = strtoupper(trim($_GET['status'] ?? 'ALL'));
$category = trim($_GET['category'] ?? 'ALL');

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

$ipftCategories = ['OFFR', 'SWO', 'WO', 'SGT', 'CPL', 'LCPL', 'SNK'];

if ($category === 'ALL') {
    $ranks = [];
    foreach ($ipftCategories as $cat) {
        $ranks = array_merge($ranks, $rankMap[$cat]);
    }
} else {
    if (!in_array($category, $ipftCategories, true)) {
        echo '<div class="alert alert-info py-3"><i class="ti ti-info-circle me-1"></i> ' . htmlspecialchars($category) . ' personnel are non-combatant support trades and exempt from IPFT.</div>';
        exit;
    }
    $ranks = $rankMap[$category];
}

$db = getDB();
$placeholders = implode(',', array_fill(0, count($ranks), '?'));

$sql = "
SELECT 
    p.id, p.personal_number, p.name, p.photo_path,
    r.name AS rank_name,
    u.name AS unit_name,
    pl.name AS platoon_name,
    COALESCE(ps.ipft_1st, '') AS ipft_1st,
    COALESCE(ps.ipft_2nd, '') AS ipft_2nd
FROM personnel p
JOIN ranks r ON p.rank_id = r.id
LEFT JOIN units u ON p.unit_id = u.id
LEFT JOIN platoons pl ON p.platoon_id = pl.id
LEFT JOIN personnel_service ps ON p.id = ps.personnel_id
WHERE p.status = 'active' AND r.name IN ($placeholders)
";

$params = $ranks;

if ($status !== 'ALL') {
    if ($annual === '1st') {
        if ($status === 'PENDING') {
            $sql .= " AND (ps.ipft_1st IS NULL OR TRIM(ps.ipft_1st) = '')";
        } elseif ($status === 'NA') {
            $sql .= " AND UPPER(TRIM(ps.ipft_1st)) IN ('NOT ATTENDING', 'NOT APPLICABLE')";
        } else {
            $sql .= " AND UPPER(TRIM(ps.ipft_1st)) = ?";
            $params[] = $status;
        }
    } elseif ($annual === '2nd') {
        if ($status === 'PENDING') {
            $sql .= " AND (ps.ipft_2nd IS NULL OR TRIM(ps.ipft_2nd) = '')";
        } elseif ($status === 'NA') {
            $sql .= " AND UPPER(TRIM(ps.ipft_2nd)) IN ('NOT ATTENDING', 'NOT APPLICABLE')";
        } else {
            $sql .= " AND UPPER(TRIM(ps.ipft_2nd)) = ?";
            $params[] = $status;
        }
    } else {
        if ($status === 'PENDING') {
            $sql .= " AND ((ps.ipft_1st IS NULL OR TRIM(ps.ipft_1st) = '') OR (ps.ipft_2nd IS NULL OR TRIM(ps.ipft_2nd) = ''))";
        } elseif ($status === 'NA') {
            $sql .= " AND (UPPER(TRIM(ps.ipft_1st)) IN ('NOT ATTENDING', 'NOT APPLICABLE') OR UPPER(TRIM(ps.ipft_2nd)) IN ('NOT ATTENDING', 'NOT APPLICABLE'))";
        } else {
            $sql .= " AND (UPPER(TRIM(ps.ipft_1st)) = ? OR UPPER(TRIM(ps.ipft_2nd)) = ?)";
            $params[] = $status;
            $params[] = $status;
        }
    }
}

$sql .= " ORDER BY r.id ASC, p.personal_number ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    echo '<div class="text-center py-4 text-muted"><i class="ti ti-users-off fs-2 d-block mb-2 opacity-50"></i>No personnel found for this selection.</div>';
    exit;
}
?>

<div class="table-responsive">
  <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
    <thead class="table-light">
      <tr>
        <th style="width: 50px;">Photo</th>
        <th>Personal No</th>
        <th>Rank</th>
        <th>Name</th>
        <th>Unit / Platoon</th>
        <th class="text-center">1st Annual</th>
        <th class="text-center">2nd Annual</th>
        <th class="text-end" style="width: 70px;">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($results as $p): 
        $badgeClass1 = match(strtoupper(trim((string)$p['ipft_1st']))) {
            'PASS' => 'bg-success text-white',
            'FAIL' => 'bg-danger text-white',
            'NOT ATTENDING', 'NOT APPLICABLE' => 'bg-warning text-dark',
            default => 'bg-secondary text-white opacity-75'
        };
        $label1 = !empty($p['ipft_1st']) ? htmlspecialchars($p['ipft_1st']) : 'PENDING';

        $badgeClass2 = match(strtoupper(trim((string)$p['ipft_2nd']))) {
            'PASS' => 'bg-success text-white',
            'FAIL' => 'bg-danger text-white',
            'NOT ATTENDING', 'NOT APPLICABLE' => 'bg-warning text-dark',
            default => 'bg-secondary text-white opacity-75'
        };
        $label2 = !empty($p['ipft_2nd']) ? htmlspecialchars($p['ipft_2nd']) : 'PENDING';
      ?>
      <tr>
        <td>
          <img src="<?= htmlspecialchars(personnelPhotoUrl($p['photo_path'] ?? null)) ?>" class="person-avatar-sm" alt="Photo" style="width: 34px; height: 34px; object-fit: cover; border-radius: 50%; border: 1px solid #e2e8f0;">
        </td>
        <td class="fw-bold"><?= htmlspecialchars($p['personal_number']) ?></td>
        <td><?= htmlspecialchars($p['rank_name'] ?? '-') ?></td>
        <td class="fw-semibold">
          <a href="profile.php?id=<?= $p['id'] ?>" target="_blank" class="text-decoration-none text-dark">
            <?= htmlspecialchars($p['name']) ?>
          </a>
        </td>
        <td>
          <span class="text-muted small"><?= htmlspecialchars($p['unit_name'] ?? '-') ?> / <?= htmlspecialchars($p['platoon_name'] ?? '-') ?></span>
        </td>
        <td class="text-center">
          <span class="badge <?= $badgeClass1 ?>" style="font-size: 0.72rem; padding: 4px 8px;"><?= $label1 ?></span>
        </td>
        <td class="text-center">
          <span class="badge <?= $badgeClass2 ?>" style="font-size: 0.72rem; padding: 4px 8px;"><?= $label2 ?></span>
        </td>
        <td class="text-end">
          <a href="profile.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2" title="View Profile">
            <i class="ti ti-arrow-right"></i>
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
