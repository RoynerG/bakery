<?php
declare(strict_types=1);

namespace App;

use App\Models\Usuario;

/**
 * Autenticación basada en sesiones PHP.
 *
 * Sesión actual del admin:
 *   $_SESSION['auth']['user_id']      => int
 *   $_SESSION['auth']['usuario']      => string
 *   $_SESSION['auth']['nombre']       => string|null
 *   $_SESSION['auth']['login_at']     => int (timestamp)
 */
final class Auth
{
    /** Intenta autenticar. Devuelve el usuario o null. */
    public static function attempt(string $usuario, string $password): ?array
    {
        $user = Usuario::verifyPassword($usuario, $password);
        if (!$user) return null;

        // Regenerar ID de sesión para evitar session fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['auth'] = [
            'user_id'  => (int) $user['id'],
            'usuario'  => $user['usuario'],
            'nombre'   => $user['nombre'],
            'login_at' => time(),
        ];

        Usuario::touchLogin((int) $user['id']);
        return $user;
    }

    /** Cierra la sesión actual. */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /** ¿Hay un usuario autenticado? */
    public static function check(): bool
    {
        return !empty($_SESSION['auth']['user_id']);
    }

    /** Devuelve el usuario actual o null. */
    public static function user(): ?array
    {
        if (!self::check()) return null;
        $id = (int) $_SESSION['auth']['user_id'];
        $user = Usuario::find($id);
        // Si por alguna razón fue borrado, limpiamos la sesión
        if (!$user) {
            self::logout();
            return null;
        }
        return $user;
    }

    /** Nombre a mostrar (nombre real o usuario). */
    public static function displayName(): string
    {
        $u = self::user();
        if (!$u) return '';
        return $u['nombre'] ?: $u['usuario'];
    }

    /**
     * Protege una página: si no hay sesión, redirige a login.
     * Llamar al inicio de cualquier página que requiera auth.
     */
    public static function require(): void
    {
        if (!self::check()) {
            flash('error', 'Debes iniciar sesión para acceder.');
            redirect('login.php');
        }
    }
}