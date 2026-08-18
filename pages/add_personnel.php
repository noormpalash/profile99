<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Personnel.php';
require_once __DIR__ . '/../classes/LookupManager.php';
Auth::requirePermission('add_personnel');
$withSidebar = true;

$error = '';
$old = $_POST;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf($_POST['csrf_token'] ?? null);
    try {
        if (Auth::hasPermission('approval') || Auth::hasPermission('auto_approval') || in_array(Auth::role(), ['admin', 'superadmin', 'techadmin'])) {
            require_once __DIR__ . '/../classes/ApprovalFormatter.php';
            $diffText = ApprovalFormatter::renderDiffText('add', 0, json_encode($_POST));
            $newId = Personnel::create($_POST, $_FILES['photo'] ?? []);
            require_once __DIR__ . '/../classes/Logger.php';
            Logger::log('add', $newId, ['details' => $diffText]);
            header('Location: dashboard.php');
            exit;
        } else {
            $photoPath = null;
            if (!empty($_FILES['photo']) && isset($_FILES['photo']['error']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                try {
                    $photoPath = Personnel::handlePhotoUpload($_FILES['photo'], $_POST['personal_number']);
                } catch (Exception $e) {
                    if ($e->getMessage() !== 'NO_PHOTO_UPLOADED') throw $e;
                }
            }
            $data = $_POST;
            if ($photoPath) $data['_pending_photo_path'] = $photoPath;
            
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO personnel_approvals (action_type, proposed_data, requested_by) VALUES ('add', ?, ?)");
            $stmt->execute([json_encode($data), $_SESSION['user_id']]);
            
            require_once __DIR__ . '/../classes/Logger.php';
            require_once __DIR__ . '/../classes/ApprovalFormatter.php';
            $diffText = ApprovalFormatter::renderDiffText('add', 0, json_encode($data));
            Logger::log('add', null, ['details' => "Requested approval for addition: " . $diffText]);
            
            $success = "Your request to add personnel has been submitted for approval.";
        }
    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        $error = "Database error occurred. Check unique constraints (e.g., Personal No).";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$ranks = LookupManager::getAll('ranks');
$units = LookupManager::getAll('units');
$cadres = LookupManager::getAll('cadres');
$platoons = LookupManager::getAll('platoons');
$courses = LookupManager::getAll('courses');
$bloodGroups = LookupManager::getAll('blood_groups');
$medicalCategories = LookupManager::getAll('medical_categories');
$moqs = LookupManager::getAll('moqs');
$districts = bangladeshDistricts();
$appointments = LookupManager::getAll('appointments');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="row">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10">
    <div class="page-header mb-3">
      <div>
        <h4>Add Personnel</h4>
      </div>

      <div class="row mb-3">
        <div class="col-md-4">
          <label class="form-label small">Appointment</label>
          <select name="appointment_id" class="form-select">
            <option value="">-- select --</option>
            <?php foreach ($appointments as $a): ?><option value="<?= $a['id'] ?>" <?= isset($old['appointment_id']) && $old['appointment_id']==$a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

      <?php if ($error): ?>
        <?php if (strpos($error, 'alert-success') !== false): ?>
            <?= $error ?>
        <?php else: ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
      <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="card shadow-sm p-4">
      <?= Auth::csrfField() ?>
      <div class="row mb-4">
        <div class="col-md-3">
          <label class="form-label small">Photo</label>
          <input type="file" name="photo" accept="image/jpeg,image/png" class="form-control">
          <div class="form-text">JPG or PNG, max 2MB. Optional.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label small">Full name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label small">Personal number <span class="text-danger">*</span></label>
          <input type="text" name="personal_number" class="form-control" value="<?= htmlspecialchars($old['personal_number'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label small">NID</label>
          <input type="text" name="nid" class="form-control" value="<?= htmlspecialchars($old['nid'] ?? '') ?>" pattern="^(\d{10}|\d{13}|\d{17})$" title="NID must be 10, 13, or 17 digits">
        </div>
      </div>

      <h6 class="mb-3">Basic Information</h6>
      <div class="row mb-3">
        <div class="col-md-3">
          <label class="form-label small">Rank</label>
          <select id="rank_id_add" name="rank_id" class="form-select">
            <option value="">-- select --</option>
            <?php foreach ($ranks as $r): ?><option value="<?= $r['id'] ?>" <?= isset($old['rank_id']) && $old['rank_id']==$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">Unit</label>
          <select name="unit_id" class="form-select">
            <option value="">-- select --</option>
            <?php foreach ($units as $u): ?><option value="<?= $u['id'] ?>" <?= isset($old['unit_id']) && $old['unit_id']==$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">Platoon</label>
          <select name="platoon_id" class="form-select">
            <option value="">-- select --</option>
            <?php foreach ($platoons as $p): ?><option value="<?= $p['id'] ?>" <?= isset($old['platoon_id']) && $old['platoon_id']==$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">Blood group</label>
          <select id="blood_group_id_add" name="blood_group_id" class="form-select">
            <option value="">-- select --</option>
            <?php foreach ($bloodGroups as $b): ?><option value="<?= $b['id'] ?>" <?= isset($old['blood_group_id']) && $old['blood_group_id']==$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-4">
          <label class="form-label small">Mobile number</label>
          <input type="text" name="mobile_number" class="form-control" value="<?= htmlspecialchars($old['mobile_number'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small">District</label>
          <input type="text" name="address" class="form-control" list="districts_add" value="<?= htmlspecialchars($old['address'] ?? '') ?>" placeholder="Search district">
          <datalist id="districts_add">
            <?php foreach ($districts as $district): ?>
              <option value="<?= htmlspecialchars($district) ?>"></option>
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="col-md-4">
          <label class="form-label small">Status</label>
          <select name="status" class="form-select">
            <?php 
              $statusOptions = [
                'active' => 'Active',
                'on_leave' => 'On Leave',
                'cmh' => 'CMH',
                'trg' => 'TRG',
                'cmd' => 'CMD',
                'att' => 'ATT',
                'goc_gd' => 'GOC GD',
                'suspend' => 'Suspend',
                'osl' => 'OSL',
                'awol' => 'AWOL'
              ];
              foreach ($statusOptions as $val => $label): 
            ?>
              <option value="<?= $val ?>" <?= (isset($old['status']) ? $old['status'] : 'active') === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-4">
          <label class="form-label small">Batch</label>
          <input type="text" name="batch" class="form-control" value="<?= htmlspecialchars($old['batch'] ?? '') ?>">
        </div>
        <div class="col-md-8">
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label small">Vill</label>
              <input type="text" name="vill" class="form-control" value="<?= htmlspecialchars($old['vill'] ?? '') ?>" placeholder="Village">
            </div>
            <div class="col-md-4">
              <label class="form-label small">PO</label>
              <input type="text" name="po" class="form-control" value="<?= htmlspecialchars($old['po'] ?? '') ?>" placeholder="Post Office">
            </div>
            <div class="col-md-4">
              <label class="form-label small">PS</label>
              <input type="text" name="ps" class="form-control" value="<?= htmlspecialchars($old['ps'] ?? '') ?>" placeholder="Police Station">
            </div>
          </div>
        </div>
      </div>

      <h6 class="mb-3">Courses & Training</h6>
      <div class="row mb-3">
        <div class="col-md-4">
          <label class="form-label small fw-bold">Cadres (multiple)</label>
          <div id="cadre_ids_add" class="border rounded p-2 bg-white personnel-option-list">
            <?php foreach ($cadres as $c): ?>
              <?php $checked = isset($old['cadre_ids']) && in_array($c['id'], (array)$old['cadre_ids']) ? 'checked' : ''; ?>
              <div class="form-check py-1">
                <input class="form-check-input" type="checkbox" name="cadre_ids[]" value="<?= $c['id'] ?>" id="cadre_add_<?= $c['id'] ?>" <?= $checked ?>>
                <label class="form-check-label ms-1" for="cadre_add_<?= $c['id'] ?>">
                  <?= htmlspecialchars($c['name']) ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label small fw-bold">Courses (select & enter result)</label>
          <?php 
            $oldCourses = (array)($old['course_id'] ?? []);
            $oldResults = (array)($old['course_result'] ?? []);
            $oldCourseMap = [];
            foreach ($oldCourses as $idx => $cid) {
                if ($cid) $oldCourseMap[$cid] = $oldResults[$idx] ?? ($oldResults[$cid] ?? '');
            }
          ?>
          <div id="courses_add_list" class="border rounded p-2 bg-white personnel-option-list">
            <?php foreach ($courses as $courseItem): ?>
              <?php 
                $cid = $courseItem['id'];
                $isAdded = isset($oldCourseMap[$cid]);
                $resultVal = $isAdded ? $oldCourseMap[$cid] : '';
              ?>
              <div class="course-item border-bottom py-2 px-1">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="form-check mb-0">
                    <input class="form-check-input course-checkbox" type="checkbox" id="course_chk_add_<?= $cid ?>" data-course-id="<?= $cid ?>" <?= $isAdded ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold small ms-1" for="course_chk_add_<?= $cid ?>">
                      <?= htmlspecialchars($courseItem['name']) ?>
                    </label>
                  </div>
                  <button type="button" class="btn btn-sm <?= $isAdded ? 'btn-success' : 'btn-outline-primary' ?> py-0 px-2 course-add-btn" data-course-id="<?= $cid ?>">
                    <?= $isAdded ? '✓ Added' : '+ Add' ?>
                  </button>
                </div>
                <div class="course-result-container <?= $isAdded ? '' : 'd-none' ?> mt-2 ms-4" id="result_box_add_<?= $cid ?>">
                  <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted small">Result</span>
                    <input type="hidden" name="course_id[]" value="<?= $cid ?>" class="course-id-input" <?= $isAdded ? '' : 'disabled' ?>>
                    <input type="text" name="course_result[<?= $cid ?>]" class="form-control course-result-input" placeholder="e.g. Passed, Grade A" value="<?= htmlspecialchars($resultVal) ?>" <?= $isAdded ? '' : 'disabled' ?>>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label small fw-bold">MOQ (select & enter result)</label>
          <?php 
            $oldMoqs = (array)($old['moq_id'] ?? []);
            $oldMoqResults = (array)($old['moq_result'] ?? []);
            $oldMoqMap = [];
            foreach ($oldMoqs as $idx => $mid) {
                if ($mid) $oldMoqMap[$mid] = $oldMoqResults[$idx] ?? ($oldMoqResults[$mid] ?? '');
            }
          ?>
          <div id="moqs_add_list" class="border rounded p-2 bg-white personnel-option-list">
            <?php foreach ($moqs as $moqItem): ?>
              <?php 
                $mid = $moqItem['id'];
                $isAdded = isset($oldMoqMap[$mid]);
                $resultVal = $isAdded ? $oldMoqMap[$mid] : '';
              ?>
              <div class="moq-item border-bottom py-2 px-1">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="form-check mb-0">
                    <input class="form-check-input moq-checkbox" type="checkbox" id="moq_chk_add_<?= $mid ?>" data-moq-id="<?= $mid ?>" <?= $isAdded ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold small ms-1" for="moq_chk_add_<?= $mid ?>">
                      <?= htmlspecialchars($moqItem['name']) ?>
                    </label>
                  </div>
                  <button type="button" class="btn btn-sm <?= $isAdded ? 'btn-success' : 'btn-outline-primary' ?> py-0 px-2 moq-add-btn" data-moq-id="<?= $mid ?>">
                    <?= $isAdded ? '✓ Added' : '+ Add' ?>
                  </button>
                </div>
                <div class="moq-result-container <?= $isAdded ? '' : 'd-none' ?> mt-2 ms-4" id="result_box_moq_add_<?= $mid ?>">
                  <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted small">Result</span>
                    <input type="hidden" name="moq_id[]" value="<?= $mid ?>" class="moq-id-input" <?= $isAdded ? '' : 'disabled' ?>>
                    <input type="text" name="moq_result[<?= $mid ?>]" class="form-control moq-result-input" placeholder="e.g. Passed, Grade A" value="<?= htmlspecialchars($resultVal) ?>" <?= $isAdded ? '' : 'disabled' ?>>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <h6 class="mb-3">Service Details</h6>
      <div class="row mb-3">
        <div class="col-md-3">
          <label class="form-label small">Admission date</label>
          <input type="date" name="admission_date" class="form-control" value="<?= htmlspecialchars($old['admission_date'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small">Retirement date</label>
          <input type="date" name="retirement_date" class="form-control" value="<?= htmlspecialchars($old['retirement_date'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small">UN Mission</label>
          <input type="text" name="un_mission" class="form-control" placeholder="e.g. MINUSCA, MONUSCO" value="<?= htmlspecialchars($old['un_mission'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small">Punishment note</label>
          <input type="text" name="punishment_note" class="form-control" value="<?= htmlspecialchars($old['punishment_note'] ?? '') ?>">
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-3">
          <label class="form-label small">IPFT (1st Annual)</label>
          <select name="ipft_1st" class="form-select">
            <option value="">Select...</option>
            <option value="PASS" <?= ($old['ipft_1st'] ?? '') === 'PASS' ? 'selected' : '' ?>>PASS</option>
            <option value="FAIL" <?= ($old['ipft_1st'] ?? '') === 'FAIL' ? 'selected' : '' ?>>FAIL</option>
            <option value="NOT ATTENDING" <?= ($old['ipft_1st'] ?? '') === 'NOT ATTENDING' ? 'selected' : '' ?>>NOT ATTENDING</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">IPFT (2nd Annual)</label>
          <select name="ipft_2nd" class="form-select">
            <option value="">Select...</option>
            <option value="PASS" <?= ($old['ipft_2nd'] ?? '') === 'PASS' ? 'selected' : '' ?>>PASS</option>
            <option value="FAIL" <?= ($old['ipft_2nd'] ?? '') === 'FAIL' ? 'selected' : '' ?>>FAIL</option>
            <option value="NOT ATTENDING" <?= ($old['ipft_2nd'] ?? '') === 'NOT ATTENDING' ? 'selected' : '' ?>>NOT ATTENDING</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">RET (Result)</label>
          <input type="text" name="ret" class="form-control" value="<?= htmlspecialchars($old['ret'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small">Speed March (Result)</label>
          <input type="text" name="speed_march" class="form-control" value="<?= htmlspecialchars($old['speed_march'] ?? '') ?>">
        </div>
      </div>

      <h6 class="mb-3 mt-4">Yearly Plan</h6>
      <div class="row mb-3">
        <?php foreach (['1st', '2nd', '3rd', '4th'] as $idx => $cycle): 
          $fieldName = "cycle_" . ($idx + 1);
        ?>
        <div class="col-md-3">
          <label class="form-label small"><?= $cycle ?> Cycle</label>
          <select name="<?= $fieldName ?>" class="form-select">
            <option value="">Select...</option>
            <option value="Training" <?= ($old[$fieldName] ?? '') === 'Training' ? 'selected' : '' ?>>Training</option>
            <option value="Administration" <?= ($old[$fieldName] ?? '') === 'Administration' ? 'selected' : '' ?>>Administration</option>
            <option value="Pre Leave" <?= ($old[$fieldName] ?? '') === 'Pre Leave' ? 'selected' : '' ?>>Pre Leave</option>
            <option value="Group Training" <?= ($old[$fieldName] ?? '') === 'Group Training' ? 'selected' : '' ?>>Group Training</option>
          </select>
        </div>
        <?php endforeach; ?>
      </div>

      <h6 class="mb-3 mt-4">Leave Status</h6>
      <div id="leaves_container" class="px-2" style="max-height: 190px; overflow-y: auto; overflow-x: hidden;">
        <div class="row g-2 mb-2 leave-row">
          <div class="col-md-3">
            <label class="form-label small">From</label>
            <input type="date" name="leaves[from_date][]" class="form-control form-control-sm">
          </div>
          <div class="col-md-2">
            <label class="form-label small">To</label>
            <input type="date" name="leaves[to_date][]" class="form-control form-control-sm">
          </div>
          <div class="col-md-2">
            <label class="form-label small">Total Days</label>
            <input type="number" name="leaves[total_days][]" class="form-control form-control-sm" readonly>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Leave Type</label>
            <select name="leaves[leave_type][]" class="form-select form-select-sm">
              <option value="">-- select --</option>
              <option value="Weekend Leave">Weekend Leave</option>
              <option value="Casual Leave">Casual Leave</option>
              <option value="Pre Leave">Pre Leave</option>
              <option value="Medical Leave">Medical Leave</option>
              <option value="Recreation Leave">Recreation Leave</option>
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.leave-row').remove()"><i class="ti ti-trash me-1"></i> Remove</button>
          </div>
        </div>
      </div>
      <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addLeaveRow()">+ Add Leave</button>

      <h6 class="mb-3 mt-4">Personal & Family</h6>
      <div class="row mb-3">
        <div class="col-md-3">
          <label class="form-label small">Birthdate</label>
          <input type="date" name="birthdate" class="form-control" value="<?= htmlspecialchars($old['birthdate'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small">Marriage date</label>
          <input type="date" name="marriage_date" class="form-control" value="<?= htmlspecialchars($old['marriage_date'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small">Marital status</label>
          <select name="marital_status" id="marital_status_select" class="form-select">
            <option value="">-- select --</option>
            <?php foreach (['single','married','widowed','divorced'] as $status): ?>
              <option value="<?= $status ?>" <?= isset($old['marital_status']) && $old['marital_status']===$status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2" id="children_count_container" style="display: <?= isset($old['marital_status']) && $old['marital_status'] !== 'single' && $old['marital_status'] !== '' ? 'block' : 'none' ?>;">
          <label class="form-label small">Children count</label>
          <input type="number" min="0" name="children_count" class="form-control" value="<?= htmlspecialchars($old['children_count'] ?? '') ?>">
        </div>
        <div class="col-md-2" id="family_member_container" style="display: <?= isset($old['marital_status']) && $old['marital_status'] !== 'single' && $old['marital_status'] !== '' ? 'block' : 'none' ?>;">
          <label class="form-label small">Family Member</label>
          <select name="family_member" id="family_member_select" class="form-select">
            <option value="No" <?= isset($old['family_member']) && $old['family_member']==='No' ? 'selected' : '' ?>>No</option>
            <option value="Yes" <?= isset($old['family_member']) && $old['family_member']==='Yes' ? 'selected' : '' ?>>Yes</option>
          </select>
        </div>
        <div class="col-md-2" id="fm_date_from_container" style="display: <?= isset($old['family_member']) && $old['family_member']==='Yes' ? 'block' : 'none' ?>;">
          <label class="form-label">From Date</label>
          <input type="date" name="fm_date_from" class="form-control" value="<?= htmlspecialchars($old['fm_date_from'] ?? '') ?>">
        </div>
        <div class="col-md-2" id="fm_date_to_container" style="display: <?= isset($old['family_member']) && $old['family_member']==='Yes' ? 'block' : 'none' ?>;">
          <label class="form-label">To Date</label>
          <input type="date" name="fm_date_to" class="form-control" value="<?= htmlspecialchars($old['fm_date_to'] ?? '') ?>">
        </div>
        <div class="col-md-12 mt-2" id="fm_current_address_container" style="display: <?= isset($old['family_member']) && $old['family_member']==='Yes' ? 'block' : 'none' ?>;">
          <label class="form-label small">Currently Living Address</label>
          <input type="text" name="fm_current_address" class="form-control" value="<?= htmlspecialchars($old['fm_current_address'] ?? '') ?>">
        </div>
        <div class="col-md-2 mt-2" id="living_status_container" style="display: <?= isset($old['family_member']) && $old['family_member']==='Yes' ? 'block' : 'none' ?>;">
          <label class="form-label small">Living Status</label>
          <select name="living_status" class="form-select">
            <option value="">-- select --</option>
            <option value="In Living" <?= isset($old['living_status']) && $old['living_status']==='In Living' ? 'selected' : '' ?>>In Living</option>
            <option value="Out Living" <?= isset($old['living_status']) && $old['living_status']==='Out Living' ? 'selected' : '' ?>>Out Living</option>
          </select>
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-4">
          <label class="form-label small">Father name</label>
          <input type="text" name="father_name" class="form-control" value="<?= htmlspecialchars($old['father_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small">Father mobile</label>
          <input type="text" name="father_mobile" class="form-control" value="<?= htmlspecialchars($old['father_mobile'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small">Mother name</label>
          <input type="text" name="mother_name" class="form-control" value="<?= htmlspecialchars($old['mother_name'] ?? '') ?>">
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-4">
          <label class="form-label small">Mother mobile</label>
          <input type="text" name="mother_mobile" class="form-control" value="<?= htmlspecialchars($old['mother_mobile'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small">Spouse name</label>
          <input type="text" name="spouse_name" class="form-control" value="<?= htmlspecialchars($old['spouse_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small">Spouse mobile</label>
          <input type="text" name="spouse_mobile" class="form-control" value="<?= htmlspecialchars($old['spouse_mobile'] ?? '') ?>">
        </div>
      </div>

      <h6 class="mb-3">Health Information</h6>
      <div class="row mb-3">
        <div class="col-md-4">
          <label class="form-label small">Medical category</label>
          <select name="medical_category_id" class="form-select">
            <option value="">-- select --</option>
            <?php foreach ($medicalCategories as $cat): ?><option value="<?= $cat['id'] ?>" <?= isset($old['medical_category_id']) && $old['medical_category_id']==$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small">Height (cm)</label>
          <input type="number" step="0.1" name="height_cm" class="form-control" value="<?= htmlspecialchars($old['height_cm'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small">Weight (kg)</label>
          <input type="number" step="0.1" name="weight_kg" class="form-control" value="<?= htmlspecialchars($old['weight_kg'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small">Any disease</label>
          <input type="text" name="any_disease" class="form-control" value="<?= htmlspecialchars($old['any_disease'] ?? '') ?>">
        </div>
      </div>

      <h6 class="mb-3">Social media links</h6>
      <div class="row mb-3">
        <?php foreach (['facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'whatsapp' => 'WhatsApp', 'twitter' => 'Twitter'] as $key => $label): ?>
          <?php 
             $inputType = $key === 'whatsapp' ? 'tel' : 'url';
             $placeholder = $key === 'whatsapp' ? 'e.g. +8801XXXXXXXXX' : 'https://';
             $val = $old['social_links'][$key] ?? '';
             if ($key === 'whatsapp' && strpos($val, 'https://wa.me/') === 0) {
                 $val = substr($val, 14);
             }
          ?>
          <div class="col-md-3 mb-3">
            <label class="form-label small"><?= $label ?></label>
            <input type="<?= $inputType ?>" name="social_links[<?= $key ?>]" class="form-control" value="<?= htmlspecialchars($val) ?>" placeholder="<?= $placeholder ?>">
          </div>
        <?php endforeach; ?>
      </div>

      <h6 class="mb-3">Other Features</h6>
      <div class="mb-3">
        <label class="form-label small">Special note</label>
        <textarea name="special_note" class="form-control" rows="3"><?= htmlspecialchars($old['special_note'] ?? '') ?></textarea>
      </div>

      <div class="text-end">
        <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save personnel</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('#courses_add_list .course-item').forEach(function(item) {
    const chk = item.querySelector('.course-checkbox');
    const btn = item.querySelector('.course-add-btn');
    const resBox = item.querySelector('.course-result-container');
    const idInput = item.querySelector('.course-id-input');
    const resInput = item.querySelector('.course-result-input');

    if (!chk || !btn || !resBox) return;

    function toggleCourse(setActive) {
      chk.checked = setActive;
      if (setActive) {
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success');
        btn.textContent = '✓ Added';
        resBox.classList.remove('d-none');
        if (idInput) idInput.removeAttribute('disabled');
        if (resInput) {
          resInput.removeAttribute('disabled');
          resInput.focus();
        }
      } else {
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-primary');
        btn.textContent = '+ Add';
        resBox.classList.add('d-none');
        if (idInput) idInput.setAttribute('disabled', 'disabled');
        if (resInput) resInput.setAttribute('disabled', 'disabled');
      }
    }

    chk.addEventListener('change', function() {
      toggleCourse(this.checked);
    });

    btn.addEventListener('click', function() {
      toggleCourse(!chk.checked);
    });
  });

  document.querySelectorAll('#moqs_add_list .moq-item').forEach(function(item) {
    const chk = item.querySelector('.moq-checkbox');
    const btn = item.querySelector('.moq-add-btn');
    const resBox = item.querySelector('.moq-result-container');
    const idInput = item.querySelector('.moq-id-input');
    const resInput = item.querySelector('.moq-result-input');

    if (!chk || !btn || !resBox) return;

    function toggleMoq(setActive) {
      chk.checked = setActive;
      if (setActive) {
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success');
        btn.textContent = '✓ Added';
        resBox.classList.remove('d-none');
        if (idInput) idInput.removeAttribute('disabled');
        if (resInput) {
          resInput.removeAttribute('disabled');
          resInput.focus();
        }
      } else {
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-primary');
        btn.textContent = '+ Add';
        resBox.classList.add('d-none');
        if (idInput) idInput.setAttribute('disabled', 'disabled');
        if (resInput) resInput.setAttribute('disabled', 'disabled');
      }
    }

    chk.addEventListener('change', function() {
      toggleMoq(this.checked);
    });

    btn.addEventListener('click', function() {
      toggleMoq(!chk.checked);
    });
  });

  const familyMemberSelect = document.getElementById('family_member_select');
  const livingStatusContainer = document.getElementById('living_status_container');
  const fmDateFromContainer = document.getElementById('fm_date_from_container');
  const fmDateToContainer = document.getElementById('fm_date_to_container');
  const fmCurrentAddressContainer = document.getElementById('fm_current_address_container');
  if (familyMemberSelect && livingStatusContainer) {
    familyMemberSelect.addEventListener('change', function() {
      if(this.value === 'Yes') {
        livingStatusContainer.style.display = 'block';
        if (fmDateFromContainer) fmDateFromContainer.style.display = 'block';
        if (fmDateToContainer) fmDateToContainer.style.display = 'block';
        if (fmCurrentAddressContainer) fmCurrentAddressContainer.style.display = 'block';
      } else {
        livingStatusContainer.style.display = 'none';
        if (fmDateFromContainer) fmDateFromContainer.style.display = 'none';
        if (fmDateToContainer) fmDateToContainer.style.display = 'none';
        if (fmCurrentAddressContainer) fmCurrentAddressContainer.style.display = 'none';
        livingStatusContainer.querySelector('select').value = '';
      }
    });
  }

  const maritalStatusSelect = document.getElementById('marital_status_select');
  const childrenCountContainer = document.getElementById('children_count_container');
  const familyMemberContainer = document.getElementById('family_member_container');
  if (maritalStatusSelect && childrenCountContainer && familyMemberContainer) {
    maritalStatusSelect.addEventListener('change', function() {
      if (this.value !== 'single' && this.value !== '') {
        childrenCountContainer.style.display = 'block';
        familyMemberContainer.style.display = 'block';
      } else {
        childrenCountContainer.style.display = 'none';
        childrenCountContainer.querySelector('input').value = '';
        familyMemberContainer.style.display = 'none';
        familyMemberSelect.value = 'No';
        familyMemberSelect.dispatchEvent(new Event('change'));
      }
    });
  }
});
</script>
<script>
function addLeaveRow() {
  const html = `
    <div class="row g-2 mb-2 leave-row">
      <div class="col-md-3"><input type="date" name="leaves[from_date][]" class="form-control form-control-sm"></div>
      <div class="col-md-2"><input type="date" name="leaves[to_date][]" class="form-control form-control-sm"></div>
      <div class="col-md-2"><input type="number" name="leaves[total_days][]" class="form-control form-control-sm" readonly></div>
      <div class="col-md-3">
        <select name="leaves[leave_type][]" class="form-select form-select-sm">
          <option value="">-- select --</option>
          <option value="Weekend Leave">Weekend Leave</option>
          <option value="Casual Leave">Casual Leave</option>
          <option value="Pre Leave">Pre Leave</option>
          <option value="Medical Leave">Medical Leave</option>
          <option value="Recreation Leave">Recreation Leave</option>
        </select>
      </div>
      <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.leave-row').remove()"><i class="ti ti-trash me-1"></i> Remove</button></div>
    </div>
  `;
  document.getElementById('leaves_container').insertAdjacentHTML('beforeend', html);
}

document.getElementById('leaves_container').addEventListener('change', function(e) {
  if (e.target.matches('input[name="leaves[from_date][]"]') || e.target.matches('input[name="leaves[to_date][]"]')) {
    const row = e.target.closest('.leave-row');
    const fromInput = row.querySelector('input[name="leaves[from_date][]"]');
    const toInput = row.querySelector('input[name="leaves[to_date][]"]');
    const daysInput = row.querySelector('input[name="leaves[total_days][]"]');
    
    if (fromInput.value && toInput.value) {
      const from = new Date(fromInput.value);
      const to = new Date(toInput.value);
      const diffTime = to - from;
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
      
      if (diffDays > 0) {
        daysInput.value = diffDays;
      } else {
        daysInput.value = '';
      }
    }
  }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
