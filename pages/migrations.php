<?php
declare(strict_types=1);
require_once __DIR__ . '/../classes/MigrationService.php';

$message = '';
$runList = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $service = new MigrationService();
        $runList = $service->runAll();
        if (empty($runList)) {
            $message = "No new migrations to run.";
        } else {
            $message = "Successfully ran: " . implode(', ', $runList);
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Migrations</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .btn { padding: 10px 15px; background: #007bff; color: #fff; border: none; cursor: pointer; border-radius: 4px; }
        .msg { margin-top: 15px; padding: 10px; background: #f4f4f4; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <h1>Database Migrations</h1>
    <p>Click the button below to apply new migrations.</p>
    
    <form method="post">
        <button type="submit" class="btn">Run Migrations</button>
    </form>

    <?php if ($message !== ''): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
</body>
</html>
