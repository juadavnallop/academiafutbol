<?php
// Verificaciones internas de la suite; no devuelve datos clínicos ni credenciales.
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/app/Core/bootstrap.php';
use App\Models\Repository as R;
if(!str_ends_with(config()['db_name'],'_test'))exit(1);
$id=(int)($argv[1]??0);
$row=R::one('SELECT contenido_cifrado FROM fichas_salud WHERE jugador_id=?',[$id]);
$profile=R::one('SELECT usuario_id FROM jugadores WHERE id=?',[$id]);
$output=['cipher_exists'=>(bool)$row,'cipher_plain_absent'=>!$row || !str_contains($row['contenido_cifrado'],'CLINICAL_MARKER'),
    'health_consents'=>(int)R::one("SELECT COUNT(*) total FROM autorizaciones WHERE jugador_id=? AND tipo='salud' AND revocado_en IS NULL",[$id])['total'],
    'evaluations'=>(int)R::one('SELECT COUNT(*) total FROM evaluaciones WHERE jugador_id=?',[$id])['total'],
    'role'=> $profile?R::one('SELECT rol FROM administradores WHERE id=?',[$profile['usuario_id']])['rol']:null,
    'audit_count'=>(int)R::one('SELECT COUNT(*) total FROM auditoria')['total']];
if($row){try{\App\Services\HealthVault::decrypt($row['contenido_cifrado'],$id+1000);$output['aad_protected']=false;}catch(Throwable){$output['aad_protected']=true;}}
echo json_encode($output);
