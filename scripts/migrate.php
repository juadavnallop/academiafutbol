<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__) . '/app/Core/bootstrap.php';
try {
    $c=config();
    if (isset($argv[1]) && str_starts_with($argv[1],'--admin=')) {
        $user=substr($argv[1],8); $password=rtrim((string)fgets(STDIN),"\r\n");
        $db=new PDO("mysql:host={$c['db_host']};port={$c['db_port']};dbname={$c['db_name']};charset=utf8mb4",$user,$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    } else $db=\App\Core\Database::connection();
    \App\Services\Migrations::run($db);
    echo "Migración de integrantes completada. Cuentas y contenidos anteriores conservados. Respalda por separado var/keys/health.key.\n";
} catch (Throwable $e) { fwrite(STDERR,"Migración detenida. Comprueba respaldo, permisos DDL y clave privada. Código: ".$e->getCode()."\n"); exit(1); }
