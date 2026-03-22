<?php
/**
 * Authentication helper
 */
class Auth {
    const SESSION_KEY = 'tirana_admin';
    const TOKEN_TTL   = 7 * 24 * 3600; // 7 days

    public static function init(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(self::SESSION_KEY);
            session_start();
        }
    }

    /** Attempt login, return user array or null */
    public static function attempt(string $email, string $password): ?array {
        self::init();
        $user = DB::one('SELECT * FROM users WHERE email = ? AND is_active = 1', [$email]);
        if (!$user || !password_verify($password, $user['password'])) return null;

        // Store in session
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];

        // Update last login
        DB::run('UPDATE users SET last_login = ? WHERE id = ?', [date('Y-m-d H:i:s'), $user['id']]);

        // Audit
        DB::insert('audit_log', ['user_id'=>$user['id'],'action'=>'LOGIN','entity'=>'users','entity_id'=>$user['id'],'ip'=>$_SERVER['REMOTE_ADDR']??'']);

        return $user;
    }

    /** Check if user is logged in */
    public static function check(): bool {
        self::init();
        return !empty($_SESSION['user_id']);
    }

    /** Get current user */
    public static function user(): ?array {
        if (!self::check()) return null;
        return DB::one('SELECT id,name,email,role,restaurant_id FROM users WHERE id = ?', [$_SESSION['user_id']]);
    }

    /** Require login or redirect */
    public static function require(string $redirect = '/admin/'): void {
        self::init();
        if (!self::check()) {
            header('Location: ' . $redirect);
            exit;
        }
    }

    /** Require specific role */
    public static function requireRole(string $minRole, string $redirect = '/admin/'): void {
        self::require($redirect);
        $roles = ['editor'=>1, 'admin'=>2, 'superadmin'=>3];
        $userRole = $_SESSION['user_role'] ?? 'editor';
        if (($roles[$userRole]??0) < ($roles[$minRole]??99)) {
            http_response_code(403);
            die('<h1>403 Forbidden</h1><p>Insufficient permissions.</p>');
        }
    }

    /** Check role without blocking */
    public static function is(string $role): bool {
        return ($_SESSION['user_role'] ?? '') === $role;
    }

    public static function isSuperAdmin(): bool { return self::is('superadmin'); }
    public static function isAdmin():      bool { return in_array($_SESSION['user_role']??'', ['admin','superadmin']); }

    /** Logout */
    public static function logout(): void {
        self::init();
        session_destroy();
    }

    /** Get the restaurant_id restriction for non-superadmins */
    public static function restaurantScope(): ?int {
        $user = self::user();
        if (!$user) return null;
        if (in_array($user['role'], ['superadmin','admin'])) return null; // all restaurants
        return $user['restaurant_id'];
    }
}
