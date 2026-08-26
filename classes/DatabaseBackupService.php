<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class DatabaseBackupService
{
    private string $host;
    private string $dbName;
    private string $user;
    private string $password;
    
    // We try to find the mysqldump/mysql binaries, prioritizing XAMPP path
    private string $mysqldumpPath = '/Applications/XAMPP/xamppfiles/bin/mysqldump';
    private string $mysqlPath = '/Applications/XAMPP/xamppfiles/bin/mysql';

    public function __construct()
    {
        $this->host = DB_HOST;
        $this->dbName = DB_NAME;
        $this->user = DB_USER;
        $this->password = DB_PASS;
        
        // Fallback to standard PATH if XAMPP paths don't exist
        if (!is_executable($this->mysqldumpPath)) {
            $this->mysqldumpPath = 'mysqldump';
        }
        if (!is_executable($this->mysqlPath)) {
            $this->mysqlPath = 'mysql';
        }
    }

    /**
     * Generates a SQL dump and returns the file path to the temporary backup file.
     * The caller is responsible for deleting the file after use.
     *
     * @throws RuntimeException if the backup fails
     */
    public function export(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'db_backup_') . '.sql';
        
        $host = escapeshellarg($this->host);
        $user = escapeshellarg($this->user);
        $dbName = escapeshellarg($this->dbName);
        
        // Build the command safely
        $command = escapeshellcmd($this->mysqldumpPath) . " -h {$host} -u {$user}";
        if ($this->password !== '') {
            $password = escapeshellarg($this->password);
            $command .= " -p{$password}";
        }
        
        $command .= " {$dbName} > " . escapeshellarg($tempFile);
        
        exec($command . ' 2>&1', $output, $resultCode);
        
        if ($resultCode !== 0) {
            if (is_file($tempFile)) {
                unlink($tempFile);
            }
            throw new RuntimeException("Database export failed. Error: " . implode("\n", $output));
        }
        
        return $tempFile;
    }

    /**
     * Imports an SQL file into the database.
     *
     * @param string $filePath The path to the SQL file to import
     * @throws RuntimeException if the import fails
     */
    public function import(string $filePath): void
    {
        if (!is_file($filePath)) {
            throw new InvalidArgumentException("SQL file not found.");
        }
        
        $host = escapeshellarg($this->host);
        $user = escapeshellarg($this->user);
        $dbName = escapeshellarg($this->dbName);
        
        // Build the command safely
        $command = escapeshellcmd($this->mysqlPath) . " -h {$host} -u {$user}";
        if ($this->password !== '') {
            $password = escapeshellarg($this->password);
            $command .= " -p{$password}";
        }
        
        $command .= " {$dbName} < " . escapeshellarg($filePath);
        
        exec($command . ' 2>&1', $output, $resultCode);
        
        if ($resultCode !== 0) {
            throw new RuntimeException("Database import failed. Error: " . implode("\n", $output));
        }
    }
}
