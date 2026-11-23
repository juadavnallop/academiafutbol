<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$db=new PDO('mysql:host=127.0.0.1;port=3307;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$name='import_'.bin2hex(random_bytes(6)).'_test';
try {
    $db->exec(str_replace('cancha_abierta',$name,file_get_contents(dirname(__DIR__).'/database/instalar_xampp.sql')));
    $count=(int)$db->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='.$db->quote($name))->fetchColumn();
    if($count!==14)throw new RuntimeException('Expected 14 tables');
    if((int)$db->query('SELECT COUNT(*) FROM administradores')->fetchColumn()!==0)throw new RuntimeException('Unexpected accounts');
    if((int)$db->query('SELECT COUNT(*) FROM contenidos_institucionales')->fetchColumn()!==1)throw new RuntimeException('Initial content missing');
    echo "PASS SQL completo: 14 tablas, sin cuentas predeterminadas, contenido inicial correcto.\n";
}finally{$db->exec("DROP DATABASE IF EXISTS `$name`");}
