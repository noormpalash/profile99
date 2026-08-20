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
        $this->tempExtractDir = $this->appRoot . '/uploads/temp_update_' . time();
        
        // Paths relative to appRoot that should never be overwritten
        $this->safePaths = [
            'config/db.php',
            'uploads'
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

        if (!mkdir($this->tempExtractDir, 0777, true) && !is_dir($this->tempExtractDir)) {
            throw new RuntimeException("Failed to create temporary extraction directory.");
        }

        try {
            $zip->extractTo($this->tempExtractDir);
            $zip->close();
            
            // Check if ZIP has a root folder or files directly.
            // Often ZIPs from github have a single root folder. We'll handle direct files.
            $this->copyFilesRecursively($this->tempExtractDir, $this->appRoot);
            
            // Run migrations after copying new files
            $migrationService = new MigrationService();
            $migrationsRun = $migrationService->runAll();
            
            $this->removeDirectory($this->tempExtractDir);
            
            return $migrationsRun;
        } catch (Exception $e) {
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
            mkdir($dst, 0777, true);
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
                copy($currentSrc, $currentDst);
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
