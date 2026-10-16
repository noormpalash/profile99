<?php
declare(strict_types=1);

class CreateCivilEducationsAndDefaultHsc implements MigrationInterface
{
    public function up(PDO $db): void
    {
        // 1. Create civil_educations table if not exists
        $db->exec("
            CREATE TABLE IF NOT EXISTS `civil_educations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(150) NOT NULL UNIQUE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. Insert default options: SSC, HSC, Degree, Masters
        $defaults = ['SSC', 'HSC', 'Degree / Equivalent', 'Masters / Equivalent'];
        $ins = $db->prepare("INSERT IGNORE INTO `civil_educations` (`name`) VALUES (?)");
        foreach ($defaults as $name) {
            $ins->execute([$name]);
        }

        // 3. Ensure personnel_education exists and populate all personnel with default 'HSC'
        $db->exec("
            INSERT INTO personnel_education (personnel_id, civil_education)
            SELECT p.id, 'HSC'
            FROM personnel p
            LEFT JOIN personnel_education pe ON p.id = pe.personnel_id
            WHERE pe.id IS NULL
        ");

        // 4. Update any existing blank education to HSC
        $db->exec("
            UPDATE personnel_education 
            SET civil_education = 'HSC' 
            WHERE civil_education IS NULL OR TRIM(civil_education) = ''
        ");
    }

    public function down(PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS `civil_educations`");
    }
}
