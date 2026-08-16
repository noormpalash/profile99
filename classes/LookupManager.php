<?php
// ============================================
// LookupManager: generic Add/Edit/Delete for dropdown-option tables
// (Ranks, Units, Cadres, Platoons, Blood Groups, Courses, Medical Categories)
// One reusable class instead of 7 separate CRUD screens.
// ============================================

require_once __DIR__ . '/../config/db.php';

class LookupManager {

    // Whitelist of allowed tables — NEVER accept a raw table name from user input directly
    private const ALLOWED_TABLES = [
        'ranks', 'units', 'cadres', 'platoons',
        'blood_groups', 'courses', 'moqs', 'medical_categories', 'appointments'
    ];

    // Maps the referencing FK column for each lookup table, used to block unsafe deletes
    private const FK_COLUMN = [
        'ranks' => 'rank_id', 'units' => 'unit_id', 'cadres' => 'cadre_id',
        'platoons' => 'platoon_id', 'blood_groups' => 'blood_group_id',
        'courses' => null, 'moqs' => null, 'medical_categories' => null,
        'appointments' => 'appointment_id',
    ];

    private static function validateTable(string $table): void {
        if (!in_array($table, self::ALLOWED_TABLES, true)) {
            throw new Exception("Invalid lookup table: $table");
        }
    }

    public static function getAll(string $table): array {
        self::validateTable($table);
        $db = getDB();
        return $db->query("SELECT * FROM `$table` ORDER BY name")->fetchAll();
    }

    public static function add(string $table, string $name): void {
        self::validateTable($table);
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO `$table` (name) VALUES (?)");
        $stmt->execute([$name]);
    }

    public static function update(string $table, int $id, string $name): void {
        self::validateTable($table);
        $db = getDB();
        $stmt = $db->prepare("UPDATE `$table` SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
    }

    // Blocks delete if personnel records still reference this value
    public static function delete(string $table, int $id): void {
        self::validateTable($table);
        $fkColumn = self::FK_COLUMN[$table];
        $db = getDB();

        if ($fkColumn !== null) {
            $check = $db->prepare("SELECT COUNT(*) FROM personnel WHERE `$fkColumn` = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                throw new Exception("Cannot delete — this value is still assigned to one or more personnel records.");
            }
        }

        $stmt = $db->prepare("DELETE FROM `$table` WHERE id = ?");
        $stmt->execute([$id]);
    }
}
