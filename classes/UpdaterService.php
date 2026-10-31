<?php
declare(strict_types=1);

require_once __DIR__ . '/MigrationService.php';

class UpdaterService
{
    private string $appRoot;
    private string $tempExtractDir;
    private array $safePaths;

    public function __construct()
    {
        $this->appRoot = realpath(__DIR__ . '/../');
        $sysTmp = rtrim(sys_get_temp_dir(), '/\\');
        $this->tempExtractDir = $sysTmp . '/profile99_update_' . time() . '_' . mt_rand(1000, 9999);
        
        // Paths relative to appRoot that should never be overwritten
        $this->safePaths = [
            'config/db.php',
            'config/.htaccess',
            'classes/.htaccess',
            'uploads',
            '.git',
            '.gitignore',
            '.env',
            '.agents',
            'skills-lock.json',
            '__MACOSX',
            '.DS_Store'
        ];
    }

    public function updateFromZip(string $zipFilePath): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException("ZipArchive extension is missing. Cannot process update.");
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            throw new RuntimeException("Failed to open ZIP file.");
        }

        // Security check: validate every file inside ZIP before extraction
        $blockedExtensions = ['exe', 'sh', 'bat', 'cmd', 'phar', 'cgi', 'pl', 'py', 'msi', 'com'];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if ($entry === false) continue;

            // Zip Slip protection: block directory traversal and absolute paths
            if (str_contains($entry, '..') || str_starts_with($entry, '/') || str_starts_with($entry, '\\')) {
                $zip->close();
                throw new RuntimeException("Malicious path traversal detected in ZIP archive.");
            }

            // Skip internal metadata, Mac resource forks, and agent tooling folders
            if (str_starts_with($entry, '__MACOSX/') || str_contains($entry, '/__MACOSX/') || str_contains($entry, '.agents/') || str_contains($entry, '.git/')) {
                continue;
            }

            // Block dangerous binary and shell extensions
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (in_array($ext, $blockedExtensions, true)) {
                $zip->close();
                throw new RuntimeException("Disallowed executable file type in update ZIP: " . htmlspecialchars($ext));
            }
        }

        if (!@mkdir($this->tempExtractDir, 0777, true) && !is_dir($this->tempExtractDir)) {
            $this->tempExtractDir = $this->appRoot . '/uploads/temp_update_' . time();
            if (!@mkdir($this->tempExtractDir, 0777, true) && !is_dir($this->tempExtractDir)) {
                throw new RuntimeException("Failed to create temporary extraction directory.");
            }
        }

        try {
            $zip->extractTo($this->tempExtractDir);
            $zip->close();
            
            // Check if ZIP has a root folder or files directly.
            // Ignore OS metadata and tool folders when checking root items
            $extractItems = array_values(array_filter(
                scandir($this->tempExtractDir),
                fn($item) => !in_array($item, ['.', '..', '__MACOSX', '.DS_Store', '.agents', '.git'], true)
            ));
            $sourceDir = $this->tempExtractDir;
            if (count($extractItems) === 1) {
                $singleItem = reset($extractItems);
                if (is_dir($this->tempExtractDir . '/' . $singleItem)) {
                    $sourceDir = $this->tempExtractDir . '/' . $singleItem;
                }
            }
            $this->copyFilesRecursively($sourceDir, $this->appRoot);
            
            // Run migrations after copying new files
            $migrationService = new MigrationService();
            $migrationsRun = $migrationService->runAll();
            
            $this->removeDirectory($this->tempExtractDir);
            
            return $migrationsRun;
        } catch (Throwable $e) {
            $this->removeDirectory($this->tempExtractDir);
            throw new RuntimeException("Update failed: " . $e->getMessage());
        }
    }

    private function copyFilesRecursively(string $src, string $dst, string $relativePath = ''): void
    {
        $dir = opendir($src);
        if ($dir === false) {
            return;
        }

        if (!is_dir($dst)) {
            @mkdir($dst, 0777, true);
            @chmod($dst, 0777);
        }

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $currentSrc = $src . '/' . $file;
            $currentDst = $dst . '/' . $file;
            $currentRel = $relativePath === '' ? $file : $relativePath . '/' . $file;

            // Skip safe paths
            if ($this->isSafePath($currentRel)) {
                continue;
            }

            if (is_dir($currentSrc)) {
                $this->copyFilesRecursively($currentSrc, $currentDst, $currentRel);
            } else {
                if (file_exists($currentDst)) {
                    @chmod($currentDst, 0666);
                }
                if (!@copy($currentSrc, $currentDst)) {
                    @unlink($currentDst);
                    if (!@copy($currentSrc, $currentDst)) {
                        $err = error_get_last();
                        throw new RuntimeException("Failed to copy file '{$currentRel}': " . ($err['message'] ?? 'permission denied'));
                    }
                }
                @chmod($currentDst, 0666);
            }
        }
        closedir($dir);
    }

    private function isSafePath(string $relPath): bool
    {
        foreach ($this->safePaths as $safePath) {
            if (str_starts_with($relPath, $safePath)) {
                return true;
            }
        }
        return false;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object !== '.' && $object !== '..') {
                $path = $dir . '/' . $object;
                if (is_dir($path) && !is_link($path)) {
                    $this->removeDirectory($path);
                } else {
                    unlink($path);
                }
            }
        }
        rmdir($dir);
    }
}
