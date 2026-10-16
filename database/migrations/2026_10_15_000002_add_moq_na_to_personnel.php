<?php
declare(strict_types=1);

class AddMoqNaToPersonnel implements MigrationInterface
{
    public function up(PDO $db): void
    {
        $col = $db->query("SHOW COLUMNS FROM personnel LIKE 'moq_na'")->fetch();
        if (!$col) {
            $db->exec("ALTER TABLE personnel ADD COLUMN moq_na TINYINT(1) NOT NULL DEFAULT 0");
        }
    }

    public function down(PDO $db): void
    {
        $col = $db->query("SHOW COLUMNS FROM personnel LIKE 'moq_na'")->fetch();
        if ($col) {
            $db->exec("ALTER TABLE personnel DROP COLUMN moq_na");
        }
    }
}
