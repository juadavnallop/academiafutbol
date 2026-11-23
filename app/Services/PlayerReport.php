<?php
declare(strict_types=1);
namespace App\Services;
use App\Models\Repository as R;
final class PlayerReport {
    public static function build(array $player): array {
        $evaluations=R::all('SELECT e.*,u.nombre AS autor FROM evaluaciones e JOIN administradores u ON u.id=e.autor_id WHERE e.jugador_id=? ORDER BY e.fecha DESC,e.id DESC LIMIT 12',[$player['id']]);
        $total=(int)R::one('SELECT COUNT(*) total FROM evaluaciones WHERE jugador_id=?',[$player['id']])['total'];
        $slots=$player['categoria_id'] ? R::all('SELECT h.* FROM horarios h JOIN categorias c ON c.id=h.categoria_id WHERE h.categoria_id=? AND h.vigente=1 AND c.activo=1 ORDER BY h.dia,h.hora_inicio',[$player['categoria_id']]) : [];
        return ['latest'=>$evaluations[0]??null,'previous'=>$evaluations[1]??null,'history'=>array_reverse($evaluations),'total'=>$total,'next'=>self::upcoming($slots),'age'=>(new \DateTimeImmutable($player['fecha_nacimiento']))->diff(new \DateTimeImmutable('today'))->y];
    }
    public static function upcoming(array $slots): array {
        $now=new \DateTimeImmutable();$today=(int)$now->format('N');
        foreach($slots as &$slot){
            $days=((int)$slot['dia']-$today+7)%7;
            $next=$now->setTime((int)substr($slot['hora_inicio'],0,2),(int)substr($slot['hora_inicio'],3,2))->modify("+$days days");
            if($next<$now)$next=$next->modify('+7 days');
            $slot['proxima_fecha']=$next->format('Y-m-d');$slot['proximo_inicio']=$next->format('Y-m-d H:i:s');
        }
        unset($slot);usort($slots,fn($a,$b)=>strcmp($a['proximo_inicio'],$b['proximo_inicio']));return $slots;
    }
    public static function trend(array $history,string $metric): array {
        $allowed=['peso_kg','altura_cm','velocidad_30m','resistencia_min'];
        if(!in_array($metric,$allowed,true))$metric='peso_kg';
        return array_values(array_filter($history,fn($row)=>$row[$metric]!==null));
    }
}
