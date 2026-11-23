<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/Core/bootstrap.php';
if(PHP_SAPI!=='cli'||!preg_match('/^members_[a-z0-9]+_test$/D',config()['db_name']))exit(1);
$db=\App\Core\Database::connection();
$player=(int)$argv[1];$author=(int)$argv[2];
$db->exec("INSERT INTO categorias(nombre,descripcion,activo) VALUES ('Formación QA','Trabajo de fundamentos',1),('Competencia QA','Preparación deportiva',1),('Inactiva QA','No visible',0)");
$ids=$db->query('SELECT id FROM categorias ORDER BY id DESC LIMIT 3')->fetchAll(PDO::FETCH_COLUMN);
[$inactive,$second,$first]=$ids;
$s=$db->prepare('INSERT INTO horarios(categoria_id,dia,hora_inicio,hora_fin,lugar,vigente) VALUES (?,?,?,?,?,?)');
foreach([[$first,1,'16:00','18:00','CANCHA_UNO',1],[$second,3,'15:00','17:00','CANCHA_DOS',1],[$inactive,2,'14:00','16:00','OCULTO_CATEGORIA',1],[$first,5,'14:00','16:00','OCULTO_HORARIO',0]] as $row)$s->execute($row);
$db->prepare('UPDATE jugadores SET categoria_id=? WHERE id=?')->execute([$first,$player]);
$s=$db->prepare('INSERT INTO publicaciones(titulo,contenido,tipo,fecha,estado,administrador_id) VALUES (?,?,?,?,?,?)');
$articles=[];
foreach([['Noticia visible QA','Texto completo <script>alert(1)</script>','noticia','2026-01-01','publicado'],['Actividad visible QA','Actividad deportiva','actividad','2026-01-02','publicado'],['Borrador oculto QA','Oculto','noticia','2026-01-01','borrador'],['Futuro oculto QA','Futuro','noticia','2099-01-01','publicado']] as $row){$s->execute([...$row,$author]);$articles[]=(int)$db->lastInsertId();}
$db->prepare('INSERT INTO evaluaciones(jugador_id,autor_id,fecha,altura_cm,peso_kg,velocidad_30m,resistencia_min,control,pase,regate,remate,trabajo_equipo,observaciones,token) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$player,$author,'2026-02-01',175,69,5,13,4,4,4,4,5,'Seguimiento: precisión y trabajo colectivo',bin2hex(random_bytes(32))]);
$upcoming=\App\Services\PlayerReport::upcoming([['dia'=>(int)date('N'),'hora_inicio'=>'00:00:00']]);
if($upcoming[0]['proximo_inicio']<=date('Y-m-d H:i:s'))throw new RuntimeException('Upcoming must be future');
echo json_encode(['first'=>$first,'second'=>$second,'articles'=>$articles]);
