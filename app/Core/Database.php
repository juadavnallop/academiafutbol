<?php
declare(strict_types=1);
namespace App\Core;
use PDO;
final class Database {
    public static function connection(): PDO {
        static $pdo;
        if (!$pdo) {
            $c = config();
            $pdo = new PDO("mysql:host={$c['db_host']};port={$c['db_port']};dbname={$c['db_name']};charset=utf8mb4", $c['db_user'], $c['db_password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("SET time_zone = '-05:00'");
        }
        return $pdo;
    }
}
