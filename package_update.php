<?php
declare(strict_types=1);

$root = __DIR__;
$outputZip = $root . '/update.zip';

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
    'package_update.php',
    'test_zip',
    '.DS_Store',
    '__MACOSX'
];

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

    if (basename($relPath) === '.DS_Store' || str_contains($relPath, '__MACOSX')) {
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
echo "Update package created successfully: {$outputZip} ({$filesAdded} files)\n";

