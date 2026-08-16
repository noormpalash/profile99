<?php
class ApprovalFormatter {
    private static function getRequesterPerms(?int $userId): array {
        if (!$userId) return [];
        $db = getDB();
        $permsStmt = $db->prepare("SELECT p.name FROM permissions p JOIN role_permissions rp ON p.id = rp.permission_id JOIN users u ON u.role_id = rp.role_id WHERE u.id = ?");
        $permsStmt->execute([$userId]);
        return $permsStmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private static function injectMissingPermittedFields(array $data, array $perms): array {
        $map = [
            'edit_personnel_course' => ['course_id' => [], 'course_result' => []],
            'edit_personnel_moqs' => ['moq_id' => [], 'moq_result' => []],
            'edit_personnel_cadres' => ['cadre_ids' => []],
            'edit_personnel_education' => ['civil_education' => ''],
            'edit_personnel_service' => ['admission_date' => '', 'retirement_date' => '', 'un_mission' => '', 'punishment_note' => ''],
            'edit_personnel_ipft' => ['ipft_1st' => '', 'ipft_2nd' => '', 'ret' => '', 'speed_march' => ''],
            'edit_personnel_yearly_plan' => ['cycle_1' => '', 'cycle_2' => '', 'cycle_3' => '', 'cycle_4' => ''],
            'edit_personnel_family' => ['birthdate' => '', 'marriage_date' => '', 'marital_status' => '', 'children_count' => '', 'father_name' => '', 'mother_name' => '', 'spouse_name' => '', 'father_mobile' => '', 'mother_mobile' => '', 'spouse_mobile' => ''],
            'edit_personnel_family_member_status' => ['family_member' => 'No', 'fm_date_from' => '', 'fm_date_to' => '', 'fm_current_address' => '', 'living_status' => ''],
            'edit_personnel_health' => ['medical_category_id' => '', 'height_cm' => '', 'weight_kg' => '', 'any_disease' => ''],
            'edit_personnel_social' => ['social_links' => []],
            'edit_personnel_notes' => ['special_note' => ''],
            'edit_personnel_leaves' => ['leaves' => []]
        ];
        
        foreach ($map as $perm => $fields) {
            if (in_array($perm, $perms)) {
                foreach ($fields as $key => $defaultVal) {
                    if (!array_key_exists($key, $data)) {
                        $data[$key] = $defaultVal;
                    }
                }
            }
        }
        return $data;
    }

    public static function renderDiff(string $actionType, int $personnelId, string $proposedDataJson, ?int $requesterId = null): string {
        $data = json_decode($proposedDataJson, true) ?: [];
        
        if ($actionType === 'add') {
            return self::renderAdd($data);
        } elseif ($actionType === 'delete') {
            return "<div class='alert alert-danger mb-0'>Request to delete personnel ID: {$personnelId}</div>";
        } elseif ($actionType === 'edit') {
            if ($requesterId) {
                $data = self::injectMissingPermittedFields($data, self::getRequesterPerms($requesterId));
            }
            return self::renderEdit($personnelId, $data);
        }
        return "Unknown action.";
    }

    public static function renderDiffText(string $actionType, int $personnelId, string $proposedDataJson, ?int $requesterId = null): string {
        $data = json_decode($proposedDataJson, true) ?: [];
        if ($actionType === 'add') {
            $name = $data['name'] ?? 'Unknown';
            return "Added personnel: {$name}";
        } elseif ($actionType === 'delete') {
            return "Deleted personnel ID: {$personnelId}";
        } elseif ($actionType === 'edit') {
            if ($requesterId) {
                $data = self::injectMissingPermittedFields($data, self::getRequesterPerms($requesterId));
            }
            return self::renderEditText($personnelId, $data);
        }
        return "Action: {$actionType}";
    }

    private static function formatFieldName(string $key): string {
        $key = preg_replace('/_ids?$/', '', $key);
        return ucwords(str_replace('_', ' ', $key));
    }

    private static $lookupCache = [];

    private static function getLookupName($table, $id) {
        if (!$id) return '';
        if (!isset(self::$lookupCache[$table])) {
            $data = LookupManager::getAll($table);
            self::$lookupCache[$table] = [];
            foreach ($data as $row) {
                self::$lookupCache[$table][$row['id']] = $row['name'];
            }
        }
        return self::$lookupCache[$table][$id] ?? $id;
    }

    private static function translateValue(string $key, $val) {
        if ($val === null || $val === '') return $val;
        
        $lookups = [
            'rank_id' => 'ranks',
            'unit_id' => 'units',
            'cadre_id' => 'cadres',
            'platoon_id' => 'platoons',
            'blood_group_id' => 'blood_groups',
            'appointment_id' => 'appointments',
            'medical_category_id' => 'medical_categories'
        ];
        
        if (isset($lookups[$key])) {
            return self::getLookupName($lookups[$key], $val);
        }
        
        if ($key === 'cadre_ids' && is_array($val)) {
            return array_map(function($id) { return self::getLookupName('cadres', $id); }, $val);
        }
        if ($key === 'course_id' && is_array($val)) {
            return array_map(function($id) { return self::getLookupName('courses', $id); }, $val);
        }
        if ($key === 'moq_id' && is_array($val)) {
            return array_map(function($id) { return self::getLookupName('moqs', $id); }, $val);
        }
        if ($key === 'course_result' && is_array($val)) {
            $mapped = [];
            foreach ($val as $id => $res) {
                $mapped[self::getLookupName('courses', $id)] = $res;
            }
            return $mapped;
        }
        if ($key === 'moq_result' && is_array($val)) {
            $mapped = [];
            foreach ($val as $id => $res) {
                $mapped[self::getLookupName('moqs', $id)] = $res;
            }
            return $mapped;
        }
        
        return $val;
    }

    private static function renderValue($val): string {
        if (!is_array($val)) {
            return htmlspecialchars((string)$val);
        }
        
        $keys = array_keys($val);
        $isParallel = true;
        $rowCount = 0;
        foreach ($keys as $k) {
            if (!is_array($val[$k])) {
                $isParallel = false; break;
            }
            $rowCount = max($rowCount, count($val[$k]));
        }
        
        if ($isParallel && $rowCount > 0) {
            $html = "<table class='table table-sm table-bordered mb-0 mt-1' style='font-size: 0.75rem;'><thead><tr>";
            foreach ($keys as $k) {
                $html .= "<th class='bg-light text-secondary'>" . self::formatFieldName((string)$k) . "</th>";
            }
            $html .= "</tr></thead><tbody>";
            for ($i = 0; $i < $rowCount; $i++) {
                $html .= "<tr>";
                foreach ($keys as $k) {
                    $html .= "<td>" . htmlspecialchars((string)($val[$k][$i] ?? '')) . "</td>";
                }
                $html .= "</tr>";
            }
            $html .= "</tbody></table>";
            return $html;
        }

        $html = "<ul class='mb-0 ps-3' style='font-size: 0.8rem;'>";
        foreach ($val as $k => $v) {
            $html .= "<li><strong>" . self::formatFieldName((string)$k) . ":</strong> " . (is_array($v) ? json_encode($v) : htmlspecialchars((string)$v)) . "</li>";
        }
        $html .= "</ul>";
        return $html;
    }

    private static function renderAdd(array $data): string {
        unset($data['csrf_token']);
        $html = "<table class='table table-sm table-bordered mb-0 bg-white'><thead><tr><th>Field</th><th>Value</th></tr></thead><tbody>";
        foreach ($data as $key => $val) {
            if ($key === 'csrf_token' || str_starts_with($key, '_')) continue;
            $translatedVal = self::translateValue($key, $val);
            $html .= "<tr><td class='fw-bold align-middle' style='width: 150px;'>" . self::formatFieldName($key) . "</td><td>" . self::renderValue($translatedVal) . "</td></tr>";
        }
        $html .= "</tbody></table>";
        return $html;
    }

    private static function renderEdit(int $personnelId, array $data): string {
        $person = Personnel::find($personnelId);
        $service = Personnel::getService($personnelId);
        $family = Personnel::getFamily($personnelId);
        $health = Personnel::getHealth($personnelId);
        $notes = Personnel::getNotes($personnelId);

        $html = "<table class='table table-sm table-bordered mb-0 bg-white'><thead><tr><th style='width: 150px;'>Field</th><th>Old Value</th><th>New Value</th></tr></thead><tbody>";
        
        $hasChanges = false;
        foreach ($data as $key => $newVal) {
            if ($key === 'csrf_token' || str_starts_with($key, '_')) continue;
            
            $oldVal = self::getOldValue($key, $person, $service, $family, $health, $notes, $personnelId);
            
            // Normalize to prevent empty strings vs missing keys from triggering false diffs
            $oldValNorm = self::normalizeArray($oldVal);
            $newValNorm = self::normalizeArray($newVal);
            
            if (empty($oldValNorm) && empty($newValNorm)) continue;
            
            $oldVal = self::translateValue($key, $oldVal);
            $newVal = self::translateValue($key, $newVal);
            
            $newValJson = is_array($newValNorm) ? json_encode($newValNorm) : (string)$newValNorm;
            $oldValJson = is_array($oldValNorm) ? json_encode($oldValNorm) : (string)$oldValNorm;

            if ($newValJson !== $oldValJson) {
                if (is_array($newVal) || is_array($oldVal)) {
                    $diffHtml = self::renderArrayDiff($oldVal, $newVal);
                    if ($diffHtml !== "") {
                        $hasChanges = true;
                        $html .= "<tr>";
                        $html .= "<td class='fw-bold align-middle'>" . self::formatFieldName($key) . "</td>";
                        $html .= "<td colspan='2'>";
                        $html .= $diffHtml;
                        $html .= "</td>";
                        $html .= "</tr>";
                    }
                } else {
                    $hasChanges = true;
                    $html .= "<tr>";
                    $html .= "<td class='fw-bold align-middle'>" . self::formatFieldName($key) . "</td>";
                    $html .= "<td class='text-danger align-middle'><del>" . htmlspecialchars((string)$oldVal) . "</del></td>";
                    $html .= "<td class='text-success align-middle fw-medium'>" . htmlspecialchars((string)$newVal) . "</td>";
                    $html .= "</tr>";
                }
            }
        }
        
        if (!$hasChanges) {
            return "<div class='text-muted'>No changes detected.</div>";
        }

        $html .= "</tbody></table>";
        return $html;
    }

    private static function normalizeArray($val) {
        if (!is_array($val)) return $val === '' ? null : $val;
        $res = [];
        foreach ($val as $k => $v) {
            $norm = self::normalizeArray($v);
            if ($norm !== null && $norm !== []) {
                $res[$k] = $norm;
            }
        }
        if (self::isList($val)) {
            return array_values($res);
        }
        ksort($res);
        return $res;
    }

    private static function renderEditText(int $personnelId, array $data): string {
        $person = Personnel::find($personnelId);
        $service = Personnel::getService($personnelId);
        $family = Personnel::getFamily($personnelId);
        $health = Personnel::getHealth($personnelId);
        $notes = Personnel::getNotes($personnelId);

        $changes = [];
        foreach ($data as $key => $newVal) {
            if ($key === 'csrf_token' || str_starts_with($key, '_')) continue;
            
            $oldVal = self::getOldValue($key, $person, $service, $family, $health, $notes, $personnelId);
            
            // Normalize to prevent empty strings vs missing keys from triggering false diffs
            $oldValNorm = self::normalizeArray($oldVal);
            $newValNorm = self::normalizeArray($newVal);
            
            // If both normalize to empty/null, they are effectively the same
            if (empty($oldValNorm) && empty($newValNorm)) continue;
            
            $oldValTrans = self::translateValue($key, $oldVal);
            $newValTrans = self::translateValue($key, $newVal);
            
            $newValJson = is_array($newValNorm) ? json_encode($newValNorm) : (string)$newValNorm;
            $oldValJson = is_array($oldValNorm) ? json_encode($oldValNorm) : (string)$oldValNorm;

            if ($newValJson !== $oldValJson) {
                if (is_array($newValTrans) || is_array($oldValTrans)) {
                    $changes[] = self::formatFieldName($key) . " updated";
                } else {
                    $old = trim((string)$oldValTrans);
                    $new = trim((string)$newValTrans);
                    if ($old === '') {
                        $changes[] = self::formatFieldName($key) . " set to " . ($new === '' ? "empty" : $new);
                    } else {
                        $changes[] = self::formatFieldName($key) . " changed from {$old} to " . ($new === '' ? "empty" : $new);
                    }
                }
            }
        }
        
        if (empty($changes)) {
            return "No changes detected";
        }
        return implode(', ', $changes);
    }

    private static function isList(array $arr): bool {
        if (function_exists('array_is_list')) return array_is_list($arr);
        if ($arr === []) return true;
        return array_keys($arr) === range(0, count($arr) - 1);
    }

    private static function isParallelArray($val): bool {
        if (!is_array($val) || empty($val)) return false;
        $keys = array_keys($val);
        foreach ($keys as $k) {
            if (!is_array($val[$k])) return false;
        }
        return true;
    }

    private static function extractParallelRows(array $val): array {
        $keys = array_keys($val);
        $rowCount = 0;
        foreach ($keys as $k) {
            $rowCount = max($rowCount, count($val[$k]));
        }
        $rows = [];
        for ($i = 0; $i < $rowCount; $i++) {
            $row = [];
            foreach ($keys as $k) {
                $row[$k] = (string)($val[$k][$i] ?? '');
            }
            $rows[] = $row;
        }
        return $rows;
    }

    private static function renderRowsTable(array $rows, string $tableClass): string {
        if (empty($rows)) return "";
        $firstRow = reset($rows);
        $keys = array_keys($firstRow);
        
        $html = "<table class='table table-sm table-bordered mb-0 mt-1 {$tableClass}' style='font-size: 0.75rem;'><thead><tr>";
        foreach ($keys as $k) {
            $html .= "<th class='bg-light text-secondary'>" . self::formatFieldName((string)$k) . "</th>";
        }
        $html .= "</tr></thead><tbody>";
        foreach ($rows as $row) {
            $html .= "<tr>";
            foreach ($keys as $k) {
                $html .= "<td>" . htmlspecialchars((string)($row[$k] ?? '')) . "</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</tbody></table>";
        return $html;
    }

    private static function renderArrayDiff($oldVal, $newVal): string {
        $oldArr = is_array($oldVal) ? $oldVal : [];
        $newArr = is_array($newVal) ? $newVal : [];
        
        $oldIsParallel = self::isParallelArray($oldArr);
        $newIsParallel = self::isParallelArray($newArr);

        if (empty($oldArr) && $newIsParallel) $oldIsParallel = true;
        if (empty($newArr) && $oldIsParallel) $newIsParallel = true;

        if ($oldIsParallel && $newIsParallel) {
            $oldRows = empty($oldArr) ? [] : self::extractParallelRows($oldArr);
            $newRows = empty($newArr) ? [] : self::extractParallelRows($newArr);
            
            $oldJson = array_map('json_encode', $oldRows);
            $newJson = array_map('json_encode', $newRows);
            
            $addedIdx = [];
            $deletedIdx = [];
            
            foreach ($newJson as $i => $j) {
                if (!in_array($j, $oldJson)) $addedIdx[] = $i;
            }
            foreach ($oldJson as $i => $j) {
                if (!in_array($j, $newJson)) $deletedIdx[] = $i;
            }
            
            if (empty($addedIdx) && empty($deletedIdx)) {
                return "";
            }

            $html = "";
            if (!empty($deletedIdx)) {
                $html .= "<div class='text-danger mb-1 fw-bold'>Removed:</div>";
                $html .= self::renderRowsTable(array_intersect_key($oldRows, array_flip($deletedIdx)), "table-danger");
            }
            if (!empty($addedIdx)) {
                $mt = !empty($html) ? " mt-2" : "";
                $html .= "<div class='text-success{$mt} mb-1 fw-bold'>Added:</div>";
                $html .= self::renderRowsTable(array_intersect_key($newRows, array_flip($addedIdx)), "table-success");
            }
            return $html;
        }

        if (self::isList($oldArr) && self::isList($newArr)) {
            $oldStrArr = array_map(function($v) { return is_array($v) ? json_encode($v) : (string)$v; }, $oldArr);
            $newStrArr = array_map(function($v) { return is_array($v) ? json_encode($v) : (string)$v; }, $newArr);
            
            $added = array_diff($newStrArr, $oldStrArr);
            $deleted = array_diff($oldStrArr, $newStrArr);
            
            if (empty($added) && empty($deleted)) {
                return "";
            }

            $html = "<ul class='mb-0 ps-3' style='font-size: 0.8rem;'>";
            foreach ($deleted as $d) {
                $html .= "<li class='text-danger'><del>" . htmlspecialchars($d) . "</del> (Removed)</li>";
            }
            foreach ($added as $a) {
                $html .= "<li class='text-success'>" . htmlspecialchars($a) . " (Added)</li>";
            }
            $html .= "</ul>";
            return $html;
        }
        
        $allKeys = array_unique(array_merge(array_keys($oldArr), array_keys($newArr)));
        $diffFound = false;
        $html = "<ul class='mb-0 ps-3' style='font-size: 0.8rem;'>";
        foreach ($allKeys as $k) {
            $o = $oldArr[$k] ?? null;
            $n = $newArr[$k] ?? null;
            $oStr = is_array($o) ? json_encode($o) : (string)$o;
            $nStr = is_array($n) ? json_encode($n) : (string)$n;
            
            if ($oStr !== $nStr) {
                $diffFound = true;
                if (!array_key_exists($k, $oldArr)) {
                    $html .= "<li class='text-success'><strong>" . self::formatFieldName((string)$k) . ":</strong> " . htmlspecialchars($nStr) . " (Added)</li>";
                } elseif (!array_key_exists($k, $newArr)) {
                    $html .= "<li class='text-danger'><strong>" . self::formatFieldName((string)$k) . ":</strong> <del>" . htmlspecialchars($oStr) . "</del> (Removed)</li>";
                } else {
                    $html .= "<li><strong>" . self::formatFieldName((string)$k) . ":</strong> <del class='text-danger'>" . htmlspecialchars($oStr) . "</del> <span class='text-muted mx-1'>&rarr;</span> <span class='text-success'>" . htmlspecialchars($nStr) . "</span></li>";
                }
            }
        }
        $html .= "</ul>";
        return $diffFound ? $html : "";
    }

    private static function getOldValue($key, $person, $service, $family, $health, $notes, $personnelId) {
        if (is_array($person) && array_key_exists($key, $person)) return $person[$key];
        if (is_array($service) && array_key_exists($key, $service)) return $service[$key];
        if (is_array($family) && array_key_exists($key, $family)) return $family[$key];
        if (is_array($health) && array_key_exists($key, $health)) return $health[$key];
        
        if ($key === 'family_member') return 'No';
        
        if ($key === 'special_note' || $key === 'note') return $notes['note'] ?? '';
        
        if ($key === 'civil_education') {
            $edu = Personnel::getEducation($personnelId);
            return $edu['civil_education'] ?? '';
        }
        
        if ($key === 'cadre_ids') {
            return array_map('strval', array_column(Personnel::getCadres($personnelId), 'id'));
        }
        if ($key === 'course_id') {
            return array_map('strval', array_column(Personnel::getCourses($personnelId), 'course_id'));
        }
        if ($key === 'course_result') {
            $courses = Personnel::getCourses($personnelId);
            $res = [];
            foreach ($courses as $c) {
                $res[$c['course_id']] = (string)$c['result'];
            }
            return $res;
        }
        if ($key === 'moq_id') {
            return array_map('strval', array_column(Personnel::getMoqs($personnelId), 'moq_id'));
        }
        if ($key === 'moq_result') {
            $moqs = Personnel::getMoqs($personnelId);
            $res = [];
            foreach ($moqs as $m) {
                $res[$m['moq_id']] = (string)$m['result'];
            }
            return $res;
        }
        if ($key === 'social_links') {
            $links = Personnel::getSocialLinks($personnelId);
            $res = [];
            foreach ($links as $l) {
                $res[$l['platform']] = (string)$l['url'];
            }
            return $res;
        }
        if ($key === 'leaves') {
            $leaves = Personnel::getLeaves($personnelId);
            if (empty($leaves)) return '';
            $res = ['from_date'=>[], 'to_date'=>[], 'total_days'=>[], 'leave_type'=>[]];
            foreach($leaves as $l) {
                $res['from_date'][] = $l['from_date'] ?? '';
                $res['to_date'][] = $l['to_date'] ?? '';
                $res['total_days'][] = (string)($l['total_days'] ?? '');
                $res['leave_type'][] = $l['leave_type'] ?? '';
            }
            return $res;
        }

        return null;
    }
}
