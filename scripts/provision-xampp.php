<?php
// CLI local: configura una base NUEVA de XAMPP y una cuenta de aplicación sin DDL.
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/app/Core/bootstrap.php';
$options=getopt('', ['admin:', 'port:']);
$user=$options['admin']??'root';$port=(int)($options['port']??3306);
$password=rtrim((string)fgets(STDIN),"\r\n");
try {
    if(is_file(ROOT.'/config/local.php'))throw new RuntimeException('Ya existe configuración local; no se sobrescribe.');
    $db=new PDO("mysql:host=127.0.0.1;port=$port;charset=utf8mb4",$user,$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $exists=$db->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='cancha_abierta'")->fetchColumn();
    if($exists)throw new RuntimeException('La base cancha_abierta ya existe; no se modifica automáticamente.');
    $db->exec('CREATE DATABASE cancha_abierta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$db->exec('USE cancha_abierta');
    $db->exec(file_get_contents(ROOT.'/database/schema.sql'));
    $db->exec(file_get_contents(ROOT.'/database/initial.sql'));
    \App\Services\Migrations::run($db);
    $secret=bin2hex(random_bytes(24));
    $db->exec("CREATE USER 'cancha_web'@'127.0.0.1' IDENTIFIED BY ".$db->quote($secret));
    $db->exec("GRANT SELECT,INSERT,UPDATE,DELETE ON cancha_abierta.* TO 'cancha_web'@'127.0.0.1'");
    $config=['db_host'=>'127.0.0.1','db_port'=>$port,'db_name'=>'cancha_abierta','db_user'=>'cancha_web','db_password'=>$secret,'base_path'=>'/cancha-abierta/public','session_secure'=>false];
    file_put_contents(ROOT.'/config/local.php',"<?php\nreturn ".var_export($config,true).";\n");
    echo "Base cancha_abierta configurada en XAMPP. Cuenta de aplicación creada; credenciales únicamente en configuración privada.\n";
}catch(Throwable $e){fwrite(STDERR,'Instalación detenida: '.$e->getMessage()."\n");exit(1);}
