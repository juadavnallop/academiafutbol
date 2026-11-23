<?php
declare(strict_types=1);
namespace App\Services;
use PDO;
final class Migrations {
    public static function run(PDO $db): void {
        $db->exec('CREATE TABLE IF NOT EXISTS migraciones (version VARCHAR(80) PRIMARY KEY, aplicada_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
        $fields = array_column($db->query('SHOW COLUMNS FROM administradores')->fetchAll(PDO::FETCH_ASSOC),'Field');
        foreach ([
            'rol' => "ENUM('administrador','asistente','jugador') NOT NULL DEFAULT 'administrador'",
            'version_sesion' => 'INT NOT NULL DEFAULT 1',
            'debe_cambiar_password' => 'TINYINT NOT NULL DEFAULT 0',
        ] as $column => $definition) if (!in_array($column,$fields,true)) $db->exec("ALTER TABLE administradores ADD $column $definition");
        $db->exec(file_get_contents(ROOT . '/database/migrations/002_members.sql'));
        $enrollmentFields=array_column($db->query('SHOW COLUMNS FROM inscripciones')->fetchAll(PDO::FETCH_ASSOC),'Field');
        if(!in_array('verificacion',$enrollmentFields,true))$db->exec("ALTER TABLE inscripciones ADD verificacion VARCHAR(240) NOT NULL DEFAULT ''");
        $db->exec("INSERT IGNORE INTO migraciones (version) VALUES ('002_members')");
        if (!$db->query("SELECT version FROM migraciones WHERE version='003_retire_seed'")->fetch()) {
            $db->exec(file_get_contents(ROOT.'/database/migrations/003_retire_seed.sql'));
            $db->exec("INSERT INTO migraciones (version) VALUES ('003_retire_seed')");
        }
        $db->exec("UPDATE administradores SET correo='archivo@academia.invalid' WHERE correo='autor-demo@example.com' AND nombre='Autor de contenidos iniciales' AND activo=0");
        $directory = ROOT . '/var/keys';
        if (!is_dir($directory)) mkdir($directory,0700,true);
        $path = $directory . '/health.key';
        if (!is_file($path)) {
            if ((int)$db->query('SELECT COUNT(*) FROM fichas_salud')->fetchColumn() > 0) throw new \RuntimeException('Restaura la clave original de salud; no se generará otra.');
            $file = fopen($path,'x'); if (!$file) throw new \RuntimeException('No se pudo crear la clave.');
            fwrite($file,random_bytes(32)); fclose($file); chmod($path,0600);
        }
    }
}
