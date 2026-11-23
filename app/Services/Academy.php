<?php
declare(strict_types=1);
namespace App\Services;
use App\Models\Repository as R;
final class Academy {
    public static function settings(): array {
        static $settings;
        return $settings ??= R::one('SELECT * FROM academia_config WHERE id=1');
    }
    public static function ready(): bool {
        $s=self::settings(); return $s['responsable'] !== '' && $s['correo'] !== '' && $s['politica'] !== '';
    }
    public static function audit(string $action, ?int $player=null): void {
        R::execute('INSERT INTO auditoria (actor_id,jugador_id,accion) VALUES (?,?,?)',[Security::user()['id'],$player,$action]);
    }
    public static function minor(string $birth): bool { return (new \DateTimeImmutable($birth))->diff(new \DateTimeImmutable('today'))->y < 18; }
    public static function policySnapshot(): string {
        $s=self::settings(); return $s['responsable']."\n".$s['correo']."\n".$s['politica'];
    }
}
