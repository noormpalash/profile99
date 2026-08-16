<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/Auth.php';

class Logger
{
    public static function log(string $actionType, ?int $targetId = null, array $details = []): void
    {
        Auth::start();
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) return;

        $db = getDB();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $detailsJson = json_encode($details);

        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action_type, target_personnel_id, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $actionType, $targetId, $detailsJson, $ip]);

        // Automatically delete logs older than 29 days (run ~10% of the time to save DB resources)
        if (rand(1, 10) === 1) {
            $db->exec("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 29 DAY)");
        }
    }
}
