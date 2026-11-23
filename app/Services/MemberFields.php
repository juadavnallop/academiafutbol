<?php
declare(strict_types=1);
namespace App\Services;
final class MemberFields {
    // label, type, required, max length or choices or numeric interval.
    public static function personal(): array { return [
        'telefono'=>['Teléfono','tel',true,40], 'direccion'=>['Dirección','text',false,240],
        'emergencia_nombre'=>['Contacto de emergencia','text',false,120], 'emergencia_telefono'=>['Teléfono de emergencia','tel',false,40],
    ]; }
    public static function registration(): array { return [
        'nombre'=>['Nombre completo del jugador','text',true,100], 'correo'=>['Correo de acceso','email',true,190],
        'fecha_nacimiento'=>['Fecha de nacimiento','date',true,10],
    ] + self::personal() + [
        'tutor_nombre'=>['Nombre del representante legal','text',false,120],
        'tutor_parentesco'=>['Parentesco o vínculo legal','text',false,60],
        'tutor_contacto'=>['Correo o teléfono del representante','text',false,150],
    ]; }
    public static function sport(array $categories): array { return [
        'categoria_id'=>['Categoría','select',false,$categories],
        'posicion'=>['Posición principal','select',false,['portero'=>'Portero','defensa'=>'Defensa','mediocampista'=>'Mediocampista','delantero'=>'Delantero']],
        'pie'=>['Pie dominante','select',false,['derecho'=>'Derecho','izquierdo'=>'Izquierdo','ambos'=>'Ambos']],
        'dorsal'=>['Dorsal','number',false,[1,99]], 'experiencia'=>['Trayectoria y objetivos deportivos','textarea',false,2000],
    ]; }
    public static function evaluation(): array { return [
        'fecha'=>['Fecha de evaluación','date',true,10],
        'altura_cm'=>['Estatura (cm)','decimal',false,[50,250]], 'peso_kg'=>['Peso (kg)','decimal',false,[10,250]],
        'velocidad_30m'=>['Tiempo en 30 metros (segundos)','decimal',false,[1,60]],
        'resistencia_min'=>['Duración de ejercicio de resistencia (minutos)','decimal',false,[0,300]],
        'control'=>['Control de balón (1–5)','number',false,[1,5]], 'pase'=>['Pase (1–5)','number',false,[1,5]],
        'regate'=>['Regate (1–5)','number',false,[1,5]], 'remate'=>['Remate (1–5)','number',false,[1,5]],
        'trabajo_equipo'=>['Trabajo en equipo (1–5)','number',false,[1,5]],
        'observaciones'=>['Observaciones y protocolo aplicado','textarea',false,2000],
    ]; }
    public static function health(): array { return [
        'eps'=>['Entidad de salud / aseguradora','text',false,120],
        'grupo_sanguineo'=>['Grupo sanguíneo conocido','select',false,array_combine(['A+','A-','B+','B-','AB+','AB-','O+','O-'],['A+','A-','B+','B-','AB+','AB-','O+','O-'])],
        'alergias'=>['Alergias informadas','textarea',false,1500],
        'condiciones'=>['Antecedentes relevantes para la práctica','textarea',false,2000],
        'medicamentos'=>['Medicamentos informados','textarea',false,1500],
        'restricciones'=>['Restricciones o indicaciones del profesional tratante','textarea',false,2000],
    ]; }
    public static function collect(array $fields): array { $data=[]; foreach ($fields as $key=>$_) $data[$key]=input($key); return $data; }
    public static function validate(array $fields,array $data): array {
        $errors=[];
        foreach ($fields as $key=>[$label,$type,$required,$rule]) {
            $v=$data[$key] ?? '';
            if ($v==='') { if($required)$errors[$key]="$label: completa este campo."; continue; }
            if ($type==='select') { if(!array_key_exists($v,$rule))$errors[$key]="$label: opción inválida."; }
            elseif (in_array($type,['number','decimal'],true)) {
                $valid=$type==='number' ? preg_match('/^\d+$/D',$v) : preg_match('/^\d+(\.\d{1,2})?$/D',$v);
                if(!$valid || (float)$v<$rule[0] || (float)$v>$rule[1])$errors[$key]="$label: valor entre {$rule[0]} y {$rule[1]}.";
            } else {
                if(mb_strlen($v)>$rule)$errors[$key]="$label: máximo $rule caracteres.";
                if($type==='email' && !filter_var($v,FILTER_VALIDATE_EMAIL))$errors[$key]="$label: ingresa un correo válido.";
                if($type==='tel' && !preg_match('/^[+\d\s().-]{7,40}$/D',$v))$errors[$key]="$label: ingresa un teléfono válido.";
                if($type==='date') {
                    $date=\DateTimeImmutable::createFromFormat('!Y-m-d',$v);
                    if(!$date || $date->format('Y-m-d')!==$v || $v>date('Y-m-d') || $v<'1900-01-01')$errors[$key]="$label: fecha inválida o futura.";
                }
            }
        }
        return $errors;
    }
    public static function password(string $password,string $confirmation): array {
        if(strlen($password)<12 || strlen($password)>72)return ['password'=>'La contraseña debe tener entre 12 y 72 bytes.'];
        if($password!==$confirmation)return ['password'=>'Las contraseñas no coinciden.'];
        return [];
    }
}
