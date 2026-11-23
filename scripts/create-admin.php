<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/Core/bootstrap.php';
use App\Models\Repository as R;
if ($argc !== 3 || !filter_var($argv[1], FILTER_VALIDATE_EMAIL) || strlen($argv[1]) > 190 || mb_strlen($argv[2]) > 100 || trim($argv[2]) === '') {
    fwrite(STDERR, "Uso: php scripts/create-admin.php correo nombre < contraseña_por_stdin\n"); exit(1);
}
// Leer desde stdin permite que PowerShell capture una SecureString sin eco ni historial.
$password = rtrim((string)fgets(STDIN), "\r\n");
if (strlen($password) < 12 || strlen($password) > 72) { fwrite(STDERR, "La contraseña debe tener entre 12 y 72 bytes.\n"); exit(1); }
try {
    R::execute('INSERT INTO administradores (correo,nombre,password_hash) VALUES (?,?,?)', [strtolower($argv[1]), $argv[2], password_hash($password, PASSWORD_DEFAULT)]);
    fwrite(STDOUT, "Administrador creado. No se muestra ni almacena la contraseña en texto plano.\n");
} catch (Throwable $e) { fwrite(STDERR, "No se creó la cuenta. Comprueba conexión, esquema y que el correo no exista.\n"); exit(1); }
