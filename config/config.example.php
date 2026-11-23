<?php
// Copiar a config/local.php. No subir ese archivo al repositorio.
return [
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'cancha_abierta',
    'db_user' => 'cancha_web',
    'db_password' => '',
    'base_path' => '/cancha-abierta/public', // Vacío para VirtualHost o php -S; sin barra final.
    'session_secure' => false, // true al servir por HTTPS.
];
