<?php
require_once __DIR__ . '/../classes/Auth.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    die('Method not allowed.');
}
Auth::verifyCsrf($_POST['csrf_token'] ?? null);
Auth::logout();
header('Location: login.php');
exit;
