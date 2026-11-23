<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/Core/bootstrap.php';
use App\Services\Security;
use App\Controllers\PublicController;
use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\MemberController;
use App\Controllers\AccountController;
try {
    Security::start();
    $route = is_string($_GET['r'] ?? null) ? $_GET['r'] : 'inicio';
    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
        header('Allow: GET, POST'); http_response_code(405); view('message', ['title' => 'Método no permitido']); exit;
    }
    if (str_starts_with($route, 'admin')) Security::requireAdmin();
    if (str_starts_with($route, 'equipo')) Security::requireRoles(['administrador','asistente']);
    if ($route === 'mi-ficha') Security::requireRoles(['jugador']);
    if (in_array($route,['cuenta','logout'],true)) Security::requireRoles(['administrador','asistente','jugador']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Security::verifyCsrf()) {
        http_response_code(403); view('message', ['title' => 'Formulario vencido', 'message' => 'Recarga la página y vuelve a intentarlo. No se guardó ningún cambio.']); exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($route, ['login','logout','contacto','admin/contenido','admin/solicitudes','registro','mi-ficha','equipo/ficha','cuenta','admin/cuentas','admin/configuracion'], true)) {
        http_response_code(405); view('message', ['title' => 'Método no permitido']); exit;
    }
    switch ($route) {
        case 'inicio': case 'academia': case 'horarios': case 'noticias': (new PublicController())->page($route); break;
        case 'publicacion': (new PublicController())->article(); break;
        case 'contacto': (new PublicController())->contact(); break;
        case 'login': (new AuthController())->login(); break;
        case 'registro': (new MemberController())->register(); break;
        case 'equipo': (new MemberController())->listing(); break;
        case 'equipo/ficha': case 'mi-ficha': (new MemberController())->profile(); break;
        case 'cuenta': (new AccountController())->password(); break;
        case 'admin/cuentas': (new AccountController())->accounts(); break;
        case 'admin/configuracion': (new AccountController())->settings(); break;
        case 'admin/auditoria': (new AccountController())->audit(); break;
        case 'admin': (new AdminController())->dashboard(); break;
        case 'admin/contenido': (new AdminController())->content(); break;
        case 'admin/solicitudes': (new AdminController())->requests(); break;
        case 'logout':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); view('message', ['title' => 'Usa el botón Cerrar sesión']); break; }
            Security::logout(); redirect('inicio');
        case 'privacidad': view('privacy', ['title' => 'Tratamiento de datos personales']); break;
        default: http_response_code(404); view('message', ['title' => 'Página no encontrada']);
    }
} catch (Throwable $error) {
    $reference = bin2hex(random_bytes(4));
    error_log('Cancha Abierta ' . $reference . ' ' . get_class($error) . ' code=' . $error->getCode());
    http_response_code(503);
    view('message', ['title' => 'Servicio temporalmente no disponible', 'message' => 'No pudimos completar la operación. Revisa la configuración y la conexión a la base de datos. Referencia: ' . $reference]);
}
