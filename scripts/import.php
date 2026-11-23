<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__) . '/app/Core/bootstrap.php';
if ($argc !== 2 || !in_array($argv[1], ['schema', 'initial'], true)) { fwrite(STDERR, "Uso: php scripts/import.php schema|initial\n"); exit(1); }
try {
    $db = \App\Core\Database::connection();
    if ($argv[1] === 'schema' && $db->query('SHOW TABLES')->fetch()) throw new RuntimeException('El esquema requiere una base vacía.');
    if ($argv[1] === 'initial') {
        if ($db->query('SELECT id FROM contenidos_institucionales LIMIT 1')->fetch()) throw new RuntimeException('Ya existen contenidos institucionales.');
    }
    $db->exec(file_get_contents(ROOT . '/database/' . $argv[1] . '.sql'));
    if ($argv[1] === 'schema') \App\Services\Migrations::run($db);
    echo "Importación completada: {$argv[1]}.\n";
} catch (Throwable $e) { fwrite(STDERR, "Importación detenida. Verifica conexión, permisos, base vacía y orden README. Código: " . $e->getCode() . "\n"); exit(1); }
