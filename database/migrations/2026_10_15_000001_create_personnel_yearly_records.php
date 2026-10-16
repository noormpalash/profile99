<?php
declare(strict_types=1);

class CreatePersonnelYearlyRecords implements MigrationInterface
{
    public function up(PDO $db): void
    {
        // 1. Create personnel_yearly_records table if not exists
        $tableExists = $db->query("SHOW TABLES LIKE 'personnel_yearly_records'")->fetch();
        if (!$tableExists) {
            $db->exec("
                CREATE TABLE personnel_yearly_records (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    personnel_id INT NOT NULL,
                    record_year INT NOT NULL,
                    ipft_1st VARCHAR(255) NULL,
                    ipft_2nd VARCHAR(255) NULL,
                    ret VARCHAR(255) NULL,
                    speed_march VARCHAR(255) NULL,
                    cycle_1 VARCHAR(100) NULL,
                    cycle_2 VARCHAR(100) NULL,
                    cycle_3 VARCHAR(100) NULL,
                    cycle_4 VARCHAR(100) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_personnel_year (personnel_id, record_year),
                    INDEX idx_personnel_year (personnel_id, record_year),
                    INDEX idx_record_year (record_year),
                    CONSTRAINT fk_yearly_records_personnel FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 2. Add service_year to personnel_service if not exists
        $colServiceYear = $db->query("SHOW COLUMNS FROM personnel_service LIKE 'service_year'")->fetch();
        if (!$colServiceYear) {
            $db->exec("ALTER TABLE personnel_service ADD COLUMN service_year INT NULL DEFAULT NULL AFTER personnel_id");
        }

        // 3. Migrate existing records from personnel_service into personnel_yearly_records
        $currentYear = (int)date('Y');
        $stmt = $db->query("
            SELECT personnel_id, ipft_1st, ipft_2nd, ret, speed_march, cycle_1, cycle_2, cycle_3, cycle_4
            FROM personnel_service
            WHERE (ipft_1st IS NOT NULL AND TRIM(ipft_1st) != '')
               OR (ipft_2nd IS NOT NULL AND TRIM(ipft_2nd) != '')
               OR (ret IS NOT NULL AND TRIM(ret) != '')
               OR (speed_march IS NOT NULL AND TRIM(speed_march) != '')
               OR (cycle_1 IS NOT NULL AND TRIM(cycle_1) != '')
               OR (cycle_2 IS NOT NULL AND TRIM(cycle_2) != '')
               OR (cycle_3 IS NOT NULL AND TRIM(cycle_3) != '')
               OR (cycle_4 IS NOT NULL AND TRIM(cycle_4) != '')
        ");
        $existingRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($existingRows)) {
            $insert = $db->prepare("
                INSERT IGNORE INTO personnel_yearly_records
                (personnel_id, record_year, ipft_1st, ipft_2nd, ret, speed_march, cycle_1, cycle_2, cycle_3, cycle_4)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($existingRows as $row) {
                $insert->execute([
                    $row['personnel_id'],
                    $currentYear,
                    $row['ipft_1st'],
                    $row['ipft_2nd'],
                    $row['ret'],
                    $row['speed_march'],
                    $row['cycle_1'],
                    $row['cycle_2'],
                    $row['cycle_3'],
                    $row['cycle_4'],
                ]);
            }
        }

        // Update service_year for existing personnel_service records
        $db->exec("UPDATE personnel_service SET service_year = {$currentYear} WHERE service_year IS NULL");
    }

    public function down(PDO $db): void
    {
        $tableExists = $db->query("SHOW TABLES LIKE 'personnel_yearly_records'")->fetch();
        if ($tableExists) {
            $db->exec("DROP TABLE personnel_yearly_records");
        }

        $colServiceYear = $db->query("SHOW COLUMNS FROM personnel_service LIKE 'service_year'")->fetch();
        if ($colServiceYear) {
            $db->exec("ALTER TABLE personnel_service DROP COLUMN service_year");
        }
    }
}
