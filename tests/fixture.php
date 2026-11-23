<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__) . '/app/Core/bootstrap.php';
use App\Core\Database;
if (!str_ends_with(config()['db_name'], '_test')) { fwrite(STDERR,"Usa una base terminada en _test.\n"); exit(1); }
$password = bin2hex(random_bytes(18)); $email = 'test-' . bin2hex(random_bytes(5)) . '@example.com';
$pdo = Database::connection();
$pdo->exec("UPDATE academia_config SET correo='contacto@example.com',responsable='Responsable de pruebas',politica='Datos inventados para pruebas de software. Conservación limitada al ensayo.' WHERE id=1 AND responsable=''");
$s = $pdo->prepare('INSERT INTO administradores (nombre,correo,password_hash) VALUES (?,?,?)');
$s->execute(['Administrador de prueba', $email, password_hash($password, PASSWORD_DEFAULT)]);
echo json_encode(['email'=>$email,'password'=>$password,'version'=>$pdo->query('SELECT VERSION()')->fetchColumn(),'php'=>PHP_VERSION]);
