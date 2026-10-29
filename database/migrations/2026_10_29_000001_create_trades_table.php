<?php
declare(strict_types=1);

class CreateTradesTable implements MigrationInterface
{
    public function up(PDO $db): void
    {
        // 1. Create trades lookup table
        $db->exec("CREATE TABLE IF NOT EXISTS `trades` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(150) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 2. Insert default 'GD' trade
        $check = $db->query("SELECT id FROM `trades` WHERE `name` = 'GD'")->fetchColumn();
        if (!$check) {
            $db->exec("INSERT INTO `trades` (`name`) VALUES ('GD')");
            $gdId = (int)$db->lastInsertId();
        } else {
            $gdId = (int)$check;
        }

        // 3. Add trade_id FK column to personnel if not exists
        $col = $db->query("SHOW COLUMNS FROM `personnel` LIKE 'trade_id'")->fetch();
        if (!$col) {
            $db->exec("ALTER TABLE `personnel` ADD COLUMN `trade_id` INT DEFAULT NULL AFTER `rank_id`");
        }

        // 4. Set existing personnel to GD by default
        if ($gdId > 0) {
            $db->exec("UPDATE `personnel` SET `trade_id` = {$gdId} WHERE `trade_id` IS NULL OR `trade_id` = 0");
        }
    }

    public function down(PDO $db): void
    {
        $col = $db->query("SHOW COLUMNS FROM `personnel` LIKE 'trade_id'")->fetch();
        if ($col) {
            $db->exec("ALTER TABLE `personnel` DROP COLUMN `trade_id`");
        }

        $db->exec("DROP TABLE IF EXISTS `trades`");
    }
}

