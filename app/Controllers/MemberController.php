<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\Repository as R;
use App\Core\Database;
use App\Services\{Security,Academy,MemberFields as F,HealthVault};
final class MemberController {
    public function register(): void {
        $actor=Security::user(); $byAdmin=($actor['rol']??'')==='administrador';
        if($actor && $actor['debe_cambiar_password'])redirect('cuenta');
        if($actor && !$byAdmin) redirect(Security::home());
        if(!Academy::ready() || (!$byAdmin && !Academy::settings()['inscripciones_abiertas'])) {
            view('message',['title'=>'Inscripciones no disponibles','message'=>'Las inscripciones en línea aún no están habilitadas. Consulta con la academia.']); return;
        }
        $fields=F::registration(); $values=[]; $errors=[];
        if($_SERVER['REQUEST_METHOD']==='POST') {
            $values=F::collect($fields); $errors=F::validate($fields,$values);
            $password=is_string($_POST['password']??null)?$_POST['password']:'';
            $confirmation=is_string($_POST['password_confirmation']??null)?$_POST['password_confirmation']:'';
            $errors+=F::password($password,$confirmation);
            if(!isset($errors['fecha_nacimiento']) && Academy::minor($values['fecha_nacimiento'])) {
                foreach(['tutor_nombre','tutor_parentesco','tutor_contacto'] as $key) if($values[$key]==='')$errors[$key]='Los menores requieren información de su representante legal.';
                if(input('representante')!=='1')$errors['representante']='La inscripción de un menor debe realizarla su representante legal.';
            }
            if(input('consentimiento')!=='1')$errors['consentimiento']='Debes autorizar el tratamiento de los datos para la inscripción.';
            if($byAdmin && (input('referencia')==='' || mb_strlen(input('referencia'))>240))$errors['referencia']='Registra la referencia de la autorización previa del titular o representante (máximo 240 caracteres).';
            if(!$byAdmin && time()-($_SESSION['registered_at']??0)<60)$errors['registro']='Espera un minuto antes de repetir el registro.';
            if(!$errors) {
                $db=Database::connection(); $db->beginTransaction();
                try {
                    R::execute("INSERT INTO administradores (nombre,correo,password_hash,rol,debe_cambiar_password) VALUES (?,?,?,'jugador',?)",[$values['nombre'],strtolower($values['correo']),password_hash($password,PASSWORD_DEFAULT),$byAdmin?1:0]);
                    $uid=(int)$db->lastInsertId();
                    $profile=array_diff_key($values,array_flip(['nombre','correo']));
                    R::execute('INSERT INTO jugadores (usuario_id,'.implode(',',array_keys($profile)).') VALUES ('.implode(',',array_fill(0,count($profile)+1,'?')).')',[$uid,...array_values($profile)]);
                    $id=(int)$db->lastInsertId();
                    R::execute('INSERT INTO inscripciones (jugador_id) VALUES (?)',[$id]);
                    $minor=Academy::minor($values['fecha_nacimiento']);
                    R::execute("INSERT INTO autorizaciones (jugador_id,tipo,version,otorgante,calidad,referencia,politica_snapshot,registrado_por) VALUES (?,'datos','datos-v2',?,?,?,?,?)",[$id,$minor?$values['tutor_nombre']:$values['nombre'],$minor?'representante':'titular',$byAdmin?input('referencia'):'Formulario en línea',Academy::policySnapshot(),$byAdmin?$actor['id']:$uid]);
                    R::execute('INSERT INTO auditoria (actor_id,jugador_id,accion) VALUES (?,?,?)',[$byAdmin?$actor['id']:$uid,$id,'inscripcion_creada']);
                    $db->commit();
                    if(!$byAdmin) { Security::login(R::one('SELECT * FROM administradores WHERE id=?',[$uid])); $_SESSION['registered_at']=time(); }
                    flash('Inscripción recibida. La academia revisará tus datos antes de confirmar la vinculación.');
                    redirect($byAdmin?'equipo/ficha':'mi-ficha',$byAdmin?['id'=>$id]:[]);
                } catch(\PDOException $e) { $db->rollBack(); if(($e->errorInfo[1]??0)!==1062)throw $e; $errors['correo']='No se pudo registrar ese correo. Si ya tienes cuenta, inicia sesión.'; }
            }
            http_response_code(422);
        }
        view('members/register',compact('fields','values','errors','byAdmin')+['title'=>'Inscripción de jugadores']);
    }
    public function listing(): void {
        $query=mb_substr(is_string($_GET['q']??null)?trim($_GET['q']):'',0,100);
        $state=is_string($_GET['estado']??null)?$_GET['estado']:'';
        $page=max(1,min(10000,(int)($_GET['page']??1)));$offset=($page-1)*25;
        $where='WHERE (u.nombre LIKE ? OR u.correo LIKE ?)';$params=['%'.$query.'%','%'.$query.'%'];
        if(in_array($state,['pendiente','en_revision','activa','rechazada','retirada'],true)){$where.=' AND i.estado=?';$params[]=$state;}
        $rows=R::all("SELECT j.id,u.nombre,u.correo,u.activo,c.nombre AS categoria,i.estado FROM jugadores j JOIN administradores u ON u.id=j.usuario_id JOIN inscripciones i ON i.jugador_id=j.id LEFT JOIN categorias c ON c.id=j.categoria_id $where ORDER BY j.id DESC LIMIT 26 OFFSET $offset",$params);
        $more=count($rows)>25;$rows=array_slice($rows,0,25);
        view('members/list',compact('rows','query','state','page','more')+['title'=>'Integrantes']);
    }
    public function profile(): void {
        $user=Security::user();$own=$user['rol']==='jugador';
        $id=$own ? (R::one('SELECT id FROM jugadores WHERE usuario_id=?',[$user['id']])['id']??0) : filter_var($_GET['id']??0,FILTER_VALIDATE_INT);
        if($own && isset($_GET['id']) && (string)$_GET['id']!==(string)$id)Security::deny();
        $player=R::one('SELECT j.*,u.nombre,u.correo FROM jugadores j JOIN administradores u ON u.id=j.usuario_id WHERE j.id=?',[$id?:0]);
        if(!$player){http_response_code(404);view('message',['title'=>'Ficha no encontrada']);return;}
        $tab=is_string($_GET['tab']??null)?$_GET['tab']:($_SERVER['REQUEST_METHOD']==='POST'?'personal':'resumen');
        if(!in_array($tab,['resumen','personal','deportiva','evaluaciones','inscripcion','salud'],true)){http_response_code(404);view('message',['title'=>'Sección no encontrada']);return;}
        if($tab==='salud' && $user['rol']==='asistente')Security::deny();
        $base=$own?'mi-ficha':'equipo/ficha';$params=$own?[]:['id'=>$id];
        $categories=array_column(R::all('SELECT id,nombre FROM categorias ORDER BY nombre'),'nombre','id');
        $fields=match($tab){'resumen'=>[],'personal'=>F::personal(),'deportiva'=>F::sport($categories),'evaluaciones'=>F::evaluation(),'salud'=>F::health(),default=>[
            'estado'=>['Estado','select',true,['pendiente'=>'Pendiente','en_revision'=>'En revisión','activa'=>'Activa','rechazada'=>'Rechazada','retirada'=>'Retirada']],
            'observaciones'=>['Observaciones de la inscripción','textarea',false,1500],
            'verificacion'=>['Referencia de revisión de identidad y autorización','text',false,240],
        ]};
        $enrollment=R::one('SELECT * FROM inscripciones WHERE jugador_id=?',[$id]);
        $canEdit=match($tab){'resumen'=>false,'personal'=>$user['rol']!=='asistente','deportiva','evaluaciones','inscripcion'=>!$own,'salud'=>true};
        $consent=null;$health=[];
        if($tab==='salud') {
            $consent=R::one("SELECT * FROM autorizaciones WHERE jugador_id=? AND tipo='salud' AND revocado_en IS NULL ORDER BY id DESC LIMIT 1",[$id]);
            if($consent){$row=R::one('SELECT contenido_cifrado FROM fichas_salud WHERE jugador_id=?',[$id]);if($row)$health=HealthVault::decrypt($row['contenido_cifrado'],(int)$id);Academy::audit('salud_consultada',(int)$id);}
            $canEdit=$own || (bool)$consent;
        }
        $values=match($tab){'inscripcion'=>$enrollment,'evaluaciones'=>['fecha'=>date('Y-m-d')],'salud'=>$health,default=>$player};$errors=[];
        if($_SERVER['REQUEST_METHOD']==='POST') {
            if(!$canEdit)Security::deny();
            $values=F::collect($fields);$errors=F::validate($fields,$values);
            if($tab==='inscripcion' && $values['estado']==='activa' && $values['verificacion']==='')$errors['verificacion']='Para activar la inscripción, registra la referencia de la verificación de identidad y autorización.';
            if($tab==='evaluaciones') {
                if(!hash_equals($_SESSION['evaluation_token_'.$id]??'',input('token')) || strlen(input('token'))!==64)$errors['envio']='El formulario venció o ya se guardó. Recarga la ficha.';
                if(count(array_filter(array_diff_key($values,['fecha'=>1]),fn($v)=>$v!==''))===0)$errors['fecha']='Registra al menos una medición, valoración u observación.';
            }
            if($tab==='salud' && input('accion')==='revocar') {
                if(!$own)Security::deny();
                $db=Database::connection();$db->beginTransaction();
                try {
                    R::one('SELECT id FROM jugadores WHERE id=? FOR UPDATE',[$id]);
                    R::execute("UPDATE autorizaciones SET revocado_en=NOW() WHERE jugador_id=? AND tipo='salud' AND revocado_en IS NULL",[$id]);
                    R::execute('DELETE FROM fichas_salud WHERE jugador_id=?',[$id]);Academy::audit('salud_autorizacion_revocada',(int)$id);$db->commit();
                } catch(\Throwable $e){$db->rollBack();throw $e;}
                flash('Autorización de salud revocada y ficha de salud eliminada de la base activa.');redirect($base,$params+['tab'=>'salud']);
            }
            if($tab==='salud' && !$consent && (!$own || input('consentimiento_salud')!=='1'))$errors['consentimiento_salud']='La información de salud requiere autorización explícita del titular o representante.';
            if(!$errors) {
                $db=Database::connection();$db->beginTransaction();
                try {
                    R::one('SELECT id FROM jugadores WHERE id=? FOR UPDATE',[$id]);
                    if(in_array($tab,['personal','deportiva'],true)) {
                        $data=$values;
                        foreach(['categoria_id','dorsal'] as $k)if(array_key_exists($k,$data)&&$data[$k]==='')$data[$k]=null;
                        R::execute('UPDATE jugadores SET '.implode(',',array_map(fn($k)=>$k.'=?',array_keys($data))).' WHERE id=?',[...array_values($data),$id]);
                    } elseif($tab==='inscripcion') {
                        R::execute('UPDATE inscripciones SET estado=?,observaciones=?,verificacion=?,revisado_por=? WHERE jugador_id=?',[$values['estado'],$values['observaciones'],$values['verificacion'],$user['id'],$id]);
                    } elseif($tab==='evaluaciones') {
                        $data=$values;foreach($data as $k=>$v)if($v==='' && $k!=='observaciones')$data[$k]=null;
                        R::execute('INSERT INTO evaluaciones (jugador_id,autor_id,token,'.implode(',',array_keys($data)).') VALUES ('.implode(',',array_fill(0,count($data)+3,'?')).')',[$id,$user['id'],input('token'),...array_values($data)]);
                        unset($_SESSION['evaluation_token_'.$id]);
                    } elseif($tab==='salud') {
                        $active=R::one("SELECT id FROM autorizaciones WHERE jugador_id=? AND tipo='salud' AND revocado_en IS NULL",[$id]);
                        if(!$active) {
                            if(!$own || input('consentimiento_salud')!=='1')throw new \RuntimeException('Consent changed');
                            $minor=Academy::minor($player['fecha_nacimiento']);
                            R::execute("INSERT INTO autorizaciones (jugador_id,tipo,version,otorgante,calidad,politica_snapshot,registrado_por) VALUES (?,'salud','salud-v2',?,?,?,?)",[$id,$minor?$player['tutor_nombre']:$player['nombre'],$minor?'representante':'titular',Academy::policySnapshot()."\nSalud opcional: uso restringido para organizar la participación deportiva; acceso titular y administrador; revocable.",$user['id']]);
                        }
                        R::execute('INSERT INTO fichas_salud (jugador_id,contenido_cifrado,actualizado_por) VALUES (?,?,?) ON DUPLICATE KEY UPDATE contenido_cifrado=VALUES(contenido_cifrado),actualizado_por=VALUES(actualizado_por)',[$id,HealthVault::encrypt($values,(int)$id),$user['id']]);
                    }
                    Academy::audit($tab.'_actualizada',(int)$id);$db->commit();
                }catch(\Throwable $e){$db->rollBack();throw $e;}
                flash('Información guardada correctamente.');redirect($base,$params+['tab'=>$tab]);
            }
            http_response_code(422);
        }
        $evaluations=[];
        if($tab==='evaluaciones') {
            $_SESSION['evaluation_token_'.$id]??=bin2hex(random_bytes(32));
            $page=max(1,min(10000,(int)($_GET['page']??1)));$offset=($page-1)*10;
            $evaluations=R::all("SELECT e.*,u.nombre AS autor FROM evaluaciones e JOIN administradores u ON u.id=e.autor_id WHERE jugador_id=? ORDER BY fecha DESC,e.id DESC LIMIT 11 OFFSET $offset",[$id]);
            $more=count($evaluations)>10;$evaluations=array_slice($evaluations,0,10);
        }
        $report=$tab==='resumen'?\App\Services\PlayerReport::build($player):null;
        $metric=is_string($_GET['metrica']??null)?$_GET['metrica']:'peso_kg';
        if(!in_array($metric,['peso_kg','altura_cm','velocidad_30m','resistencia_min'],true))$metric='peso_kg';
        view('members/profile',compact('player','id','tab','base','params','fields','values','errors','canEdit','own','consent','enrollment','evaluations','categories','report','metric')+['page'=>$page??1,'more'=>$more??false,'title'=>$own?'Mi ficha técnica':$player['nombre']]);
    }
}
