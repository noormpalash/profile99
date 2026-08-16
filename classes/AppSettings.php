<?php
require_once __DIR__ . '/../config/db.php';

class AppSettings {
    private static ?array $settings = null;

    private static function load(): void {
        if (self::$settings === null) {
            $db = getDB();
            $rows = $db->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
            self::$settings = $rows !== false ? $rows : [];
        }
    }

    public static function get(string $key, string $default = ''): string {
        self::load();
        return self::$settings[$key] ?? $default;
    }

    public static function getAll(): array {
        self::load();
        return self::$settings;
    }

    public static function set(string $key, string $value): void {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
        
        // Update local cache
        self::load();
        self::$settings[$key] = $value;
    }
}
