<?php
declare(strict_types=1);
namespace App\Services;
final class Validation {
    public static function fields(array $fields, array $data): array {
        $errors = [];
        foreach ($fields as $key => [$label, $type, $rule]) {
            $value = $data[$key] ?? '';
            if ($value === '') { $errors[$key] = "$label: completa este campo."; continue; }
            if (in_array($type, ['select', 'category'], true)) {
                if (!array_key_exists($value, $rule)) $errors[$key] = "$label: selecciona una opción válida.";
            } elseif (mb_strlen($value) > $rule) $errors[$key] = "$label: máximo $rule caracteres.";
            if ($type === 'time' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $value)) $errors[$key] = "$label: hora inválida.";
            if ($type === 'date') {
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
                if (!$date || $date->format('Y-m-d') !== $value) $errors[$key] = "$label: fecha inválida.";
            }
        }
        if (isset($data['hora_inicio'], $data['hora_fin']) && $data['hora_fin'] <= $data['hora_inicio']) $errors['hora_fin'] = 'La hora final debe ser posterior a la inicial.';
        return $errors;
    }
    public static function contact(array $data): array {
        $errors = self::fields([
            'nombre_adulto' => ['Nombre del adulto', 'text', 100],
            'contacto' => ['Correo de contacto', 'text', 150],
            'tipo' => ['Tipo de solicitud', 'select', ['informacion' => 'Información', 'preinscripcion' => 'Preinscripción']],
            'mensaje' => ['Mensaje', 'textarea', 2000],
        ], $data);
        if (!filter_var($data['contacto'] ?? '', FILTER_VALIDATE_EMAIL)) $errors['contacto'] = 'Ingresa un correo electrónico válido.';
        if (($data['consentimiento'] ?? '') !== '1') $errors['consentimiento'] = 'Debes aceptar las condiciones de tratamiento de datos.';
        return $errors;
    }
}
