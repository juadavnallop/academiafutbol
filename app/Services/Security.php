<?php
declare(strict_types=1);
namespace App\Services;
use App\Models\Repository;
final class Security {
    private static ?array $current = null;
    public static function start(): void {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        $sessionPath = ROOT . '/var/sessions';
        if (!is_dir($sessionPath) && !mkdir($sessionPath, 0700, true) && !is_dir($sessionPath)) throw new \RuntimeException('Session directory unavailable');
        session_save_path($sessionPath);
        session_name('cancha_session');
        session_set_cookie_params(['httponly' => true, 'secure' => config()['session_secure'], 'samesite' => 'Lax', 'path' => (config()['base_path'] ?: '') . '/']);
        if (!session_start()) throw new \RuntimeException('Session unavailable');
        if (isset($_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > 1800) {
            $_SESSION = []; session_regenerate_id(true);
        }
        $_SESSION['last_seen'] = time();
        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
        header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        header('Cache-Control: no-store');
    }
    public static function verifyCsrf(): bool {
        return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], input('csrf'));
    }
    public static function requireAdmin(): void {
        self::requireRoles(['administrador']);
    }
    public static function user(): ?array {
        if (self::$current !== null) return self::$current;
        if (empty($_SESSION['user_id'])) return null;
        $user=Repository::one('SELECT id,nombre,correo,rol,activo,version_sesion,debe_cambiar_password FROM administradores WHERE id=?',[$_SESSION['user_id']]);
        if (!$user || !$user['activo'] || (int)$user['version_sesion'] !== (int)($_SESSION['version_sesion'] ?? 0)) {
            unset($_SESSION['user_id'],$_SESSION['admin_id']); return null;
        }
        return self::$current=$user;
    }
    public static function requireRoles(array $roles): void {
        $user=self::user(); if (!$user) redirect('login');
        if (!in_array($user['rol'],$roles,true)) self::deny();
        $route=$_GET['r'] ?? '';
        if ($user['debe_cambiar_password'] && !in_array($route,['cuenta','logout'],true)) redirect('cuenta');
    }
    public static function deny(): never {
        http_response_code(403); view('message',['title'=>'Acceso no autorizado','message'=>'Tu cuenta no tiene permiso para consultar o modificar esta información.']); exit;
    }
    public static function login(array $user): void {
        session_regenerate_id(true);
        $_SESSION=['user_id'=>$user['id'],'version_sesion'=>$user['version_sesion'],'csrf'=>bin2hex(random_bytes(32)),'last_seen'=>time()];
        if ($user['rol']==='administrador') { $_SESSION['admin_id']=$user['id']; $_SESSION['admin_name']=$user['nombre']; }
        self::$current=null;
    }
    public static function home(): string {
        return match (self::user()['rol'] ?? '') { 'administrador'=>'admin','asistente'=>'equipo',default=>'mi-ficha' };
    }
    public static function logout(): void {
        $_SESSION = [];
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
        session_destroy();
    }
}
