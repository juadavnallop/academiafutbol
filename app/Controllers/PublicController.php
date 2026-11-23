<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\Repository as R;
use App\Services\Validation;
final class PublicController {
    public function page(string $route): void {
        $titles = ['inicio' => 'El equipo comienza aquí', 'academia' => 'Nuestra academia', 'horarios' => 'Categorías y horarios', 'noticias' => 'Actualidad del equipo'];
        $institution = R::all("SELECT * FROM contenidos_institucionales WHERE estado = 'publicado' ORDER BY id");
        $categories = R::all('SELECT * FROM categorias WHERE activo = 1 ORDER BY id');
        $category=filter_var($_GET['categoria']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]])?:0;
        $day=filter_var($_GET['dia']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>7]])?:0;
        $where='h.vigente = 1 AND c.activo = 1';$params=[];
        if($route==='horarios' && $category){$where.=' AND h.categoria_id=?';$params[]=$category;}
        if($route==='horarios' && $day){$where.=' AND h.dia=?';$params[]=$day;}
        $schedules = R::all('SELECT h.*, c.nombre FROM horarios h JOIN categorias c ON c.id = h.categoria_id WHERE '.$where.' ORDER BY h.dia, h.hora_inicio,h.id',$params);
        $kind=is_string($_GET['tipo']??null)&&in_array($_GET['tipo'],['noticia','actividad'],true)?$_GET['tipo']:'';
        $page=max(1,min(10000,(int)($_GET['page']??1)));$offset=($page-1)*9;
        $newsWhere="estado = 'publicado' AND fecha <= CURRENT_DATE";$newsParams=[];
        if($route==='noticias' && $kind){$newsWhere.=' AND tipo=?';$newsParams[]=$kind;}
        $count=(int)R::one("SELECT COUNT(*) total FROM publicaciones WHERE $newsWhere",$newsParams)['total'];
        $limit=$route==='inicio'?3:9;$offset=$route==='inicio'?0:$offset;
        $news = R::all("SELECT * FROM publicaciones WHERE $newsWhere ORDER BY fecha DESC,id DESC LIMIT $limit OFFSET $offset",$newsParams);
        $upcoming=\App\Services\PlayerReport::upcoming($schedules);
        view('public', compact('route', 'institution', 'categories', 'schedules', 'news','category','day','kind','page','count','upcoming') + ['title' => $titles[$route]]);
    }
    public function article(): void {
        $id=filter_var($_GET['id']??0,FILTER_VALIDATE_INT);
        $article=R::one("SELECT * FROM publicaciones WHERE id=? AND estado='publicado' AND fecha<=CURRENT_DATE",[$id?:0]);
        if(!$article){http_response_code(404);view('message',['title'=>'Publicación no disponible']);return;}
        view('portal/article',['article'=>$article,'title'=>$article['titulo']]);
    }
    public function contact(): void {
        if (!\App\Services\Academy::ready()) {
            view('message', ['title'=>'Contacto de la academia', 'message'=>'La academia está actualizando sus canales de atención. El formulario estará disponible cuando se publique la información de contacto y privacidad.']);
            return;
        }
        $errors = []; $values = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach (['nombre_adulto', 'contacto', 'tipo', 'mensaje', 'consentimiento'] as $field) $values[$field] = input($field);
            $errors = Validation::contact($values);
            $token = input('token_envio');
            if (strlen($token) !== 64 || !hash_equals($_SESSION['contact_token'] ?? '', $token)) $errors['envio'] = 'Este formulario venció o ya se envió. Recarga la página.';
            if (!$errors) {
                try {
                    R::execute("INSERT INTO solicitudes (nombre_adulto,contacto,tipo,mensaje,consentimiento,token_envio,consentimiento_version) VALUES (?,?,?,?,1,?,'contacto-v2')", [$values['nombre_adulto'], $values['contacto'], $values['tipo'], $values['mensaje'], $token]);
                } catch (\PDOException $ex) { if (($ex->errorInfo[1] ?? 0) !== 1062) throw $ex; }
                unset($_SESSION['contact_token']);
                flash('Solicitud recibida. La academia podrá revisar tu mensaje y contactarte. El envío no confirma una inscripción.');
                redirect('contacto');
            }
            http_response_code(422);
        }
        $_SESSION['contact_token'] ??= bin2hex(random_bytes(32));
        view('contact', ['title' => 'Hablemos de fútbol', 'errors' => $errors, 'values' => $values]);
    }
}
