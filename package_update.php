<?php
declare(strict_types=1);

$root = __DIR__;
$withVendor = in_array('--with-vendor', $argv, true);
$outputZip = $root . ($withVendor ? '/update_full.zip' : '/update.zip');

if (file_exists($outputZip)) {
    unlink($outputZip);
}

$zip = new ZipArchive();
if ($zip->open($outputZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    echo "ERROR: Cannot create {$outputZip}\n";
    exit(1);
}

$blockedExtensions = ['exe', 'sh', 'bat', 'cmd', 'phar', 'cgi', 'pl', 'py', 'msi', 'com', 'sql'];
$excludePrefixes = [
    '.git',
    '.agents',
    'uploads',
    'config/db.php',
    'update.zip',
    'update_full.zip',
    'package_update.php',
    'test_zip',
    'docs',
    '.DS_Store',
    '__MACOSX'
];

if (!$withVendor) {
    $excludePrefixes[] = 'vendor';
}

$filesAdded = 0;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($iterator as $name => $file) {
    if (!$file->isFile()) {
        continue;
    }

    $filePath = $file->getRealPath();
    $relPath = ltrim(str_replace('\\', '/', substr($filePath, strlen($root))), '/');

    // Skip temp/lock/system files
    $base = basename($relPath);
    if ($base === '.DS_Store' || str_starts_with($base, '~$') || str_contains($relPath, '__MACOSX')) {
        continue;
    }

    // Check excludes
    $skip = false;
    foreach ($excludePrefixes as $prefix) {
        if ($relPath === $prefix || str_starts_with($relPath, $prefix . '/') || str_starts_with($relPath, $prefix)) {
            $skip = true;
            break;
        }
    }
    if ($skip) {
        continue;
    }

    $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));
    if (in_array($ext, $blockedExtensions, true)) {
        continue;
    }

    $zip->addFile($filePath, $relPath);
    $filesAdded++;
}

$zip->close();
$mode = $withVendor ? "full (with vendor)" : "app-only (clean & fast)";
echo "Update package created: {$outputZip} [{$mode}, {$filesAdded} files, " . round(filesize($outputZip) / 1024, 1) . " KB]\n";
