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

    public static function getCustomAbsentStatuses(): array {
        self::load();
        $raw = self::$settings['absent_custom_labels_json'] ?? '';
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && !empty($decoded)) {
                $result = [];
                foreach ($decoded as $item) {
                    if (is_array($item) && !empty($item['label'])) {
                        $k = !empty($item['key']) ? trim((string)$item['key']) : self::slugifyCustomStatus(trim((string)$item['label']));
                        $result[$k] = trim((string)$item['label']);
                    } elseif (is_string($item) && trim($item) !== '') {
                        $k = self::slugifyCustomStatus(trim($item));
                        $result[$k] = trim($item);
                    }
                }
                if (!empty($result)) {
                    return $result;
                }
            }
        }

        $legacy = trim((string)(self::$settings['absent_custom_label'] ?? 'Others'));
        if ($legacy === '') {
            $legacy = 'Others';
        }
        return ['others' => $legacy];
    }

    public static function setCustomAbsentStatuses(array $statuses): void {
        $cleaned = [];
        $seenKeys = [];
        $firstLabel = '';

        foreach ($statuses as $idx => $val) {
            $label = '';
            $key = '';
            if (is_array($val)) {
                $label = trim((string)($val['label'] ?? ''));
                $key = trim((string)($val['key'] ?? ''));
            } else {
                $label = trim((string)$val);
            }

            if ($label === '') {
                continue;
            }

            if ($key === '') {
                if ($idx === 0 || count($cleaned) === 0) {
                    $key = 'others';
                } else {
                    $key = self::slugifyCustomStatus($label);
                }
            }

            $baseKey = $key;
            $counter = 1;
            while (isset($seenKeys[$key])) {
                $key = $baseKey . '_' . $counter;
                $counter++;
            }
            $seenKeys[$key] = true;

            if ($firstLabel === '') {
                $firstLabel = $label;
            }

            $cleaned[] = [
                'key' => $key,
                'label' => $label
            ];
        }

        if (empty($cleaned)) {
            $cleaned = [['key' => 'others', 'label' => 'Others']];
            $firstLabel = 'Others';
        }

        self::set('absent_custom_labels_json', json_encode($cleaned));
        self::set('absent_custom_label', $firstLabel);
    }

    private static function slugifyCustomStatus(string $name): string {
        $slug = strtolower((string)preg_replace('/[^a-zA-Z0-9]+/', '_', trim($name)));
        $slug = trim($slug, '_');
        if ($slug === '' || $slug === 'others') {
            $slug = 'custom_' . substr(md5($name . (string)microtime(true)), 0, 6);
        } elseif (!str_starts_with($slug, 'custom_') && in_array($slug, ['active', 'on_leave', 'cmh', 'trg', 'cmd', 'att', 'goc_gd', 'suspend', 'osl', 'awol'], true)) {
            $slug = 'custom_' . $slug;
        }
        return $slug;
    }
}
