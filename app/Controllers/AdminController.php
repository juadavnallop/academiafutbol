<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\Repository as R;
use App\Services\Catalog;
use App\Services\Validation;
final class AdminController {
    public function dashboard(): void {
        $counts = [];
        foreach (['categorias', 'horarios', 'publicaciones', 'solicitudes'] as $table) $counts[$table] = R::one("SELECT COUNT(*) AS total FROM $table")['total'];
        view('dashboard', ['title' => 'Tu academia, al día', 'counts' => $counts]);
    }
    public function content(): void {
        $module = is_string($_GET['mod'] ?? null) ? $_GET['mod'] : 'institucion';
        $definition = Catalog::modules()[$module] ?? null;
        if (!$definition) { http_response_code(404); view('message', ['title' => 'Módulo no encontrado']); return; }
        $table = $definition['table']; $fields = $definition['fields'];
        if ($module === 'horarios') {
            $fields['categoria_id'][2] = array_column(R::all('SELECT id,nombre FROM categorias ORDER BY nombre'), 'nombre', 'id');
        }
        $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($id === false) { http_response_code(404); view('message', ['title' => 'Registro no encontrado']); return; }
        $values = $id ? R::one("SELECT * FROM $table WHERE id=?", [$id]) : [];
        if ($id && !$values) { http_response_code(404); view('message', ['title' => 'Registro no encontrado']); return; }
        foreach (['hora_inicio', 'hora_fin'] as $time) if (isset($values[$time])) $values[$time] = substr($values[$time], 0, 5);
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values = [];
            foreach ($fields as $key => $_) $values[$key] = input($key);
            $errors = Validation::fields($fields, $values);
            if (!$errors) {
                if ($module === 'publicaciones') $values['administrador_id'] = $_SESSION['admin_id'];
                try {
                    R::save($module, $values, $id);
                    flash('Cambios guardados. El portal muestra únicamente registros publicados, activos y vigentes.');
                    redirect('admin/contenido', ['mod' => $module]);
                } catch (\PDOException $ex) {
                    if (($ex->errorInfo[1] ?? 0) !== 1062) throw $ex;
                    $errors['registro'] = 'Ya existe un registro con ese nombre o con esa combinación de categoría, día, hora y lugar.';
                }
            }
            http_response_code(422);
        }
        $page = max(1, min(100000, (int)($_GET['page'] ?? 1))); $offset = ($page - 1) * 25;
        $rows = R::all("SELECT * FROM $table ORDER BY id DESC LIMIT 26 OFFSET $offset");
        $more = count($rows) > 25; $rows = array_slice($rows, 0, 25);
        view('editor', compact('module', 'fields', 'values', 'errors', 'id', 'rows', 'page', 'more') + ['title' => $definition['title']]);
    }
    public function requests(): void {
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $state = input('estado'); $id = filter_var(input('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id || !in_array($state, ['pendiente', 'revisada', 'cerrada'], true)) $errors['estado'] = 'Selecciona un registro y un estado válidos.';
            elseif (!R::one('SELECT id FROM solicitudes WHERE id=?', [$id])) $errors['estado'] = 'La solicitud no existe.';
            else { R::execute('UPDATE solicitudes SET estado=? WHERE id=?', [$state, $id]); flash('Estado de la solicitud actualizado.'); redirect('admin/solicitudes'); }
            http_response_code(422);
        }
        $page = max(1, min(100000, (int)($_GET['page'] ?? 1))); $offset = ($page - 1) * 20;
        $requests = R::all("SELECT * FROM solicitudes ORDER BY id DESC LIMIT 21 OFFSET $offset");
        $more = count($requests) > 20; $requests = array_slice($requests, 0, 20);
        view('requests', compact('requests', 'errors', 'page', 'more') + ['title' => 'Solicitudes recibidas']);
    }
}
