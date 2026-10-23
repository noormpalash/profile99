<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class DatabaseBackupService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getDB();
    }

    /**
     * Generates a SQL dump using PDO and returns the file path to the temporary backup file.
     * The caller is responsible for deleting the file after use.
     *
     * This approach avoids shell commands (mysqldump), making it cross-platform
     * compatible (Windows, Mac, Linux) without binary path issues.
     *
     * @throws RuntimeException if the backup fails
     */
    public function export(): string
    {
        $tempFile = $this->createTempBackupFile();

        $handle = fopen($tempFile, 'w');
        if ($handle === false) {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }
            throw new RuntimeException("Failed to open temporary file for writing.");
        }

        try {
            $dbName = DB_NAME;
            $timestamp = date('Y-m-d H:i:s');

            // Write header
            fwrite($handle, "-- Database Backup: {$dbName}\n");
            fwrite($handle, "-- Generated: {$timestamp}\n");
            fwrite($handle, "-- Server: " . DB_HOST . "\n");
            fwrite($handle, "-- -----------------------------------------------\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
            fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
            fwrite($handle, "SET AUTOCOMMIT = 0;\n");
            fwrite($handle, "START TRANSACTION;\n\n");

            // Get all tables
            $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $this->exportTable($handle, $table);
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fwrite($handle, "COMMIT;\n");

            fclose($handle);

            return $tempFile;
        } catch (\Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }
            throw new RuntimeException("Database export failed. Error: " . $e->getMessage());
        }
    }

    /**
     * Resolves a writable temporary file path for the backup.
     * Tries system temp dir, upload_tmp_dir, and project uploads/temp directory.
     *
     * @throws RuntimeException if no writable directory is found
     */
    private function createTempBackupFile(): string
    {
        $candidateDirs = [
            sys_get_temp_dir(),
            ini_get('upload_tmp_dir'),
            __DIR__ . '/../uploads/temp',
            __DIR__ . '/../uploads',
            '/tmp',
        ];

        $candidateDirs = array_values(array_unique(array_filter($candidateDirs)));

        foreach ($candidateDirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if (!is_dir($dir) || !is_writable($dir)) {
                continue;
            }

            $tempFile = @tempnam($dir, 'db_backup_');
            if ($tempFile !== false) {
                @unlink($tempFile);
                $sqlFile = $tempFile . '.sql';
                $handle = @fopen($sqlFile, 'w');
                if ($handle !== false) {
                    fclose($handle);
                    return $sqlFile;
                }
            }

            $sqlFile = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'db_backup_' . bin2hex(random_bytes(8)) . '.sql';
            $handle = @fopen($sqlFile, 'w');
            if ($handle !== false) {
                fclose($handle);
                return $sqlFile;
            }
        }

        throw new RuntimeException("Failed to create temporary file for backup. No writable temporary directory found.");
    }

    /**
     * Exports a single table's structure and data.
     *
     * @param resource $handle File handle to write to
     * @param string $table Table name
     */
    private function exportTable($handle, string $table): void
    {
        $quotedTable = $this->pdo->quote($table);

        // Table structure
        fwrite($handle, "-- -----------------------------------------------\n");
        fwrite($handle, "-- Table structure for `{$table}`\n");
        fwrite($handle, "-- -----------------------------------------------\n\n");

        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

        $createStmt = $this->pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
        if (isset($createStmt['Create Table'])) {
            fwrite($handle, $createStmt['Create Table'] . ";\n\n");
        }

        // Table data
        $countStmt = $this->pdo->query("SELECT COUNT(*) FROM `{$table}`");
        $rowCount = (int) $countStmt->fetchColumn();

        if ($rowCount === 0) {
            fwrite($handle, "-- No data in `{$table}`\n\n");
            return;
        }

        fwrite($handle, "-- -----------------------------------------------\n");
        fwrite($handle, "-- Data for `{$table}` ({$rowCount} rows)\n");
        fwrite($handle, "-- -----------------------------------------------\n\n");

        // Get column names
        $columns = [];
        $colStmt = $this->pdo->query("SHOW COLUMNS FROM `{$table}`");
        while ($col = $colStmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = '`' . $col['Field'] . '`';
        }
        $columnList = implode(', ', $columns);

        // Export in batches to handle large tables
        $batchSize = 500;
        $offset = 0;

        while ($offset < $rowCount) {
            $rows = $this->pdo->query(
                "SELECT * FROM `{$table}` LIMIT {$batchSize} OFFSET {$offset}"
            )->fetchAll(PDO::FETCH_NUM);

            if (empty($rows)) {
                break;
            }

            $values = [];
            foreach ($rows as $row) {
                $escapedValues = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $escapedValues[] = 'NULL';
                    } else {
                        $escapedValues[] = $this->pdo->quote((string) $value);
                    }
                }
                $values[] = '(' . implode(', ', $escapedValues) . ')';
            }

            fwrite($handle, "INSERT INTO `{$table}` ({$columnList}) VALUES\n");
            fwrite($handle, implode(",\n", $values) . ";\n\n");

            $offset += $batchSize;
        }
    }

    /**
     * Imports an SQL file into the database.
     *
     * Uses PDO exec() to run the SQL statements, making it cross-platform
     * compatible without requiring the mysql binary.
     *
     * @param string $filePath The path to the SQL file to import
     * @throws RuntimeException if the import fails
     * @throws InvalidArgumentException if the file is not found
     */
    public function import(string $filePath): void
    {
        if (!is_file($filePath)) {
            throw new InvalidArgumentException("SQL file not found.");
        }

        $sql = file_get_contents($filePath);
        if ($sql === false) {
            throw new RuntimeException("Failed to read SQL file.");
        }

        // Security check: reject SQL files containing dangerous administrative or exfiltration commands
        if (preg_match('/\b(GRANT\s+ALL|CREATE\s+USER|DROP\s+DATABASE|ALTER\s+USER|INTO\s+OUTFILE|LOAD_FILE|SHUTDOWN)\b/i', $sql, $matches)) {
            throw new RuntimeException("Import aborted: disallowed SQL statement detected (" . htmlspecialchars($matches[1]) . ").");
        }

        try {
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $this->pdo->exec($sql);
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        } catch (\PDOException $e) {
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            error_log("Database import PDO error: " . $e->getMessage());
            throw new RuntimeException("Database import failed due to a SQL error. Check server logs for details.");
        }
    }
}
