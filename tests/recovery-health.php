<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/app/Core/bootstrap.php';
$target=$argv[1]??'';$source=config()['db_name'];
if(!preg_match('/^[a-zA-Z0-9_]+_test$/D',$target)||!str_ends_with($source,'_test'))exit(1);
$db=\App\Core\Database::connection();
$rows=$db->query("SELECT jugador_id,contenido_cifrado FROM $target.fichas_salud")->fetchAll();
if(!$rows){fwrite(STDERR,"La prueba necesita al menos una ficha cifrada.\n");exit(1);}
foreach($rows as $row){
    $stmt=$db->prepare("SELECT contenido_cifrado FROM $source.fichas_salud WHERE jugador_id=?");$stmt->execute([$row['jugador_id']]);
    $expected=\App\Services\HealthVault::decrypt($stmt->fetchColumn(),(int)$row['jugador_id']);
    $actual=\App\Services\HealthVault::decrypt($row['contenido_cifrado'],(int)$row['jugador_id']);
    if($expected!==$actual)exit(1);
}
echo json_encode(['case'=>'Descifrado tras restauración con clave original','passed'=>true,'records'=>count($rows),'date'=>date(DATE_ATOM)],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
