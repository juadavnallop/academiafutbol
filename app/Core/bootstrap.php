<?php
declare(strict_types=1);
define('ROOT', dirname(__DIR__, 2));
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) require $file;
    }
});
date_default_timezone_set('America/Bogota');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
function config(): array {
    static $config;
    $path = getenv('CANCHA_CONFIG') ?: ROOT . '/config/' . (is_file(ROOT . '/config/local.php') ? 'local.php' : 'config.example.php');
    return $config ??= require $path;
}
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $route = 'inicio', array $params = []): string {
    return config()['base_path'] . '/index.php?' . http_build_query(['r' => $route] + $params);
}
function redirect(string $route, array $params = []): never { header('Location: ' . url($route, $params), true, 303); exit; }
function csrf(): string { return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">'; }
function view(string $template, array $data = []): void {
    extract($data, EXTR_SKIP);
    ob_start(); require ROOT . '/app/Views/' . $template . '.php'; $body = ob_get_clean();
    require ROOT . '/app/Views/layout.php';
}
function flash(string $message): void { $_SESSION['flash'] = $message; }
function input(string $key): string { $value = $_POST[$key] ?? ''; return is_string($value) ? trim($value) : ''; }
