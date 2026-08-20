<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/MigrationInterface.php';

class MigrationService
{
    private PDO $db;
    private string $migrationsDir;

    public function __construct()
    {
        $this->db = getDB();
        $this->migrationsDir = __DIR__ . '/../database/migrations/';
        $this->initMigrationsTable();
    }

    private function initMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $this->db->exec($sql);
    }

    private function getExecutedMigrations(): array
    {
        $stmt = $this->db->query("SELECT migration_name FROM migrations");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function runAll(): array
    {
        $executed = $this->getExecutedMigrations();
        $files = glob($this->migrationsDir . '*.php');
        $run = [];

        if ($files === false) {
            return $run;
        }

        sort($files);

        foreach ($files as $file) {
            $filename = basename($file);
            
            if (in_array($filename, $executed, true)) {
                continue;
            }

            require_once $file;
            $className = $this->getClassNameFromFilename($filename);
            
            if (!class_exists($className)) {
                throw new RuntimeException("Migration class {$className} not found in {$file}");
            }

            $migration = new $className();
            if (!$migration instanceof MigrationInterface) {
                throw new RuntimeException("Class {$className} must implement MigrationInterface");
            }

            $this->db->beginTransaction();
            try {
                $migration->up($this->db);
                
                $stmt = $this->db->prepare("INSERT INTO migrations (migration_name) VALUES (:name)");
                $stmt->execute(['name' => $filename]);
                
                if ($this->db->inTransaction()) {
                    $this->db->commit();
                }
                $run[] = $filename;
            } catch (Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                throw new RuntimeException("Failed to run migration {$filename}: " . $e->getMessage());
            }
        }

        return $run;
    }

    private function getClassNameFromFilename(string $filename): string
    {
        // Example: 2026_08_20_000000_create_test_table.php -> CreateTestTable
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $parts = explode('_', $name);
        // Remove date and time parts (e.g. 2026, 08, 20, 000000)
        $parts = array_slice($parts, 4);
        
        $className = '';
        foreach ($parts as $part) {
            $className .= ucfirst(strtolower($part));
        }
        
        return $className;
    }
}
