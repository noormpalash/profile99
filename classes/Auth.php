<?php
// ============================================
// Auth: login, logout, role checks
// Roles: superadmin > admin > user
// ============================================

require_once __DIR__ . '/../config/db.php';

class Auth
{

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }
    }

    public static function attemptLogin(string $username, string $password): bool
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT u.id, u.username, u.name, u.password_hash, u.status, u.role_id, u.session_token, u.last_activity, r.name AS role
                               FROM users u JOIN roles r ON u.role_id = r.id
                               WHERE u.username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active')
            return false;
        if (!password_verify($password, $user['password_hash']))
            return false;


        self::start();
        $sessionToken = bin2hex(random_bytes(32));
        $updateStmt = $db->prepare("UPDATE users SET session_token = ?, last_activity = NOW() WHERE id = ?");
        $updateStmt->execute([$sessionToken, $user['id']]);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'] ?: $user['username'];
        $_SESSION['role'] = $user['role']; // superadmin | admin | user
        $_SESSION['session_token'] = $sessionToken;
        $_SESSION['just_logged_in'] = true;

        // Load permissions
        $perms = $db->prepare("SELECT p.name FROM permissions p JOIN role_permissions rp ON p.id = rp.permission_id WHERE rp.role_id = ?");
        $perms->execute([$user['role_id']]);
        $_SESSION['permissions'] = $perms->fetchAll(PDO::FETCH_COLUMN);

        require_once __DIR__ . '/Logger.php';
        Logger::log('login', null, ['username' => $username]);

        return true;
    }

    private static $permissionsCache = null;

    private static function loadPermissions(): array {
        if (self::$permissionsCache !== null) {
            return self::$permissionsCache;
        }
        $db = getDB();
        $perms = $db->prepare("SELECT p.name FROM permissions p JOIN role_permissions rp ON p.id = rp.permission_id JOIN users u ON rp.role_id = u.role_id WHERE u.id = ?");
        $perms->execute([$_SESSION['user_id']]);
        self::$permissionsCache = $perms->fetchAll(PDO::FETCH_COLUMN);
        return self::$permissionsCache;
    }

    public static function hasPermission(string $permission): bool
    {
        self::start();
        if (!isset($_SESSION['user_id'])) return false;
        $perms = self::loadPermissions();
        return in_array($permission, $perms);
    }

    public static function hasAnyPermission(array $permissions): bool
    {
        self::start();
        if (!isset($_SESSION['user_id'])) return false;
        $perms = self::loadPermissions();
        foreach ($permissions as $p) {
            if (in_array($p, $perms)) return true;
        }
        return false;
    }

    public static function logout(): void
    {
        self::start();
        if (isset($_SESSION['user_id'])) {
            require_once __DIR__ . '/Logger.php';
            Logger::log('logout', null, ['username' => $_SESSION['username'] ?? '']);
            $db = getDB();
            $stmt = $db->prepare("UPDATE users SET session_token = NULL WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        }
        session_unset();
        session_destroy();
    }

    private static function forceLogout(): void
    {
        self::start();
        session_unset();
        session_destroy();
    }

    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verifyCsrf(?string $token): void
    {
        self::start();
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (!is_string($token) || $token === '' || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            http_response_code(419);
            die('Invalid request token. Please refresh the page and try again.');
        }
    }

    // Session inactivity timeout in seconds (5 minutes)
    private const SESSION_TIMEOUT = 300;

    public static function isLoggedIn(): bool
    {
        self::start();
        if (!isset($_SESSION['user_id'])) return false;

        // Security: enforce 5-minute inactivity timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > self::SESSION_TIMEOUT) {
            self::logout();
            return false;
        }
        $_SESSION['last_activity'] = time();
        
        $db = getDB();
        $stmt = $db->prepare("SELECT session_token FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $tokenInDb = $stmt->fetchColumn();
        
        if (!$tokenInDb || !isset($_SESSION['session_token']) || $tokenInDb !== $_SESSION['session_token']) {
            if ($tokenInDb && isset($_SESSION['session_token']) && $tokenInDb !== $_SESSION['session_token']) {
                setcookie('logout_reason', 'new_device', time() + 60, '/');
            }
            self::forceLogout();
            return false;
        }

        if (!isset($_SESSION['last_activity_update']) || time() - $_SESSION['last_activity_update'] > 60) {
            $updateStmt = $db->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
            $updateStmt->execute([$_SESSION['user_id']]);
            $_SESSION['last_activity_update'] = time();
        }

        return true;
    }

    public static function getSessionTimeout(): int
    {
        return self::SESSION_TIMEOUT;
    }

    public static function role(): ?string
    {
        self::start();
        return $_SESSION['role'] ?? null;
    }

    // Call at the top of any page that requires login
    public static function requireLogin(): void
    {
        self::start();
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/pages/login.php');
            exit;
        }
    }

    // Call at the top of admin-only pages
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!in_array(self::role(), ['admin', 'superadmin', 'techadmin'])) {
            http_response_code(403);
            die('Access denied — admin privileges required.');
        }
    }

    // Call at the top of superadmin-only pages
    public static function requireSuperadmin(): void
    {
        self::requireLogin();
        if (!in_array(self::role(), ['superadmin', 'techadmin'])) {
            http_response_code(403);
            die('Access denied — superadmin privileges required.');
        }
    }

    // Call at the top of techadmin-only pages
    public static function requireTechadmin(): void
    {
        self::requireLogin();
        if (self::role() !== 'techadmin') {
            http_response_code(403);
            die('Access denied — techadmin privileges required.');
        }
    }

    public static function requirePermission(string $permission): void
    {
        self::requireLogin();
        if (!self::hasPermission($permission)) {
            http_response_code(403);
            die('Access denied — missing permission: ' . htmlspecialchars($permission));
        }
    }

    public static function requireAnyPermission(array $permissions): void
    {
        self::requireLogin();
        foreach ($permissions as $p) {
            if (self::hasPermission($p))
                return;
        }
        http_response_code(403);
        die('Access denied — missing one of required permissions.');
    }
}
