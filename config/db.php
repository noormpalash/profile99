<?php
// ============================================
// Database connection (edit if your XAMPP MySQL user/password differs)
// ============================================

// Use 127.0.0.1 instead of 'localhost' to force TCP (avoids socket issues when running CLI)
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'army_personnel_db');
define('DB_USER', 'root');
define('DB_PASS', '');          // default XAMPP MySQL password is empty

define('UPLOAD_DIR', __DIR__ . '/../uploads/personnel/');

$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
if ($docRoot === '' && !empty($_SERVER['SCRIPT_FILENAME']) && !empty($_SERVER['SCRIPT_NAME'])) {
    $scriptFile = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']));
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    if ($scriptFile !== false && str_ends_with($scriptFile, $scriptName)) {
        $docRoot = substr($scriptFile, 0, -strlen($scriptName));
        $docRoot = rtrim($docRoot, '/');
    }
}
$appRoot = realpath(__DIR__ . '/..') ?: '';
$baseUrl = '';
if ($docRoot !== '' && $appRoot !== '') {
    $docRootNormalized = str_replace(['\\', '/'], '/', rtrim($docRoot, '/\\'));
    $appRootNormalized = str_replace(['\\', '/'], '/', rtrim($appRoot, '/\\'));
    if (stripos($appRootNormalized, $docRootNormalized) === 0) {
        $baseUrl = substr($appRootNormalized, strlen($docRootNormalized));
        $baseUrl = $baseUrl === '' ? '' : '/' . ltrim($baseUrl, '/');
    }
}
define('BASE_URL', $baseUrl);
define('UPLOAD_URL', BASE_URL . '/uploads/personnel/');
define('MAX_PHOTO_SIZE', 2 * 1024 * 1024); // 2MB
define('DEFAULT_PHOTO_DATA_URI', "data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 120 120%27%3E%3Crect width=%27120%27 height=%27120%27 rx=%2760%27 fill=%27%23e3e8de%27/%3E%3Ccircle cx=%2760%27 cy=%2744%27 r=%2722%27 fill=%27%234a5d23%27/%3E%3Cpath d=%27M24 104c6-22 24-34 36-34s30 12 36 34%27 fill=%27%234a5d23%27/%3E%3C/svg%3E");

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please contact the administrator.");
        }
    }
    return $pdo;
}

function personnelPhotoUrl(?string $filename): string {
    $filename = trim((string)$filename);
    if ($filename !== '') {
        $path = UPLOAD_DIR . $filename;
        if (is_file($path)) {
            return UPLOAD_URL . rawurlencode($filename);
        }
    }

    return DEFAULT_PHOTO_DATA_URI;
}

function bangladeshDistricts(): array {
    return [
        'Bagerhat', 'Bandarban', 'Barguna', 'Barishal', 'Bhola', 'Bogura', 'Brahmanbaria', 'Chandpur',
        'Chattogram', 'Chuadanga', 'Cox\'s Bazar', 'Cumilla', 'Dhaka', 'Dinajpur', 'Faridpur', 'Feni',
        'Gaibandha', 'Gazipur', 'Gopalganj', 'Habiganj', 'Jamalpur', 'Jashore', 'Jhalokathi', 'Jhenaidah',
        'Joypurhat', 'Khagrachhari', 'Khulna', 'Kishoreganj', 'Kurigram', 'Kushtia', 'Lakshmipur', 'Lalmonirhat',
        'Madaripur', 'Magura', 'Manikganj', 'Meherpur', 'Moulvibazar', 'Munshiganj', 'Mymensingh', 'Naogaon',
        'Narail', 'Narayanganj', 'Narsingdi', 'Natore', 'Netrokona', 'Nilphamari', 'Noakhali', 'Pabna',
        'Panchagarh', 'Patuakhali', 'Pirojpur', 'Rajbari', 'Rajshahi', 'Rangamati', 'Rangpur', 'Satkhira',
        'Shariatpur', 'Sherpur', 'Sirajganj', 'Sunamganj', 'Sylhet', 'Tangail', 'Thakurgaon'
    ];
}
