<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Database;
use App\Models\Repository as R;
use App\Services\{Security,Academy,MemberFields as F};
final class AccountController {
    public function password(): void {
        $errors=[];
        if($_SERVER['REQUEST_METHOD']==='POST') {
            $current=is_string($_POST['actual']??null)?$_POST['actual']:'';
            $password=is_string($_POST['password']??null)?$_POST['password']:'';
            $confirmation=is_string($_POST['password_confirmation']??null)?$_POST['password_confirmation']:'';
            $errors=F::password($password,$confirmation);$user=Security::user();
            $account=R::one('SELECT * FROM administradores WHERE id=?',[$user['id']]);
            if(!password_verify($current,$account['password_hash']))$errors['actual']='La contraseña actual no es correcta.';
            if($password===$current)$errors['password']='Elige una contraseña diferente de la actual.';
            if(!$errors) {
                R::execute('UPDATE administradores SET password_hash=?,version_sesion=version_sesion+1,debe_cambiar_password=0 WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$user['id']]);
                Academy::audit('password_actualizada');Security::login(R::one('SELECT * FROM administradores WHERE id=?',[$user['id']]));
                flash('Contraseña actualizada. Las otras sesiones de tu cuenta se han invalidado.');redirect(Security::home());
            }
            http_response_code(422);
        }
        view('members/password',compact('errors')+['title'=>'Mi cuenta']);
    }
    public function accounts(): void {
        $errors=[];$values=[];$id=filter_var($_GET['id']??0,FILTER_VALIDATE_INT);
        $account=$id?R::one('SELECT id,nombre,correo,rol,activo FROM administradores WHERE id=?',[$id]):null;
        if($id && !$account){http_response_code(404);view('message',['title'=>'Cuenta no encontrada']);return;}
        $fields=['nombre'=>['Nombre completo','text',true,100],'correo'=>['Correo de acceso','email',true,190],
            'rol'=>['Rol','select',true,$id?['administrador'=>'Administrador','asistente'=>'Asistente','jugador'=>'Jugador']:['asistente'=>'Asistente','administrador'=>'Administrador']],
            'activo'=>['Acceso','select',true,['1'=>'Habilitado','0'=>'Deshabilitado']]];
        $values=$account??['activo'=>'1'];
        if($_SERVER['REQUEST_METHOD']==='POST') {
            $values=F::collect($fields);$errors=F::validate($fields,$values);
            $password=is_string($_POST['password']??null)?$_POST['password']:'';
            if(!$id || $password!=='')$errors+=F::password($password,is_string($_POST['password_confirmation']??null)?$_POST['password_confirmation']:'');
            if($id==Security::user()['id'] && ($values['rol']!=='administrador' || $values['activo']!=='1'))$errors['activo']='No puedes desactivar ni retirar tu propio rol de administrador.';
            if($id && $values['rol']==='jugador' && !R::one('SELECT id FROM jugadores WHERE usuario_id=?',[$id]))$errors['rol']='Esta cuenta no tiene inscripción. Crea al jugador desde Inscribir jugador.';
            if(!$errors) {
                $db=Database::connection();$db->beginTransaction();
                try {
                    if($id) {
                        $admins=R::all("SELECT id FROM administradores WHERE rol='administrador' AND activo=1 FOR UPDATE");
                        if($account['rol']==='administrador' && $account['activo'] && count($admins)<=1 && ($values['rol']!=='administrador'||$values['activo']!=='1'))throw new \DomainException('Debe permanecer al menos un administrador activo.');
                        R::execute('UPDATE administradores SET nombre=?,correo=?,rol=?,activo=?,version_sesion=version_sesion+1 WHERE id=?',[$values['nombre'],strtolower($values['correo']),$values['rol'],$values['activo'],$id]);
                        if($password!=='')R::execute('UPDATE administradores SET password_hash=?,debe_cambiar_password=1,intentos_fallidos=0,bloqueado_hasta=NULL WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$id]);
                    } else R::execute('INSERT INTO administradores (nombre,correo,rol,activo,password_hash,debe_cambiar_password) VALUES (?,?,?,?,?,1)',[$values['nombre'],strtolower($values['correo']),$values['rol'],$values['activo'],password_hash($password,PASSWORD_DEFAULT)]);
                    Academy::audit('cuenta_guardada');$db->commit();
                    if($id==Security::user()['id']){Security::logout();redirect('login');}
                    flash('Cuenta guardada. Los cambios de acceso invalidan las sesiones anteriores.');redirect('admin/cuentas');
                }catch(\DomainException $e){$db->rollBack();$errors['activo']=$e->getMessage();}
                catch(\PDOException $e){$db->rollBack();if(($e->errorInfo[1]??0)!==1062)throw $e;$errors['correo']='Ya existe una cuenta con ese correo.';}
            }
            http_response_code(422);
        }
        $page=max(1,min(10000,(int)($_GET['page']??1)));$offset=($page-1)*25;
        $rows=R::all("SELECT id,nombre,correo,rol,activo FROM administradores ORDER BY id DESC LIMIT 26 OFFSET $offset");$more=count($rows)>25;$rows=array_slice($rows,0,25);
        view('members/accounts',compact('fields','values','errors','rows','id','page','more')+['title'=>'Cuentas y roles']);
    }
    public function settings(): void {
        $fields=['nombre'=>['Nombre de la academia','text',true,120],'ciudad'=>['Ciudad','text',false,120],
            'correo'=>['Correo de contacto y privacidad','email',true,190],'telefono'=>['Teléfono','tel',false,40],
            'responsable'=>['Responsable del tratamiento de datos','text',true,190],
            'politica'=>['Política: finalidades, conservación, derechos y canales de atención','textarea',true,12000],
            'inscripciones_abiertas'=>['Inscripciones en línea','select',true,['0'=>'Cerradas','1'=>'Abiertas']]];
        $values=Academy::settings();$errors=[];
        if($_SERVER['REQUEST_METHOD']==='POST') {
            $values=F::collect($fields);$errors=F::validate($fields,$values);
            if(!$errors) {
                R::execute('UPDATE academia_config SET '.implode(',',array_map(fn($k)=>$k.'=?',array_keys($fields))).' WHERE id=1',array_values($values));
                Academy::audit('configuracion_actualizada');flash('Configuración de la academia actualizada.');redirect('admin/configuracion');
            }
            http_response_code(422);
        }
        view('members/settings',compact('fields','values','errors')+['title'=>'Configuración de la academia']);
    }
    public function audit(): void {
        $page=max(1,min(10000,(int)($_GET['page']??1)));$offset=($page-1)*50;
        $rows=R::all("SELECT a.*,u.nombre FROM auditoria a JOIN administradores u ON u.id=a.actor_id ORDER BY a.id DESC LIMIT 51 OFFSET $offset");$more=count($rows)>50;$rows=array_slice($rows,0,50);
        view('members/audit',compact('rows','page','more')+['title'=>'Registro de acciones']);
    }
}
