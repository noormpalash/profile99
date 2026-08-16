<?php
// ============================================
// Personnel: CRUD + mandatory photo handling
// ============================================

require_once __DIR__ . '/../config/db.php';

class Personnel {

    // ---- List / Search ----
    public static function all(): array {
        $db = getDB();
        return $db->query("SELECT p.*, r.name AS rank_name, u.name AS unit_name, b.name AS blood_group_name, a.name AS appointment_name, pf.family_member, pf.living_status
                            FROM personnel p
                            LEFT JOIN ranks r ON p.rank_id = r.id
                            LEFT JOIN units u ON p.unit_id = u.id
                            LEFT JOIN blood_groups b ON p.blood_group_id = b.id
                            LEFT JOIN appointments a ON p.appointment_id = a.id
                            LEFT JOIN personnel_family pf ON p.id = pf.personnel_id
                            ORDER BY p.name")->fetchAll();
    }

    public static function filter(array $filters): array {
        $db = getDB();
        $sql = "SELECT p.*, r.name AS rank_name, u.name AS unit_name, b.name AS blood_group_name, a.name AS appointment_name, pf.family_member, pf.living_status
            FROM personnel p
            LEFT JOIN ranks r ON p.rank_id = r.id
            LEFT JOIN units u ON p.unit_id = u.id
            LEFT JOIN blood_groups b ON p.blood_group_id = b.id
            LEFT JOIN appointments a ON p.appointment_id = a.id
            LEFT JOIN personnel_family pf ON p.id = pf.personnel_id
            WHERE 1=1";
        $params = [];

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $sql .= " AND (p.name LIKE ? OR p.personal_number LIKE ? OR p.mobile_number LIKE ?)";
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $rankId = (int)($filters['rank_id'] ?? 0);
        if ($rankId > 0) {
            $sql .= " AND p.rank_id = ?";
            $params[] = $rankId;
        }

        $unitId = (int)($filters['unit_id'] ?? 0);
        if ($unitId > 0) {
            $sql .= " AND p.unit_id = ?";
            $params[] = $unitId;
        }

        $appointmentId = (int)($filters['appointment_id'] ?? 0);
        if ($appointmentId > 0) {
            $sql .= " AND p.appointment_id = ?";
            $params[] = $appointmentId;
        }

        $bloodGroupId = (int)($filters['blood_group_id'] ?? 0);
        if ($bloodGroupId > 0) {
            $sql .= " AND p.blood_group_id = ?";
            $params[] = $bloodGroupId;
        }

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }

        $district = trim((string)($filters['district'] ?? ''));
        if ($district !== '') {
            $sql .= " AND p.address LIKE ?";
            $params[] = '%' . $district . '%';
        }

        $platoonId = (int)($filters['platoon_id'] ?? 0);
        if ($platoonId > 0) {
            $sql .= " AND p.platoon_id = ?";
            $params[] = $platoonId;
        }

        $maritalStatus = trim((string)($filters['marital_status'] ?? ''));
        if ($maritalStatus !== '') {
            $sql .= " AND p.id IN (SELECT personnel_id FROM personnel_family WHERE marital_status = ?)";
            $params[] = $maritalStatus;
        }

        $courseId = (int)($filters['course_id'] ?? 0);
        if ($courseId > 0) {
            $sql .= " AND p.id IN (SELECT personnel_id FROM personnel_courses WHERE course_id = ?)";
            $params[] = $courseId;
        }

        $moqId = (int)($filters['moq_id'] ?? 0);
        if ($moqId > 0) {
            $sql .= " AND p.id IN (SELECT personnel_id FROM personnel_moqs WHERE moq_id = ?)";
            $params[] = $moqId;
        }

        $moqStatus = trim((string)($filters['moq_status'] ?? ''));
        if ($moqStatus === 'qualified') {
            $sql .= " AND p.id IN (SELECT personnel_id FROM personnel_moqs)";
        } elseif ($moqStatus === 'not_qualified') {
            $sql .= " AND p.id NOT IN (SELECT personnel_id FROM personnel_moqs)";
        }

        $cadreId = (int)($filters['cadre_id'] ?? 0);
        if ($cadreId > 0) {
            $sql .= " AND (p.cadre_id = ? OR p.id IN (SELECT personnel_id FROM personnel_cadres WHERE cadre_id = ?))";
            $params[] = $cadreId;
            $params[] = $cadreId;
        }

        $familyMember = trim((string)($filters['family_member'] ?? ''));
        if ($familyMember !== '') {
            $sql .= " AND p.id IN (SELECT personnel_id FROM personnel_family WHERE family_member = ?)";
            $params[] = $familyMember;
        }

        $sql .= " ORDER BY p.name";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $db = getDB();
        $stmt = $db->prepare("SELECT p.*, r.name AS rank_name, u.name AS unit_name, c.name AS cadre_name, pl.name AS platoon_name, b.name AS blood_group_name, a.name AS appointment_name,
                       (SELECT leave_type FROM personnel_leaves WHERE personnel_id = p.id AND CURRENT_DATE >= from_date AND CURRENT_DATE <= to_date LIMIT 1) AS current_leave_type
                       FROM personnel p
                       LEFT JOIN ranks r ON p.rank_id = r.id
                       LEFT JOIN units u ON p.unit_id = u.id
                       LEFT JOIN cadres c ON p.cadre_id = c.id
                       LEFT JOIN platoons pl ON p.platoon_id = pl.id
                       LEFT JOIN blood_groups b ON p.blood_group_id = b.id
                       LEFT JOIN appointments a ON p.appointment_id = a.id
                       WHERE p.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function search(string $term): array {
        $db = getDB();
        $stmt = $db->prepare("SELECT p.*, r.name AS rank_name, u.name AS unit_name, a.name AS appointment_name, pf.family_member, pf.living_status
                       FROM personnel p
                       LEFT JOIN ranks r ON p.rank_id = r.id
                       LEFT JOIN units u ON p.unit_id = u.id
                       LEFT JOIN appointments a ON p.appointment_id = a.id
                       LEFT JOIN personnel_family pf ON p.id = pf.personnel_id
                       WHERE p.name LIKE ? OR p.personal_number LIKE ? OR a.name LIKE ?
                       ORDER BY p.name");
        $like = "%$term%";
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    // ---- Mandatory photo upload/validation ----
    // Returns the saved relative path on success, or throws Exception on failure.
    public static function handlePhotoUpload(array $file, string $personalNumber): string {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            // Photo is optional now; caller should handle missing photo.
            throw new Exception('NO_PHOTO_UPLOADED');
        }

        if ($file['size'] > MAX_PHOTO_SIZE) {
            throw new Exception('Photo must be smaller than 2MB.');
        }

        // Validate actual MIME type, not just the extension
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($allowed[$mime])) {
            throw new Exception('Only JPG or PNG photos are allowed.');
        }

        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

        $ext = $allowed[$mime];
        $safeNumber = preg_replace('/[^A-Za-z0-9_-]/', '', $personalNumber);
        $filename = $safeNumber . '_' . time() . '.' . $ext;
        $destination = UPLOAD_DIR . $filename;

        // Resize/crop to a consistent 400x400 using GD (bundled with XAMPP)
        self::resizeAndSave($file['tmp_name'], $mime, $destination, 400, 400);

        return $filename; // store just the filename in DB; build full path with UPLOAD_URL
    }

    private static function resizeAndSave(string $srcPath, string $mime, string $destPath, int $w, int $h): void {
        $src = $mime === 'image/png' ? imagecreatefrompng($srcPath) : imagecreatefromjpeg($srcPath);
        if (!$src) {
            throw new Exception('Uploaded image could not be processed.');
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);

        // Crop to square from center, then resize
        $side = min($srcW, $srcH);
        $srcX = intdiv($srcW - $side, 2);
        $srcY = intdiv($srcH - $side, 2);

        $dst = imagecreatetruecolor($w, $h);
        if ($mime === 'image/png') {
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $w, $h, $side, $side);

        if ($mime === 'image/png') {
            imagepng($dst, $destPath, 8);
        } else {
            imagejpeg($dst, $destPath, 85);
        }

        imagedestroy($src);
        imagedestroy($dst);
    }

    // ---- Create ----
    public static function create(array $data, array $photoFile = [], ?array $requesterPerms = null): int {
        $db = getDB();
        $isTransactionOwner = false;
        if (!$db->inTransaction()) {
            $db->beginTransaction();
            $isTransactionOwner = true;
        }

        try {
            // Photo optional: attempt upload only when provided, or use pending photo
            $photoPath = null;
            if (!empty($data['_pending_photo_path'])) {
                $photoPath = $data['_pending_photo_path'];
            } elseif (!empty($photoFile) && isset($photoFile['error']) && $photoFile['error'] === UPLOAD_ERR_OK) {
                try {
                    $photoPath = self::handlePhotoUpload($photoFile, $data['personal_number']); // may throw
                } catch (Exception $e) {
                    if ($e->getMessage() === 'NO_PHOTO_UPLOADED') {
                        $photoPath = null;
                    } else {
                        throw $e;
                    }
                }
            }

            $addressVal = trim((string)($data['address'] ?? '')) ?: null;
            $detailedAddressVal = trim((string)($data['detailed_address'] ?? '')) ?: null;
            $villVal = trim((string)($data['vill'] ?? '')) ?: null;
            $poVal = trim((string)($data['po'] ?? '')) ?: null;
            $psVal = trim((string)($data['ps'] ?? '')) ?: null;

            $stmt = $db->prepare("INSERT INTO personnel
                (personal_number, name, nid, photo_path, rank_id, unit_id, cadre_id, platoon_id, blood_group_id, appointment_id, batch, mobile_number, address, detailed_address, vill, po, ps, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $data['personal_number'], $data['name'], trim((string)($data['nid'] ?? '')) ?: null, $photoPath,
                empty($data['rank_id']) ? null : $data['rank_id'], empty($data['unit_id']) ? null : $data['unit_id'], empty($data['cadre_id']) ? null : $data['cadre_id'],
                empty($data['platoon_id']) ? null : $data['platoon_id'], empty($data['blood_group_id']) ? null : $data['blood_group_id'], empty($data['appointment_id']) ? null : $data['appointment_id'],
                trim((string)($data['batch'] ?? '')) ?: null,
                $data['mobile_number'] ?? null, $addressVal, $detailedAddressVal, $villVal, $poVal, $psVal,
                $data['status'] ?? 'active'
            ]);
            $personnelId = (int)$db->lastInsertId();

            // Save multiple courses (course_id[] and course_result[] expected)
            if (isset($data['course_id']) && is_array($data['course_id'])) {
                self::saveCourse($personnelId, $data['course_id'], $data['course_result'] ?? []);
            } else {
                self::saveCourse($personnelId, $data['course_id'] ?? null, $data['course_result'] ?? null);
            }
            if (isset($data['moq_id']) && is_array($data['moq_id'])) {
                self::saveMoq($personnelId, $data['moq_id'], $data['moq_result'] ?? []);
            } else {
                self::saveMoq($personnelId, $data['moq_id'] ?? null, $data['moq_result'] ?? null);
            }
            self::saveEducation($personnelId, $data['civil_education'] ?? null);
            // Save multiple cadres if provided (cadre_ids[])
            if (isset($data['cadre_ids']) && is_array($data['cadre_ids'])) {
                self::saveCadres($personnelId, $data['cadre_ids']);
            } else {
                // fallback single cadre_id
                if (!empty($data['cadre_id'])) {
                    self::saveCadres($personnelId, [(int)$data['cadre_id']]);
                }
            }
            self::saveService($personnelId, $data['admission_date'] ?? null, $data['retirement_date'] ?? null, $data['un_mission'] ?? null, $data['punishment_note'] ?? null, $data['ipft_1st'] ?? null, $data['ipft_2nd'] ?? null, $data['ret'] ?? null, $data['speed_march'] ?? null, $data['cycle_1'] ?? null, $data['cycle_2'] ?? null, $data['cycle_3'] ?? null, $data['cycle_4'] ?? null);
            self::saveFamily(
                $personnelId,
                $data['birthdate'] ?? null,
                $data['marriage_date'] ?? null,
                $data['father_name'] ?? null,
                $data['father_mobile'] ?? null,
                $data['mother_name'] ?? null,
                $data['mother_mobile'] ?? null,
                $data['spouse_name'] ?? null,
                $data['spouse_mobile'] ?? null,
                $data['children_count'] ?? null,
                $data['marital_status'] ?? null,
                $data['family_member'] ?? 'No',
                $data['living_status'] ?? null,
                $data['fm_date_from'] ?? null,
                $data['fm_date_to'] ?? null,
                $data['fm_current_address'] ?? null
            );
            self::saveHealth($personnelId, empty($data['medical_category_id']) ? null : (int)$data['medical_category_id'], $data['height_cm'] ?? null, $data['weight_kg'] ?? null, $data['any_disease'] ?? null);
            self::saveSocialLinks($personnelId, $data['social_links'] ?? []);
            self::saveNote($personnelId, $data['special_note'] ?? null);
            if (isset($data['leaves']) && is_array($data['leaves'])) {
                self::saveLeaves($personnelId, $data['leaves']);
            }

            if ($isTransactionOwner) $db->commit();
            return $personnelId;
        } catch (Exception $e) {
            if ($isTransactionOwner) $db->rollBack();
            if (!empty($photoPath ?? '')) {
                @unlink(UPLOAD_DIR . $photoPath);
            }
            throw $e;
        }
    }

    private static function hasPerm(string $perm, ?array $requesterPerms): bool {
        if ($requesterPerms !== null) {
            return in_array($perm, $requesterPerms);
        }
        return Auth::hasPermission($perm);
    }

    public static function hasAnyPerm(array $requiredPerms, ?array $userPerms = null): bool {
        if ($userPerms !== null) {
            return !empty(array_intersect($requiredPerms, $userPerms));
        }
        return Auth::hasAnyPermission($requiredPerms);
    }

    private static function parseCommaSeparatedLookups(string $table, string $str): array {
        $ids = [];
        foreach (explode(',', $str) as $val) {
            $val = trim($val);
            if ($val !== '') {
                $ids[] = (string)self::getOrCreateLookup($table, $val);
            }
        }
        return $ids;
    }

    private static function parseKeyValueLookups(string $table, string $str): array {
        $ids = [];
        $results = [];
        foreach (explode(',', $str) as $item) {
            $parts = explode(':', $item, 2);
            $name = trim($parts[0]);
            if ($name !== '') {
                $id = (string)self::getOrCreateLookup($table, $name);
                $ids[] = $id;
                $results[$id] = trim($parts[1] ?? '');
            }
        }
        return ['ids' => $ids, 'results' => $results];
    }

    private static function parseLeaves(string $str): array {
        $leaves = ['from_date' => [], 'to_date' => [], 'leave_type' => []];
        foreach (explode(',', $str) as $item) {
            $parts = explode(':', $item, 3);
            if (count($parts) >= 2) {
                $leaves['from_date'][] = trim($parts[0]);
                $leaves['to_date'][] = trim($parts[1]);
                $leaves['leave_type'][] = trim($parts[2] ?? '');
            }
        }
        return $leaves;
    }

    private static function parseSocialLinks(string $str): array {
        $links = [];
        foreach (explode(',', $str) as $item) {
            // Because URL contains ':' (e.g. https://), we limit explode to 2.
            $parts = explode(':', $item, 2);
            $platform = strtolower(trim($parts[0]));
            $url = trim($parts[1] ?? '');
            if ($platform !== '' && $url !== '') {
                $links[$platform] = $url;
            }
        }
        return $links;
    }

    // ---- Update (photo optional on edit — keep existing if not replaced) ----
    public static function update(int $id, array $data, ?array $photoFile = null, ?array $requesterPerms = null): void {
        $db = getDB();
        $isTransactionOwner = false;
        if (!$db->inTransaction()) {
            $db->beginTransaction();
            $isTransactionOwner = true;
        }
        $existingPerson = self::find($id);
        $oldPhotoPath = $existingPerson['photo_path'] ?? null;
        $newPhotoPath = null;

        if (!$existingPerson) {
            throw new Exception("Personnel not found.");
        }

        try {
            $newPhotoPath = null;

            $addressVal = $data['address'] ?? null;
            $detailedAddressVal = $data['detailed_address'] ?? null;
            $villVal = $data['vill'] ?? null;
            $poVal = $data['po'] ?? null;
            $psVal = $data['ps'] ?? null;

            if (self::hasPerm('edit_personnel_basic', $requesterPerms)) {
                if ($photoFile && $photoFile['error'] === UPLOAD_ERR_OK) {
                    $newPhotoPath = self::handlePhotoUpload($photoFile, $data['personal_number'] ?? $existingPerson['personal_number']);
                    // Delete old photo
                    if (!empty($existingPerson['photo_path'])) {
                        $oldPath = UPLOAD_DIR . $existingPerson['photo_path'];
                        if (file_exists($oldPath) && is_file($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                }
                
                $sql = "UPDATE personnel SET personal_number=?, name=?, nid=?, rank_id=?, unit_id=?,
                        cadre_id=?, platoon_id=?, blood_group_id=?, appointment_id=?, batch=?, mobile_number=?, address=?, detailed_address=?, vill=?, po=?, ps=?, status=?";
                $params = [
                    $data['personal_number'] ?? $existingPerson['personal_number'], $data['name'] ?? $existingPerson['name'], trim((string)($data['nid'] ?? $existingPerson['nid'])) ?: null,
                    empty($data['rank_id']) ? null : $data['rank_id'], empty($data['unit_id']) ? null : $data['unit_id'], empty($data['cadre_id']) ? null : $data['cadre_id'],
                    empty($data['platoon_id']) ? null : $data['platoon_id'], empty($data['blood_group_id']) ? null : $data['blood_group_id'], empty($data['appointment_id']) ? null : $data['appointment_id'],
                    trim((string)($data['batch'] ?? '')) ?: null,
                    $data['mobile_number'] ?? null, $addressVal, $detailedAddressVal, $villVal, $poVal, $psVal,
                    $data['status'] ?? 'active'
                ];

                if ($newPhotoPath) {
                    $sql .= ", photo_path=?";
                    $params[] = $newPhotoPath;
                }
                
                $sql .= " WHERE id=?";
                $params[] = $id;

                $stmt = $db->prepare($sql);
                $stmt->execute($params);

            } elseif (self::hasPerm('edit_personnel_status', $requesterPerms) && isset($data['status'])) {
                $stmt = $db->prepare("UPDATE personnel SET status=? WHERE id=?");
                $stmt->execute([$data['status'], $id]);
            }

            // Removed clearRelatedData() to handle it per section
            if (self::hasPerm('edit_personnel_course', $requesterPerms)) {
                $db->prepare("DELETE FROM personnel_courses WHERE personnel_id = ?")->execute([$id]);
                if (isset($data['course_id']) && is_array($data['course_id'])) {
                    self::saveCourse($id, $data['course_id'], $data['course_result'] ?? []);
                } else {
                    self::saveCourse($id, $data['course_id'] ?? null, $data['course_result'] ?? null);
                }
            }

            if (self::hasPerm('edit_personnel_moqs', $requesterPerms)) {
                $db->prepare("DELETE FROM personnel_moqs WHERE personnel_id = ?")->execute([$id]);
                if (isset($data['moq_id']) && is_array($data['moq_id'])) {
                    self::saveMoq($id, $data['moq_id'], $data['moq_result'] ?? []);
                } else {
                    self::saveMoq($id, $data['moq_id'] ?? null, $data['moq_result'] ?? null);
                }
            }

            if (self::hasPerm('edit_personnel_cadres', $requesterPerms)) {
                $db->prepare("DELETE FROM personnel_cadres WHERE personnel_id = ?")->execute([$id]);
                if (isset($data['cadre_ids']) && is_array($data['cadre_ids'])) {
                    self::saveCadres($id, $data['cadre_ids']);
                } else {
                    if (!empty($data['cadre_id'])) {
                        self::saveCadres($id, [(int)$data['cadre_id']]);
                    }
                }
            }

            if (self::hasPerm('edit_personnel_education', $requesterPerms)) {
                $db->prepare("DELETE FROM personnel_education WHERE personnel_id = ?")->execute([$id]);
                self::saveEducation($id, $data['civil_education'] ?? null);
            }

            $updateService = false;
            $existingService = self::getService($id) ?: [];
            $serviceData = [
                'admission_date' => $existingService['admission_date'] ?? null,
                'retirement_date' => $existingService['retirement_date'] ?? null,
                'un_mission' => $existingService['un_mission'] ?? null,
                'punishment_note' => $existingService['punishment_note'] ?? null,
                'ipft_1st' => $existingService['ipft_1st'] ?? null,
                'ipft_2nd' => $existingService['ipft_2nd'] ?? null,
                'ret' => $existingService['ret'] ?? null,
                'speed_march' => $existingService['speed_march'] ?? null,
                'cycle_1' => $existingService['cycle_1'] ?? null,
                'cycle_2' => $existingService['cycle_2'] ?? null,
                'cycle_3' => $existingService['cycle_3'] ?? null,
                'cycle_4' => $existingService['cycle_4'] ?? null,
            ];

            if (self::hasPerm('edit_personnel_service', $requesterPerms)) {
                $serviceData['admission_date'] = $data['admission_date'] ?? null;
                $serviceData['retirement_date'] = $data['retirement_date'] ?? null;
                $serviceData['un_mission'] = $data['un_mission'] ?? null;
                $serviceData['punishment_note'] = $data['punishment_note'] ?? null;
                $updateService = true;
            }
            if (self::hasPerm('edit_personnel_ipft', $requesterPerms)) {
                $serviceData['ipft_1st'] = $data['ipft_1st'] ?? null;
                $serviceData['ipft_2nd'] = $data['ipft_2nd'] ?? null;
                $serviceData['ret'] = $data['ret'] ?? null;
                $serviceData['speed_march'] = $data['speed_march'] ?? null;
                $updateService = true;
            }
            if (self::hasPerm('edit_personnel_yearly_plan', $requesterPerms)) {
                $serviceData['cycle_1'] = $data['cycle_1'] ?? null;
                $serviceData['cycle_2'] = $data['cycle_2'] ?? null;
                $serviceData['cycle_3'] = $data['cycle_3'] ?? null;
                $serviceData['cycle_4'] = $data['cycle_4'] ?? null;
                $updateService = true;
            }

            if ($updateService) {
                $db->prepare("DELETE FROM personnel_service WHERE personnel_id = ?")->execute([$id]);
                self::saveService(
                    $id,
                    $serviceData['admission_date'],
                    $serviceData['retirement_date'],
                    $serviceData['un_mission'],
                    $serviceData['punishment_note'],
                    $serviceData['ipft_1st'],
                    $serviceData['ipft_2nd'],
                    $serviceData['ret'],
                    $serviceData['speed_march'],
                    $serviceData['cycle_1'],
                    $serviceData['cycle_2'],
                    $serviceData['cycle_3'],
                    $serviceData['cycle_4']
                );
            }

            $updateFamily = false;
            $existingFamily = self::getFamily($id) ?: [];
            $familyData = [
                'birthdate' => $existingFamily['birthdate'] ?? null,
                'marriage_date' => $existingFamily['marriage_date'] ?? null,
                'father_name' => $existingFamily['father_name'] ?? null,
                'father_mobile' => $existingFamily['father_mobile'] ?? null,
                'mother_name' => $existingFamily['mother_name'] ?? null,
                'mother_mobile' => $existingFamily['mother_mobile'] ?? null,
                'spouse_name' => $existingFamily['spouse_name'] ?? null,
                'spouse_mobile' => $existingFamily['spouse_mobile'] ?? null,
                'children_count' => $existingFamily['children_count'] ?? null,
                'marital_status' => $existingFamily['marital_status'] ?? null,
                'family_member' => $existingFamily['family_member'] ?? 'No',
                'living_status' => $existingFamily['living_status'] ?? null,
                'fm_date_from' => $existingFamily['fm_date_from'] ?? null,
                'fm_date_to' => $existingFamily['fm_date_to'] ?? null,
                'fm_current_address' => $existingFamily['fm_current_address'] ?? null,
            ];

            if (self::hasPerm('edit_personnel_family', $requesterPerms)) {
                $familyData['birthdate'] = $data['birthdate'] ?? null;
                $familyData['marriage_date'] = $data['marriage_date'] ?? null;
                $familyData['father_name'] = $data['father_name'] ?? null;
                $familyData['father_mobile'] = $data['father_mobile'] ?? null;
                $familyData['mother_name'] = $data['mother_name'] ?? null;
                $familyData['mother_mobile'] = $data['mother_mobile'] ?? null;
                $familyData['spouse_name'] = $data['spouse_name'] ?? null;
                $familyData['spouse_mobile'] = $data['spouse_mobile'] ?? null;
                $familyData['children_count'] = $data['children_count'] ?? null;
                $familyData['marital_status'] = $data['marital_status'] ?? null;
                $updateFamily = true;
            }

            if (self::hasPerm('edit_personnel_family_member_status', $requesterPerms)) {
                $familyData['family_member'] = $data['family_member'] ?? 'No';
                $familyData['living_status'] = $data['living_status'] ?? null;
                $familyData['fm_date_from'] = $data['fm_date_from'] ?? null;
                $familyData['fm_date_to'] = $data['fm_date_to'] ?? null;
                $familyData['fm_current_address'] = $data['fm_current_address'] ?? null;
                $updateFamily = true;
            }

            if ($updateFamily) {
                self::saveFamily(
                    $id,
                    $familyData['birthdate'],
                    $familyData['marriage_date'],
                    $familyData['father_name'],
                    $familyData['father_mobile'],
                    $familyData['mother_name'],
                    $familyData['mother_mobile'],
                    $familyData['spouse_name'],
                    $familyData['spouse_mobile'],
                    $familyData['children_count'],
                    $familyData['marital_status'],
                    $familyData['family_member'],
                    $familyData['living_status'],
                    $familyData['fm_date_from'],
                    $familyData['fm_date_to'],
                    $familyData['fm_current_address']
                );
            }

            if (self::hasPerm('edit_personnel_health', $requesterPerms)) {
                $db->prepare("DELETE FROM personnel_health WHERE personnel_id = ?")->execute([$id]);
                self::saveHealth($id, empty($data['medical_category_id']) ? null : (int)$data['medical_category_id'], $data['height_cm'] ?? null, $data['weight_kg'] ?? null, $data['any_disease'] ?? null);
            }

            if (self::hasPerm('edit_personnel_social', $requesterPerms)) {
                $db->prepare("DELETE FROM personnel_social_links WHERE personnel_id = ?")->execute([$id]);
                self::saveSocialLinks($id, $data['social_links'] ?? []);
            }

            if (self::hasPerm('edit_personnel_notes', $requesterPerms)) {
                $db->prepare("DELETE FROM personnel_notes WHERE personnel_id = ?")->execute([$id]);
                self::saveNote($id, $data['special_note'] ?? null);
            }

            if (self::hasPerm('edit_personnel_leaves', $requesterPerms)) {
                self::saveLeaves($id, $data['leaves'] ?? []);
            }

            if ($isTransactionOwner) $db->commit();
            if (!empty($newPhotoPath) && !empty($oldPhotoPath) && $oldPhotoPath !== $newPhotoPath) {
                $oldFile = UPLOAD_DIR . $oldPhotoPath;
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }
        } catch (Exception $e) {
            if ($isTransactionOwner) $db->rollBack();
            if (!empty($newPhotoPath)) {
                $newFile = UPLOAD_DIR . $newPhotoPath;
                if (is_file($newFile)) {
                    @unlink($newFile);
                }
            }
            throw $e;
        }
    }



    private static function saveCourse(int $personnelId, $courseIdOrArray, $resultOrArray = null): void {
        $db = getDB();
        // support single course or arrays
        if (is_array($courseIdOrArray)) {
            $courseIds = $courseIdOrArray;
            $results = is_array($resultOrArray) ? $resultOrArray : [];
            $isSequential = empty($results) || (array_keys($results) === range(0, count($results) - 1));
            $stmt = $db->prepare("INSERT INTO personnel_courses (personnel_id, course_id, result) VALUES (?,?,?)");
            foreach ($courseIds as $idx => $cid) {
                $cid = (int)$cid;
                if ($cid <= 0) continue;
                if (isset($results[$cid]) && !$isSequential) {
                    $res = $results[$cid];
                } elseif (isset($results[$idx]) && $isSequential) {
                    $res = $results[$idx];
                } else {
                    $res = $results[$cid] ?? ($results[$idx] ?? null);
                }
                $stmt->execute([$personnelId, $cid, trim($res) ?: null]);
            }
            return;
        }

        // single
        $courseId = $courseIdOrArray;
        $result = $resultOrArray;
        if (!$courseId) return;
        $stmt = $db->prepare("INSERT INTO personnel_courses (personnel_id, course_id, result) VALUES (?,?,?)");
        $stmt->execute([$personnelId, $courseId, $result ?: null]);
    }

    private static function saveMoq(int $personnelId, $moqIdOrArray, $resultOrArray = null): void {
        $db = getDB();
        // support single moq or arrays
        if (is_array($moqIdOrArray)) {
            $moqIds = $moqIdOrArray;
            $results = is_array($resultOrArray) ? $resultOrArray : [];
            $isSequential = empty($results) || (array_keys($results) === range(0, count($results) - 1));
            $stmt = $db->prepare("INSERT INTO personnel_moqs (personnel_id, moq_id, result) VALUES (?,?,?)");
            foreach ($moqIds as $idx => $cid) {
                $cid = (int)$cid;
                if ($cid <= 0) continue;
                if (isset($results[$cid]) && !$isSequential) {
                    $res = $results[$cid];
                } elseif (isset($results[$idx]) && $isSequential) {
                    $res = $results[$idx];
                } else {
                    $res = $results[$cid] ?? ($results[$idx] ?? null);
                }
                $stmt->execute([$personnelId, $cid, trim($res) ?: null]);
            }
            return;
        }

        // single
        $moqId = $moqIdOrArray;
        $result = $resultOrArray;
        if (!$moqId) return;
        $stmt = $db->prepare("INSERT INTO personnel_moqs (personnel_id, moq_id, result) VALUES (?,?,?)");
        $stmt->execute([$personnelId, $moqId, $result ?: null]);
    }

    private static function saveCadres(int $personnelId, array $cadreIds): void {
        if (empty($cadreIds)) return;
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO personnel_cadres (personnel_id, cadre_id) VALUES (?,?)");
        foreach ($cadreIds as $cid) {
            $cid = (int)$cid;
            if ($cid <= 0) continue;
            $stmt->execute([$personnelId, $cid]);
        }
    }

    public static function getCadres(int $personnelId): array {
        $db = getDB();
        $stmt = $db->prepare("SELECT c.* FROM personnel_cadres pc JOIN cadres c ON pc.cadre_id = c.id WHERE pc.personnel_id = ? ORDER BY c.name");
        $stmt->execute([$personnelId]);
        return $stmt->fetchAll();
    }

    public static function getCourses(int $personnelId): array {
        $db = getDB();
        $stmt = $db->prepare("SELECT pc.*, c.name AS course_name FROM personnel_courses pc JOIN courses c ON pc.course_id = c.id WHERE pc.personnel_id = ? ORDER BY pc.id");
        $stmt->execute([$personnelId]);
        return $stmt->fetchAll();
    }

    public static function getMoqs(int $personnelId): array {
        $db = getDB();
        $stmt = $db->prepare("SELECT pm.*, m.name AS moq_name FROM personnel_moqs pm JOIN moqs m ON pm.moq_id = m.id WHERE pm.personnel_id = ? ORDER BY pm.id");
        $stmt->execute([$personnelId]);
        return $stmt->fetchAll();
    }

    private static function saveEducation(int $personnelId, ?string $civilEducation): void {
        if ($civilEducation === null || trim($civilEducation) === '') {
            return;
        }
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO personnel_education (personnel_id, civil_education) VALUES (?,?)");
        $stmt->execute([$personnelId, trim($civilEducation)]);
    }

    private static function saveService(int $personnelId, ?string $admissionDate, ?string $retirementDate, ?string $unMission, ?string $punishmentNote, ?string $ipft1 = null, ?string $ipft2 = null, ?string $ret = null, ?string $speedMarch = null, ?string $c1 = null, ?string $c2 = null, ?string $c3 = null, ?string $c4 = null): void {
        if (($admissionDate === null || trim($admissionDate) === '') && ($retirementDate === null || trim($retirementDate) === '') && ($unMission === null || trim($unMission) === '') && ($punishmentNote === null || trim($punishmentNote) === '') && ($ipft1 === null || trim($ipft1) === '') && ($ipft2 === null || trim($ipft2) === '') && ($ret === null || trim($ret) === '') && ($speedMarch === null || trim($speedMarch) === '') && ($c1 === null || trim($c1) === '') && ($c2 === null || trim($c2) === '') && ($c3 === null || trim($c3) === '') && ($c4 === null || trim($c4) === '')) {
            return;
        }
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO personnel_service (personnel_id, admission_date, retirement_date, un_mission, punishment_note, ipft_1st, ipft_2nd, ret, speed_march, cycle_1, cycle_2, cycle_3, cycle_4) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $personnelId,
            $admissionDate ?: null,
            $retirementDate ?: null,
            trim((string)$unMission) ?: null,
            trim((string)$punishmentNote) ?: null,
            trim((string)$ipft1) ?: null,
            trim((string)$ipft2) ?: null,
            trim((string)$ret) ?: null,
            trim((string)$speedMarch) ?: null,
            trim((string)$c1) ?: null,
            trim((string)$c2) ?: null,
            trim((string)$c3) ?: null,
            trim((string)$c4) ?: null
        ]);
    }

    private static function saveFamily(int $personnelId, ?string $birthdate, ?string $marriageDate, ?string $fatherName, ?string $fatherMobile, ?string $motherName, ?string $motherMobile, ?string $spouseName, ?string $spouseMobile, ?string $childrenCount, ?string $maritalStatus, ?string $familyMember, ?string $livingStatus, ?string $fmDateFrom = null, ?string $fmDateTo = null, ?string $fmCurrentAddress = null): void {
        // If everything is empty, skip saving
        if ((($birthdate === null || trim($birthdate) === '') && ($marriageDate === null || trim($marriageDate) === '') && ($fatherName === null || trim($fatherName) === '') && ($fatherMobile === null || trim($fatherMobile) === '') && ($motherName === null || trim($motherName) === '') && ($motherMobile === null || trim($motherMobile) === '') && ($spouseName === null || trim($spouseName) === '') && ($spouseMobile === null || trim($spouseMobile) === '') && ($childrenCount === null || trim($childrenCount) === '') && ($maritalStatus === null || trim($maritalStatus) === '') && ($familyMember === null || trim($familyMember) === 'No') && ($livingStatus === null || trim($livingStatus) === '') && ($fmDateFrom === null || trim($fmDateFrom) === '') && ($fmDateTo === null || trim($fmDateTo) === '') && ($fmCurrentAddress === null || trim($fmCurrentAddress) === '')) ) {
            return;
        }
        $db = getDB();
        // check if exists
        $stmt = $db->prepare("SELECT id FROM personnel_family WHERE personnel_id = :pid");
        $stmt->execute([':pid' => $personnelId]);
        if ($row = $stmt->fetch()) {
            $stmt = $db->prepare('UPDATE personnel_family SET birthdate = :b, marriage_date = :m, father_name = :fn, father_mobile = :fm, mother_name = :mn, mother_mobile = :mm, spouse_name = :sn, spouse_mobile = :sm, children_count = :c, marital_status = :ms, family_member = :fm_mb, fm_date_from = :fdf, fm_date_to = :fdt, fm_current_address = :fca, living_status = :ls WHERE personnel_id = :pid');
            $stmt->execute([
                ':b' => $birthdate ?: null,
                ':m' => $marriageDate ?: null,
                ':fn' => trim($fatherName) ?: null,
                ':fm' => trim($fatherMobile) ?: null,
                ':mn' => trim($motherName) ?: null,
                ':mm' => trim($motherMobile) ?: null,
                ':sn' => trim($spouseName) ?: null,
                ':sm' => trim($spouseMobile) ?: null,
                ':c' => $childrenCount !== null && trim($childrenCount) !== '' ? (int)$childrenCount : 0,
                ':ms' => $maritalStatus ?: null,
                ':fm_mb' => $familyMember ?: 'No',
                ':fdf' => trim($fmDateFrom) ?: null,
                ':fdt' => trim($fmDateTo) ?: null,
                ':fca' => trim($fmCurrentAddress) ?: null,
                ':ls' => trim($livingStatus) ?: null,
                ':pid' => $personnelId
            ]);
        } else {
            $stmt = $db->prepare('INSERT INTO personnel_family (personnel_id, birthdate, marriage_date, father_name, father_mobile, mother_name, mother_mobile, spouse_name, spouse_mobile, children_count, marital_status, family_member, fm_date_from, fm_date_to, fm_current_address, living_status) VALUES (:pid, :b, :m, :fn, :fm, :mn, :mm, :sn, :sm, :c, :ms, :fm_mb, :fdf, :fdt, :fca, :ls)');
            $stmt->execute([
                ':pid' => $personnelId,
                ':b' => $birthdate ?: null,
                ':m' => $marriageDate ?: null,
                ':fn' => trim($fatherName) ?: null,
                ':fm' => trim($fatherMobile) ?: null,
                ':mn' => trim($motherName) ?: null,
                ':mm' => trim($motherMobile) ?: null,
                ':sn' => trim($spouseName) ?: null,
                ':sm' => trim($spouseMobile) ?: null,
                ':c' => $childrenCount !== null && trim($childrenCount) !== '' ? (int)$childrenCount : 0,
                ':ms' => $maritalStatus ?: null,
                ':fm_mb' => $familyMember ?: 'No',
                ':fdf' => trim($fmDateFrom) ?: null,
                ':fdt' => trim($fmDateTo) ?: null,
                ':fca' => trim($fmCurrentAddress) ?: null,
                ':ls' => trim($livingStatus) ?: null
            ]);
        }
    }

    private static function saveHealth(int $personnelId, ?int $medicalCategoryId, ?string $height, ?string $weight, ?string $anyDisease): void {
        if (($medicalCategoryId === null || $medicalCategoryId === 0) && ($height === null || trim($height) === '') && ($weight === null || trim($weight) === '') && ($anyDisease === null || trim($anyDisease) === '')) {
            return;
        }
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO personnel_health (personnel_id, medical_category_id, height_cm, weight_kg, any_disease) VALUES (?,?,?,?,?)");
        $stmt->execute([
            $personnelId,
            $medicalCategoryId ?: null,
            $height !== null && trim($height) !== '' ? $height : null,
            $weight !== null && trim($weight) !== '' ? $weight : null,
            trim($anyDisease) ?: null
        ]);
    }

    private static function saveSocialLinks(int $personnelId, array $links): void {
        $db = getDB();
        foreach ($links as $platform => $url) {
            $url = trim($url);
            if ($url === '') {
                continue;
            }
            $stmt = $db->prepare("INSERT INTO personnel_social_links (personnel_id, platform, url) VALUES (?,?,?)");
            $stmt->execute([$personnelId, $platform, $url]);
        }
    }

    private static function saveNote(int $personnelId, ?string $note): void {
        if ($note === null || trim($note) === '') {
            return;
        }
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO personnel_notes (personnel_id, note) VALUES (?,?)");
        $stmt->execute([$personnelId, trim($note)]);
    }

    public static function getCourse(int $personnelId): ?array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM personnel_courses WHERE personnel_id = ? LIMIT 1");
        $stmt->execute([$personnelId]);
        return $stmt->fetch() ?: null;
    }

    public static function getMoq(int $personnelId): ?array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM personnel_moqs WHERE personnel_id = ? LIMIT 1");
        $stmt->execute([$personnelId]);
        return $stmt->fetch() ?: null;
    }

    public static function getEducation(int $personnelId): ?array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM personnel_education WHERE personnel_id = ? LIMIT 1");
        $stmt->execute([$personnelId]);
        return $stmt->fetch() ?: null;
    }

    public static function getService(int $personnelId): ?array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM personnel_service WHERE personnel_id = ? LIMIT 1");
        $stmt->execute([$personnelId]);
        return $stmt->fetch() ?: null;
    }

    public static function getFamily(int $personnelId): ?array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM personnel_family WHERE personnel_id = ? LIMIT 1");
        $stmt->execute([$personnelId]);
        return $stmt->fetch() ?: null;
    }

    public static function getHealth(int $personnelId): ?array {
        $db = getDB();
        $stmt = $db->prepare("SELECT ph.*, mc.name AS medical_category_name
                              FROM personnel_health ph
                              LEFT JOIN medical_categories mc ON ph.medical_category_id = mc.id
                              WHERE ph.personnel_id = ?
                              LIMIT 1");
        $stmt->execute([$personnelId]);
        return $stmt->fetch() ?: null;
    }

    public static function getNotes(int $personnelId): ?array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM personnel_notes WHERE personnel_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$personnelId]);
        return $stmt->fetch() ?: null;
    }

    public static function getSocialLinks(int $personnelId): array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM personnel_social_links WHERE personnel_id = ? ORDER BY id");
        $stmt->execute([$personnelId]);
        return $stmt->fetchAll();
    }

    public static function getLeaves(int $personnelId): array {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM personnel_leaves WHERE personnel_id = ? ORDER BY from_date DESC");
        $stmt->execute([$personnelId]);
        return $stmt->fetchAll();
    }

    private static function saveLeaves(int $personnelId, array $leavesData): void {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM personnel_leaves WHERE personnel_id = ?");
        $stmt->execute([$personnelId]);

        if (empty($leavesData['from_date'])) return;

        $stmt = $db->prepare("INSERT INTO personnel_leaves (personnel_id, from_date, to_date, total_days, leave_type) VALUES (?, ?, ?, ?, ?)");
        
        $count = count($leavesData['from_date']);
        for ($i = 0; $i < $count; $i++) {
            $from = trim((string)($leavesData['from_date'][$i] ?? ''));
            $to = trim((string)($leavesData['to_date'][$i] ?? ''));
            $days = trim((string)($leavesData['total_days'][$i] ?? ''));
            $type = trim((string)($leavesData['leave_type'][$i] ?? ''));
            
            if ($from === '' && $to === '' && $type === '') continue;
            
            $stmt->execute([$personnelId, $from ?: null, $to ?: null, $days !== '' ? (int)$days : null, $type ?: null]);
        }
    }

    // ---- Delete ----
    public static function delete(int $id): void {
        $person = self::find($id);
        if ($person && !empty($person['photo_path'])) {
            $file = UPLOAD_DIR . $person['photo_path'];
            if (file_exists($file)) unlink($file);
        }
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM personnel WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    public static function importExcel(string $filePath): array {
        require_once __DIR__ . '/../vendor/autoload.php';
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        $results = [];
        
        foreach ($sheet->getRowIterator(2) as $rowObj) {
            $rowIndex = $rowObj->getRowIndex();
            $personalNumber = trim((string)$sheet->getCell('A' . $rowIndex)->getValue());
            $name = trim((string)$sheet->getCell('B' . $rowIndex)->getValue());
            
            if ($personalNumber === '' && $name === '') {
                continue; // Skip empty rows
            }
            if ($personalNumber === '') {
                $results[] = ['row' => $rowIndex, 'status' => 'error', 'message' => 'Personal Number is required'];
                continue;
            }
            if ($name === '') {
                $results[] = ['row' => $rowIndex, 'personal_number' => $personalNumber, 'status' => 'error', 'message' => 'Name is required'];
                continue;
            }
            
            try {
                $data = [
                    'personal_number' => $personalNumber,
                    'name' => $name,
                    'nid' => trim((string)$sheet->getCell('C' . $rowIndex)->getValue()),
                    'appointment_id' => self::getOrCreateLookup('appointments', $sheet->getCell('D' . $rowIndex)->getValue()),
                    'rank_id' => self::getOrCreateLookup('ranks', $sheet->getCell('E' . $rowIndex)->getValue()),
                    'unit_id' => self::getOrCreateLookup('units', $sheet->getCell('F' . $rowIndex)->getValue()),
                    'platoon_id' => self::getOrCreateLookup('platoons', $sheet->getCell('G' . $rowIndex)->getValue()),
                    'blood_group_id' => self::getOrCreateLookup('blood_groups', $sheet->getCell('H' . $rowIndex)->getValue()),
                    'status' => trim((string)$sheet->getCell('I' . $rowIndex)->getValue()) ?: 'active',
                    'batch' => trim((string)$sheet->getCell('J' . $rowIndex)->getValue()),
                    'mobile_number' => trim((string)$sheet->getCell('K' . $rowIndex)->getValue()),
                    'address' => trim((string)$sheet->getCell('L' . $rowIndex)->getValue()), // district
                    'vill' => trim((string)$sheet->getCell('M' . $rowIndex)->getValue()),
                    'po' => trim((string)$sheet->getCell('N' . $rowIndex)->getValue()),
                    'ps' => trim((string)$sheet->getCell('O' . $rowIndex)->getValue()),
                    'admission_date' => self::parseExcelDate($sheet->getCell('P' . $rowIndex)),
                    'retirement_date' => self::parseExcelDate($sheet->getCell('Q' . $rowIndex)),
                    'un_mission' => trim((string)$sheet->getCell('R' . $rowIndex)->getValue()),
                    'punishment_note' => trim((string)$sheet->getCell('S' . $rowIndex)->getValue()),
                    'ipft_1st' => trim((string)$sheet->getCell('T' . $rowIndex)->getValue()),
                    'ipft_2nd' => trim((string)$sheet->getCell('U' . $rowIndex)->getValue()),
                    'ret' => trim((string)$sheet->getCell('V' . $rowIndex)->getValue()),
                    'speed_march' => trim((string)$sheet->getCell('W' . $rowIndex)->getValue()),
                    'cycle_1' => trim((string)$sheet->getCell('X' . $rowIndex)->getValue()),
                    'cycle_2' => trim((string)$sheet->getCell('Y' . $rowIndex)->getValue()),
                    'cycle_3' => trim((string)$sheet->getCell('Z' . $rowIndex)->getValue()),
                    'cycle_4' => trim((string)$sheet->getCell('AA' . $rowIndex)->getValue()),
                    'birthdate' => self::parseExcelDate($sheet->getCell('AB' . $rowIndex)),
                    'marriage_date' => self::parseExcelDate($sheet->getCell('AC' . $rowIndex)),
                    'marital_status' => trim(strtolower((string)$sheet->getCell('AD' . $rowIndex)->getValue())),
                    'children_count' => trim((string)$sheet->getCell('AE' . $rowIndex)->getValue()),
                    'family_member' => trim((string)$sheet->getCell('AF' . $rowIndex)->getValue()) ?: 'No',
                    'fm_date_from' => self::parseExcelDate($sheet->getCell('AG' . $rowIndex)),
                    'fm_date_to' => self::parseExcelDate($sheet->getCell('AH' . $rowIndex)),
                    'living_status' => trim((string)$sheet->getCell('AI' . $rowIndex)->getValue()),
                    'fm_current_address' => trim((string)$sheet->getCell('AJ' . $rowIndex)->getValue()),
                    'father_name' => trim((string)$sheet->getCell('AK' . $rowIndex)->getValue()),
                    'father_mobile' => trim((string)$sheet->getCell('AL' . $rowIndex)->getValue()),
                    'mother_name' => trim((string)$sheet->getCell('AM' . $rowIndex)->getValue()),
                    'mother_mobile' => trim((string)$sheet->getCell('AN' . $rowIndex)->getValue()),
                    'spouse_name' => trim((string)$sheet->getCell('AO' . $rowIndex)->getValue()),
                    'spouse_mobile' => trim((string)$sheet->getCell('AP' . $rowIndex)->getValue()),
                    'medical_category_id' => self::getOrCreateLookup('medical_categories', $sheet->getCell('AQ' . $rowIndex)->getValue()),
                    'height_cm' => trim((string)$sheet->getCell('AR' . $rowIndex)->getValue()),
                    'weight_kg' => trim((string)$sheet->getCell('AS' . $rowIndex)->getValue()),
                    'any_disease' => trim((string)$sheet->getCell('AT' . $rowIndex)->getValue()),
                    'special_note' => trim((string)$sheet->getCell('AU' . $rowIndex)->getValue()),
                ];

                $cadresStr = trim((string)$sheet->getCell('AV' . $rowIndex)->getValue());
                if ($cadresStr !== '') {
                    $data['cadre_ids'] = self::parseCommaSeparatedLookups('cadres', $cadresStr);
                }
                
                $coursesStr = trim((string)$sheet->getCell('AW' . $rowIndex)->getValue());
                if ($coursesStr !== '') {
                    $cData = self::parseKeyValueLookups('courses', $coursesStr);
                    $data['course_id'] = $cData['ids'];
                    $data['course_result'] = $cData['results'];
                }
                
                $moqsStr = trim((string)$sheet->getCell('AX' . $rowIndex)->getValue());
                if ($moqsStr !== '') {
                    $mData = self::parseKeyValueLookups('moqs', $moqsStr);
                    $data['moq_id'] = $mData['ids'];
                    $data['moq_result'] = $mData['results'];
                }
                
                $leavesStr = trim((string)$sheet->getCell('AY' . $rowIndex)->getValue());
                if ($leavesStr !== '') {
                    $data['leaves'] = self::parseLeaves($leavesStr);
                }
                
                $socialStr = trim((string)$sheet->getCell('AZ' . $rowIndex)->getValue());
                if ($socialStr !== '') {
                    $data['social_links'] = self::parseSocialLinks($socialStr);
                }
                
                $db = getDB();
                $stmt = $db->prepare("SELECT id FROM personnel WHERE personal_number = ?");
                $stmt->execute([$personalNumber]);
                $id = $stmt->fetchColumn();
                
                $perms = ['edit_personnel_basic', 'edit_personnel_status', 'edit_personnel_course', 'edit_personnel_moqs', 'edit_personnel_cadres', 'edit_personnel_education', 'edit_personnel_service', 'edit_personnel_ipft', 'edit_personnel_yearly_plan', 'edit_personnel_family', 'edit_personnel_family_member_status', 'edit_personnel_health', 'edit_personnel_social', 'edit_personnel_notes', 'edit_personnel_leaves'];

                if ($id) {
                    self::update((int)$id, $data, null, $perms);
                    $results[] = ['row' => $rowIndex, 'personal_number' => $personalNumber, 'name' => $name, 'status' => 'success', 'message' => 'Updated existing record'];
                } else {
                    self::create($data, [], $perms);
                    $results[] = ['row' => $rowIndex, 'personal_number' => $personalNumber, 'name' => $name, 'status' => 'success', 'message' => 'Created new record'];
                }
            } catch (Exception $e) {
                $results[] = ['row' => $rowIndex, 'personal_number' => $personalNumber, 'name' => $name, 'status' => 'error', 'message' => $e->getMessage()];
            }
        }
        
        return $results;
    }
    
    private static function parseExcelDate($cell): ?string {
        $val = $cell->getValue();
        if ($val === null || trim((string)$val) === '') return null;
        if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
            $timestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($val);
            return gmdate('Y-m-d', $timestamp);
        }
        $time = strtotime((string)$val);
        return $time ? date('Y-m-d', $time) : null;
    }

    private static function getOrCreateLookup(string $table, $value): ?int {
        $allowed = ['ranks', 'units', 'cadres', 'platoons', 'blood_groups', 'courses', 'moqs', 'medical_categories', 'appointments'];
        if (!in_array($table, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid lookup table: $table");
        }
        if ($value === null || trim((string)$value) === '') return null;
        $value = trim((string)$value);
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM `$table` WHERE name = ?");
        $stmt->execute([$value]);
        $id = $stmt->fetchColumn();
        if ($id) return (int)$id;
        
        try {
            $stmt = $db->prepare("INSERT INTO `$table` (name) VALUES (?)");
            $stmt->execute([$value]);
            return (int)$db->lastInsertId();
        } catch (Exception $e) {
            return null;
        }
    }
}
