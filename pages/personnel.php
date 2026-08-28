<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Personnel.php';
require_once __DIR__ . '/../classes/LookupManager.php';
require_once __DIR__ . '/../classes/AppSettings.php';
require_once __DIR__ . '/../config/db.php';
Auth::requirePermission('view_personnel');
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
$showAdvanced = $hasFilters || isset($_GET['advanced']);
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
  .appointment-badge {
    display: inline-block;
    background: #eef2ff;
    color: #1d4ed8;
    padding: 4px 8px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 0.82rem;
    margin-left: 8px
  }

  .name-cell {
    display: flex;
    align-items: center;
    gap: 8px
  }
</style>

<div class="row">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="col-md-9 col-lg-10">
    <div class="page-header no-print">
      <div>
        <h4>Personnel</h4>
        <div class="text-muted">Manage records and use advanced filters to narrow the list.</div>
      </div>
      <div class="d-flex gap-2 flex-wrap align-items-center">
        <?php if (Auth::hasPermission('print_records')): ?>
          <button type="button" class="btn-print no-print" onclick="printWithInfo()"><i class="ti ti-printer"></i> Print</button>
        <?php endif; ?>
        <?php if (Auth::hasPermission('add_personnel')): ?>
          <a href="add_personnel.php" class="btn btn-primary btn-sm no-print">Add personnel</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="card shadow-sm mb-4 dashboard-filter-card no-print" style="border-radius: 16px; padding: 0;">
      <form method="get" class="mb-0">
        <!-- Main Search Bar -->
        <div class="p-3 d-flex align-items-center gap-2 flex-wrap bg-white" style="border-radius: 16px;">
          <div class="input-group dashboard-search-group flex-grow-1">
            <span class="input-group-text"><i class="ti ti-search"></i></span>
            <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($filters['q']) ?>"
              placeholder="Search by name or personal no..." autofocus>
          </div>
          <button class="btn btn-primary px-4 fw-medium" type="submit">Search</button>
          <button class="btn btn-light border text-secondary fw-medium px-3" type="button" data-bs-toggle="collapse"
            data-bs-target="#advancedFilters" aria-expanded="<?= $showAdvanced ? 'true' : 'false' ?>" aria-controls="advancedFilters">
            <i class="ti ti-adjustments-horizontal me-1"></i> <span>Advanced</span>
          </button>
          <?php if ($hasFilters): ?>
            <a href="personnel.php?advanced=1" class="btn btn-outline-danger px-3 fw-medium" title="Clear all filters">
              <i class="ti ti-x"></i> <span>Clear</span>
            </a>
          <?php endif; ?>
        </div>

        <!-- Advanced Filters Collapsible -->
        <div class="collapse <?= $showAdvanced ? 'show' : '' ?>" id="advancedFilters">
          <div class="advanced-filters-collapse p-4 border-top"
            style="background: #f8fafc; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
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
                  <?php
                  $customAbsentStatuses = AppSettings::getCustomAbsentStatuses();
                  $statusOptions = [
                    'active' => 'Active',
                    'on_leave' => 'On Leave (Any)',
                    'leave_casual' => 'On Leave (Casual)',
                    'leave_pre' => 'On Leave (Pre Leave)',
                    'leave_weekend' => 'On Leave (Weekend)',
                    'leave_medical' => 'On Leave (Medical)',
                    'leave_recreation' => 'On Leave (Recreation)',
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
                    $statusOptions[$cKey] = $cLabel;
                  }
                  foreach ($statusOptions as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>>
                      <?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small text-secondary fw-medium">District</label>
                <input type="text" name="district" class="form-control" list="dashboardDistricts"
                  value="<?= htmlspecialchars($filters['district']) ?>" placeholder="Search district">
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
                    <option value="<?= $moq['id'] ?>" <?= $filters['moq_id'] !== '' && (int) $filters['moq_id'] === (int) $moq['id'] ? 'selected' : '' ?>><?= htmlspecialchars($moq['name']) ?>
                    </option>
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
              <a href="personnel.php?advanced=1" class="btn btn-link text-secondary text-decoration-none me-2">Clear filters</a>
              <button type="submit" class="btn btn-dark px-4">Apply Filters</button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <div class="card shadow-sm p-3 mb-4 no-print">
      <div class="table-responsive">
        <table id="personnelTable" class="table table-hover align-middle mb-0 no-print">
          <thead>
            <tr>
              <th style="width: 40px;"><input type="checkbox" id="selectAllPersonnel" class="form-check-input"
                  onclick="const checkboxes = document.querySelectorAll('.personnel-print-cb'); checkboxes.forEach(cb => cb.checked = this.checked);">
              </th>
              <th>Photo</th>
              <th>Name</th>
              <th>Personal No.</th>
              <th>Rank</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($people as $p): ?>
              <tr>
                <td><input type="checkbox" class="form-check-input personnel-print-cb"
                    value="<?= htmlspecialchars($p['personal_number']) ?>"></td>
                <td><img src="<?= htmlspecialchars(personnelPhotoUrl($p['photo_path'] ?? null)) ?>" class="rounded-circle"
                    width="36" height="36" style="object-fit:cover"></td>
                <td>
                  <div class="name-cell">
                    <div><?= htmlspecialchars($p['name']) ?></div>
                    <?php if (!empty($p['appointment_name'])): ?>
                      <div class="appointment-badge"><?= htmlspecialchars($p['appointment_name']) ?></div><?php endif; ?>
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
                    <form method="post" action="delete_personnel.php" class="d-inline"
                      data-confirm="Delete this record permanently?">
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

    <!-- Print Only Section -->
    <div class="print-only d-none">
      <h3 id="customPrintHeading" style="text-align:center; margin-bottom: 5px; font-weight: bold;"></h3>
      <h5 id="customPrintSubHeading"
        style="text-align:center; margin-bottom: 20px; font-weight: normal; font-size: 14pt;"></h5>
      <table class="table table-bordered" id="printTable"
        style="width: 100% !important; margin: 0 auto !important; text-align: center;">
        <thead>
          <tr>
            <th>Ser</th>
            <th>P No</th>
            <th>Rank</th>
            <th>Name</th>
            <th class="info-col info-col-blood_group" style="display:none;">Blood Group</th>
            <th class="info-col info-col-district" style="display:none;">District</th>
            <th class="info-col info-col-phone" style="display:none;">Phone</th>
            <th class="info-col info-col-appointment" style="display:none;">Appointment</th>
            <th class="info-col info-col-unit" style="display:none;">Unit</th>
            <th class="info-col info-col-cadre" style="display:none;">Cadre</th>
            <th class="info-col info-col-platoon" style="display:none;">Platoon</th>
            <th class="info-col info-col-nid" style="display:none;">NID</th>
            <th>Remark</th>
          </tr>
        </thead>
        <tbody id="printPersonnelTbody">
          <?php
          $rankWeights = [
            'Major' => 1, 'MAJ' => 1, 'Maj' => 1,
            'Captain' => 2, 'CAPT' => 2, 'Capt' => 2,
            'Lieutenant' => 3, 'LT' => 3, 'Lt' => 3,
            'Senior Warrant Officer' => 4, 'SWO' => 4,
            'Warrant Officer' => 5, 'WO' => 5,
            'Sergeant' => 6, 'SGT' => 6, 'Sgt' => 6,
            'Corporal' => 7, 'CPL' => 7, 'Cpl' => 7,
            'Lance Corporal' => 8, 'LCPL' => 8, 'Lcpl' => 8,
            'Sainik' => 9, 'SNK' => 9, 'SNK(GD)' => 9, 'Snk' => 9,
            'CK' => 10,
            'NC(E)' => 11,
            'NC(U)' => 12,
            'ATT' => 13
          ];
          $statusFilter = $filters['status'] ?? '';
          $isLeaveFilter = ($statusFilter === 'on_leave' || strpos($statusFilter, 'leave_') === 0);

          foreach ($people as $p):
            $rName = $p['rank_name'] ?? '';
            $weight = $rankWeights[$rName] ?? 99;
            ?>
            <tr data-pno="<?= htmlspecialchars($p['personal_number']) ?>" data-seniority="<?= $weight ?>">
              <td class="serial-col" style="text-align: center; vertical-align: middle;"></td>
              <td style="text-align: center; vertical-align: middle;"><?= htmlspecialchars($p['personal_number']) ?></td>
              <td style="text-align: center; vertical-align: middle;"><?= htmlspecialchars($p['rank_name'] ?? '-') ?></td>
              <td style="text-align: left; vertical-align: middle; padding-left: 10px !important;">
                <?= htmlspecialchars($p['name']) ?></td>
              <td class="info-col info-col-blood_group" style="display:none; text-align:center; vertical-align:middle;">
                <?= htmlspecialchars($p['blood_group_name'] ?? '-') ?></td>
              <td class="info-col info-col-district" style="display:none; text-align:center; vertical-align:middle;">
                <?= htmlspecialchars($p['address'] ?? '-') ?></td>
              <td class="info-col info-col-phone" style="display:none; text-align:center; vertical-align:middle;">
                <?= htmlspecialchars($p['mobile_number'] ?? '-') ?></td>
              <td class="info-col info-col-appointment" style="display:none; text-align:center; vertical-align:middle;">
                <?= htmlspecialchars($p['appointment_name'] ?? '-') ?></td>
              <td class="info-col info-col-unit" style="display:none; text-align:center; vertical-align:middle;">
                <?= htmlspecialchars($p['unit_name'] ?? '-') ?></td>
              <td class="info-col info-col-cadre" style="display:none; text-align:center; vertical-align:middle;">
                <?= htmlspecialchars($p['cadre_name'] ?? '-') ?></td>
              <td class="info-col info-col-platoon" style="display:none; text-align:center; vertical-align:middle;">
                <?= htmlspecialchars($p['platoon_name'] ?? '-') ?></td>
              <td class="info-col info-col-nid" style="display:none; text-align:center; vertical-align:middle;">
                <?= htmlspecialchars($p['nid'] ?? '-') ?></td>
              <td style="text-align: left; vertical-align: middle;">
                <?php if ($isLeaveFilter && !empty($p['current_leave_from']) && !empty($p['current_leave_to'])): ?>
                  <?= date('d/m/y', strtotime($p['current_leave_from'])) ?> to
                  <?= date('d/m/y', strtotime($p['current_leave_to'])) ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<script>
  // Shared helper: sort & filter print rows, set headings, trigger print
  function executePrint(options) {
    document.getElementById("customPrintHeading").innerText = options.heading;

    const subEl = document.getElementById("customPrintSubHeading");
    subEl.innerText = options.subHeading;
    subEl.style.display = options.subHeading ? 'block' : 'none';

    let dateEl = document.getElementById("customPrintDate");
    if (!dateEl) {
      dateEl = document.createElement("div");
      dateEl.id = "customPrintDate";
      dateEl.style.cssText = "text-align:right; font-weight: bold; margin-bottom: 10px;";
      document.getElementById("customPrintSubHeading").parentNode.insertBefore(dateEl, document.getElementById("customPrintSubHeading").nextSibling);
    }
    if (options.date) {
      const d = new Date(options.date);
      dateEl.innerText = "Date: " + d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
      dateEl.style.display = 'block';
    } else {
      dateEl.style.display = 'none';
    }

    // Handle selected rows
    const checkboxes = document.querySelectorAll('.personnel-print-cb:checked');
    const selectedPnos = Array.from(checkboxes).map(cb => cb.value);
    const printTbody = document.getElementById('printPersonnelTbody');
    let printRowsArray = Array.from(document.querySelectorAll('#printPersonnelTbody tr'));

    const comparePNo = (pno1, pno2) => {
      const a = pno1.toUpperCase();
      const b = pno2.toUpperCase();
      if (a.startsWith('BJO') && !b.startsWith('BJO')) return -1;
      if (!a.startsWith('BJO') && b.startsWith('BJO')) return 1;
      return a.localeCompare(b);
    };

    if (options.maintainSeniorityByPNo) {
      printRowsArray.sort((a, b) => {
        const pnoA = a.getAttribute('data-pno') || '';
        const pnoB = b.getAttribute('data-pno') || '';
        return comparePNo(pnoA, pnoB);
      });
      printRowsArray.forEach(row => printTbody.appendChild(row));
    }

    let serialCounter = 1;
    printRowsArray.forEach(row => {
      if (selectedPnos.length === 0 || selectedPnos.includes(row.getAttribute('data-pno'))) {
        row.style.display = '';
        const serCol = row.querySelector('.serial-col');
        if (serCol) serCol.innerText = serialCounter++;
      } else {
        row.style.display = 'none';
      }
    });

    // Handle info columns visibility
    const allInfoFields = ['blood_group', 'district', 'phone', 'appointment', 'unit', 'cadre', 'platoon', 'nid'];
    const selectedFields = options.infoFields || [];
    allInfoFields.forEach(field => {
      const display = selectedFields.includes(field) ? '' : 'none';
      document.querySelectorAll('.info-col-' + field).forEach(el => el.style.display = display);
    });

    let dynStyle = document.getElementById('dynamicPrintStyle');
    if (!dynStyle) {
      dynStyle = document.createElement('style');
      dynStyle.id = 'dynamicPrintStyle';
      document.head.appendChild(dynStyle);
    }
    const orientation = selectedFields.length > 3 ? 'landscape' : 'portrait';
    dynStyle.innerHTML = `@page { size: ${options.pageSize} ${orientation}; margin: 0.5in 0.5in 0.5in 0.8in; }`;

    setTimeout(() => { window.print(); }, 300);
  }

  function buildCommonPrintHtml() {
    return '<input id="printHeading" class="swal2-input" placeholder="Heading" value="Personnel List" style="max-width: 90%; margin: 10px auto;">' +
      '<input id="printSubHeading" class="swal2-input" placeholder="Sub-heading (optional)" style="max-width: 90%; margin: 10px auto;">' +
      '<div style="text-align:left; width: 90%; margin: 10px auto 0; font-size:14px;">Date (optional):</div>' +
      '<input type="date" id="printDate" class="swal2-input" style="max-width: 90%; margin: 5px auto 10px;">' +
      '<div class="form-check form-switch text-start" style="max-width: 90%; margin: 10px auto 10px;">' +
        '<input class="form-check-input" type="checkbox" id="maintainSeniorityByPNo">' +
        '<label class="form-check-label small text-dark ms-1" for="maintainSeniorityByPNo">Maintain Army Seniority by Personal No</label>' +
      '</div>' +
      '<div style="text-align:left; width: 90%; margin: 10px auto 0; font-size:14px;">Page Size:</div>' +
      '<select id="printPageSize" class="swal2-select" style="max-width: 90%; margin: 5px auto 10px;"><option value="A4">A4</option><option value="Legal">Legal</option><option value="Letter">Letter</option></select>';
  }

  function getCommonPrintValues() {
    return {
      heading: document.getElementById('printHeading').value,
      subHeading: document.getElementById('printSubHeading').value,
      date: document.getElementById('printDate').value,
      pageSize: document.getElementById('printPageSize').value,
      maintainSeniorityByPNo: document.getElementById('maintainSeniorityByPNo').checked
    };
  }

  function printWithInfo() {
    const infoFieldsHtml =
      '<div style="text-align:left; width: 90%; margin: 15px auto 8px; font-size:14px; font-weight:600; border-bottom:1px solid #e5e7eb; padding-bottom:6px;">Select columns to include:</div>' +
      '<div style="display:grid; grid-template-columns:1fr 1fr; gap:8px 16px; width:90%; margin:8px auto; text-align:left; font-size:14px;">' +
        '<div class="form-check form-switch mb-0">' +
          '<input class="form-check-input info-field-cb" type="checkbox" id="col_blood_group" value="blood_group">' +
          '<label class="form-check-label small text-dark ms-1" for="col_blood_group">Blood Group</label>' +
        '</div>' +
        '<div class="form-check form-switch mb-0">' +
          '<input class="form-check-input info-field-cb" type="checkbox" id="col_district" value="district">' +
          '<label class="form-check-label small text-dark ms-1" for="col_district">District</label>' +
        '</div>' +
        '<div class="form-check form-switch mb-0">' +
          '<input class="form-check-input info-field-cb" type="checkbox" id="col_phone" value="phone">' +
          '<label class="form-check-label small text-dark ms-1" for="col_phone">Phone Number</label>' +
        '</div>' +
        '<div class="form-check form-switch mb-0">' +
          '<input class="form-check-input info-field-cb" type="checkbox" id="col_appointment" value="appointment">' +
          '<label class="form-check-label small text-dark ms-1" for="col_appointment">Appointment</label>' +
        '</div>' +
        '<div class="form-check form-switch mb-0">' +
          '<input class="form-check-input info-field-cb" type="checkbox" id="col_unit" value="unit">' +
          '<label class="form-check-label small text-dark ms-1" for="col_unit">Unit</label>' +
        '</div>' +
        '<div class="form-check form-switch mb-0">' +
          '<input class="form-check-input info-field-cb" type="checkbox" id="col_cadre" value="cadre">' +
          '<label class="form-check-label small text-dark ms-1" for="col_cadre">Cadre</label>' +
        '</div>' +
        '<div class="form-check form-switch mb-0">' +
          '<input class="form-check-input info-field-cb" type="checkbox" id="col_platoon" value="platoon">' +
          '<label class="form-check-label small text-dark ms-1" for="col_platoon">Platoon</label>' +
        '</div>' +
        '<div class="form-check form-switch mb-0">' +
          '<input class="form-check-input info-field-cb" type="checkbox" id="col_nid" value="nid">' +
          '<label class="form-check-label small text-dark ms-1" for="col_nid">NID</label>' +
        '</div>' +
      '</div>';

    Swal.fire({
      title: 'Print with Information',
      html: buildCommonPrintHtml() + infoFieldsHtml,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: '<i class="ti ti-printer"></i> Print',
      preConfirm: () => {
        const base = getCommonPrintValues();
        const infoFields = Array.from(document.querySelectorAll('.info-field-cb:checked')).map(cb => cb.value);
        return { ...base, infoFields };
      }
    }).then((result) => {
      if (result.isConfirmed) {
        executePrint(result.value);
      }
    });
  }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>