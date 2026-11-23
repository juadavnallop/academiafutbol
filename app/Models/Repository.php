<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class Repository {
    public static function all(string $sql, array $params = []): array {
        $statement = Database::connection()->prepare($sql); $statement->execute($params); return $statement->fetchAll();
    }
    public static function one(string $sql, array $params = []): ?array { return self::all($sql, $params)[0] ?? null; }
    public static function execute(string $sql, array $params = []): int {
        $statement = Database::connection()->prepare($sql); $statement->execute($params); return $statement->rowCount();
    }
    public static function save(string $module, array $data, int $id): void {
        // Identificadores SQL exclusivamente definidos por el servidor, nunca por el formulario.
        $definition = \App\Services\Catalog::modules()[$module];
        $table = $definition['table'];
        $allowed = array_keys($definition['fields']);
        if ($module === 'publicaciones') $allowed[] = 'administrador_id';
        $data = array_intersect_key($data, array_flip($allowed));
        $columns = array_keys($data);
        if ($id) {
            self::execute('UPDATE ' . $table . ' SET ' . implode(', ', array_map(fn($c) => "$c = ?", $columns)) . ' WHERE id = ?', [...array_values($data), $id]);
        } else {
            self::execute('INSERT INTO ' . $table . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')', array_values($data));
        }
    }
}
