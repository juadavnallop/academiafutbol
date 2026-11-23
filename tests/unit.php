<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/Core/bootstrap.php';
use App\Services\Validation as V;
use App\Services\Catalog;
$passed = 0;
function check(bool $condition, string $name): void { global $passed; if (!$condition) { fwrite(STDERR, "FAIL $name\n"); exit(1); } $passed++; echo "PASS $name\n"; }
check(e('<script>"&') === '&lt;script&gt;&quot;&amp;', 'escape de HTML');
check(count(V::contact([])) === 5, 'contacto vacío y consentimiento');
$valid = ['nombre_adulto'=>'Persona Demo','contacto'=>'persona@example.com','tipo'=>'informacion','mensaje'=>'Prueba ficticia','consentimiento'=>'1'];
check(V::contact($valid) === [], 'contacto válido');
check(V::contact(array_replace($valid,['contacto'=>'persona@dominio-real.co']))===[], 'acepta correo válido de cualquier dominio');
check(isset(V::contact(array_replace($valid,['tipo'=>'otro']))['tipo']), 'rechaza tipo fuera de catálogo');
check(isset(V::contact(array_replace($valid,['mensaje'=>str_repeat('a',2001)]))['mensaje']), 'rechaza longitud excesiva');
$fields = Catalog::modules()['horarios']['fields']; $fields['categoria_id'][2] = [1=>'Demo'];
$slot = ['categoria_id'=>'1','dia'=>'2','hora_inicio'=>'16:00','hora_fin'=>'17:30','lugar'=>'Demo','vigente'=>'1'];
check(V::fields($fields,$slot) === [], 'horario válido');
check(isset(V::fields($fields,array_replace($slot,['hora_fin'=>'15:00']))['hora_fin']), 'orden de horas');
check(isset(V::fields($fields,array_replace($slot,['dia'=>'8']))['dia']), 'día inválido');
check(isset(V::fields($fields,array_replace($slot,['categoria_id'=>'99']))['categoria_id']), 'categoría inexistente');
check(isset(V::fields(['fecha'=>['Fecha','date',10]],['fecha'=>'2026-02-30'])['fecha']), 'fecha imposible');
check(V::fields(['fecha'=>['Fecha','date',10]],['fecha'=>'2028-02-29']) === [], 'fecha bisiesta');
check(isset(V::fields($fields,array_replace($slot,['hora_inicio'=>'25:00']))['hora_inicio']), 'hora imposible');
echo "$passed pruebas aprobadas.\n";
