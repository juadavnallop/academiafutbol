<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/app/Core/bootstrap.php';
$name=$argv[1]??'';
if(!preg_match('/^members_[a-z0-9]+_test$/D',$name))throw new RuntimeException('Invalid isolated database');
$db=new PDO('mysql:host=127.0.0.1;port=3307;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec("CREATE DATABASE $name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$db->exec("USE $name");
$db->exec(file_get_contents(ROOT.'/database/schema.sql'));
\App\Services\Migrations::run($db);
\App\Services\Migrations::run($db); // Reejecución sin pérdida de datos.
$fixture=['database'=>$name,'version'=>$db->query('SELECT VERSION()')->fetchColumn(),'php'=>PHP_VERSION];
foreach(['administrador','asistente'] as $role) {
    $password=bin2hex(random_bytes(16));$email=$role.'@example.com';
    $s=$db->prepare('INSERT INTO administradores (nombre,correo,password_hash,rol) VALUES (?,?,?,?)');$s->execute([ucfirst($role).' QA',$email,password_hash($password,PASSWORD_DEFAULT),$role]);
    $fixture[$role]=['email'=>$email,'password'=>$password,'id'=>(int)$db->lastInsertId()];
}
$path=ROOT.'/var/members-test-config.php';
file_put_contents($path,"<?php\nreturn ".var_export(['db_host'=>'127.0.0.1','db_port'=>3307,'db_name'=>$name,'db_user'=>'root','db_password'=>'','base_path'=>'','session_secure'=>false],true).";\n");
$fixture['config']=$path;
echo json_encode($fixture);
