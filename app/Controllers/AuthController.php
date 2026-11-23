<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Models\Repository as R;
final class AuthController {
    public function login(): void {
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = strtolower(input('correo'));
            $admin = R::one('SELECT * FROM administradores WHERE correo = ?', [$email]);
            $blocked = $admin && $admin['bloqueado_hasta'] && strtotime($admin['bloqueado_hasta']) > time();
            $sessionBlocked = ($_SESSION['login_blocked'] ?? 0) > time();
            $hash = $admin['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            $valid = strlen($password) <= 1024 && password_verify($password, $hash);
            if (!$blocked && !$sessionBlocked && $admin && $admin['activo'] && $valid) {
                R::execute('UPDATE administradores SET intentos_fallidos=0, bloqueado_hasta=NULL WHERE id=?', [$admin['id']]);
                \App\Services\Security::login($admin);
                redirect($admin['debe_cambiar_password'] ? 'cuenta' : \App\Services\Security::home());
            }
            if ($admin && !$blocked) R::execute('UPDATE administradores SET intentos_fallidos=intentos_fallidos+1, bloqueado_hasta=CASE WHEN intentos_fallidos >= 5 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE) ELSE NULL END WHERE id=?', [$admin['id']]);
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            if ($_SESSION['login_attempts'] >= 5) { $_SESSION['login_blocked'] = time() + 900; $_SESSION['login_attempts'] = 0; }
            http_response_code(422);
            $error = 'No fue posible iniciar sesión. Revisa tus datos o espera 15 minutos si hubo varios intentos.';
        }
        view('login', ['title' => 'Iniciar sesión', 'error' => $error]);
    }
}
