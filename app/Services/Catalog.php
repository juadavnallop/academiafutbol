<?php
declare(strict_types=1);
namespace App\Services;
final class Catalog {
    public static function days(): array { return [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo']; }
    public static function modules(): array {
        return [
            'institucion' => ['title' => 'Información institucional', 'table' => 'contenidos_institucionales', 'fields' => [
                'titulo' => ['Título', 'text', 140], 'contenido' => ['Contenido', 'textarea', 10000],
                'estado' => ['Estado', 'select', ['borrador' => 'Borrador', 'publicado' => 'Publicado']],
            ]],
            'categorias' => ['title' => 'Categorías', 'table' => 'categorias', 'fields' => [
                'nombre' => ['Nombre', 'text', 100], 'descripcion' => ['Descripción', 'textarea', 2000],
                'activo' => ['Estado', 'select', [1 => 'Activa', 0 => 'Inactiva']],
            ]],
            'horarios' => ['title' => 'Horarios', 'table' => 'horarios', 'fields' => [
                'categoria_id' => ['Categoría', 'category', []], 'dia' => ['Día', 'select', self::days()],
                'hora_inicio' => ['Hora de inicio', 'time', 5], 'hora_fin' => ['Hora de finalización', 'time', 5],
                'lugar' => ['Lugar', 'text', 180], 'vigente' => ['Vigencia', 'select', [1 => 'Vigente', 0 => 'No vigente']],
            ]],
            'publicaciones' => ['title' => 'Noticias y actividades', 'table' => 'publicaciones', 'fields' => [
                'titulo' => ['Título', 'text', 160], 'contenido' => ['Contenido', 'textarea', 10000],
                'tipo' => ['Tipo', 'select', ['noticia' => 'Noticia', 'actividad' => 'Actividad']],
                'fecha' => ['Fecha de publicación', 'date', 10],
                'estado' => ['Estado', 'select', ['borrador' => 'Borrador', 'publicado' => 'Publicado']],
            ]],
        ];
    }
}
