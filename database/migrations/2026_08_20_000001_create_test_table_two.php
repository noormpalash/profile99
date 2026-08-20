<?php
declare(strict_types=1);

class CreateTestTableTwo implements MigrationInterface
{
    public function up(PDO $db): void
    {
        $db->exec("
            CREATE TABLE test_migration_feature_two (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down(PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS test_migration_feature_two;");
    }
}
