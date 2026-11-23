<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);
$header="-- Cancha Abierta: instalación limpia para phpMyAdmin / XAMPP.\n-- No importar sobre la base instalada. No contiene contraseñas ni integrantes.\n-- InnoDB; MariaDB 10.4 / MySQL 8.0.16+.\nCREATE DATABASE IF NOT EXISTS cancha_abierta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE cancha_abierta;\nSET NAMES utf8mb4;\nSET time_zone = '-05:00';\n\n";
$sql=$header.file_get_contents($root.'/database/schema.sql')."\n".file_get_contents($root.'/database/initial.sql');
$sql.="\nCREATE TABLE IF NOT EXISTS migraciones (version VARCHAR(80) PRIMARY KEY, aplicada_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;\nINSERT INTO migraciones (version) VALUES ('002_members'),('003_retire_seed');\n";
file_put_contents($root.'/database/instalar_xampp.sql',$sql);
echo "SQL de instalación generado: database/instalar_xampp.sql\n";
