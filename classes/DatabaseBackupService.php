<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class DatabaseBackupService
{
    private string $host;
    private string $dbName;
    private string $user;
    private string $password;
    
    private string $mysqldumpPath;
    private string $mysqlPath;

    public function __construct()
    {
        $this->host = DB_HOST;
        $this->dbName = DB_NAME;
        $this->user = DB_USER;
        $this->password = DB_PASS;
        
        $possibleMysqldumpPaths = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe', // Windows XAMPP
            '/Applications/XAMPP/xamppfiles/bin/mysqldump', // Mac XAMPP
            '/opt/lampp/bin/mysqldump', // Linux XAMPP
        ];
        
        $possibleMysqlPaths = [
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            '/Applications/XAMPP/xamppfiles/bin/mysql',
            '/opt/lampp/bin/mysql',
        ];
        
        $this->mysqldumpPath = 'mysqldump'; // Fallback to standard PATH
        foreach ($possibleMysqldumpPaths as $path) {
            if (is_file($path)) {
                $this->mysqldumpPath = $path;
                break;
            }
        }
        
        $this->mysqlPath = 'mysql'; // Fallback to standard PATH
        foreach ($possibleMysqlPaths as $path) {
            if (is_file($path)) {
                $this->mysqlPath = $path;
                break;
            }
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
        
        // On Windows, escapeshellcmd might cause issues if not used correctly, 
        // wrapping executable path in quotes is usually safer.
        $cmdBinary = escapeshellarg($this->mysqldumpPath);
        
        $command = "{$cmdBinary} -h {$host} -u {$user}";
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
        
        $cmdBinary = escapeshellarg($this->mysqlPath);
        
        $command = "{$cmdBinary} -h {$host} -u {$user}";
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
