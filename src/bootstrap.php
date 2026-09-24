<?php
/**
 * Bootstrap: carga config, autoload manual, helpers y modelos.
 * Este archivo se incluye desde cualquier página en /public.
 */

declare(strict_types=1);

// 1. Configuración
require_once __DIR__ . '/../config/config.php';

// 2. Helpers
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/debug.php';

// 2.1 Debug: agregar ?debug=1 a la URL para ver errores y stack trace
debug_install();

// 3. Autoload manual de clases del namespace App\
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require_once $file;
});

// 4. Disparar el instalador si la BD no tiene tablas (ambos drivers)
try {
    $db = App\Database::getInstance();

    if (DB_DRIVER === 'sqlite') {
        $exists = $db->fetchColumn(
            "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='ingredientes'"
        );
    } elseif (DB_DRIVER === 'mysql') {
        $exists = $db->fetchColumn(
            "SELECT COUNT(*) FROM information_schema.tables
              WHERE table_schema = ? AND table_name = 'ingredientes'",
            [DB_NAME]
        );
    } else {
        $exists = 1;
    }

    if (!$exists && is_file(__DIR__ . '/../install.php')) {
        require_once __DIR__ . '/../install.php';
    }
} catch (Throwable $e) {
    // Silenciar: la página mostrará el mensaje si lo requiere
}

// 5. Guard de autenticacion global:
//    - Si NO hay usuarios en la BD -> forzar setup.php
//    - Si hay usuarios pero NO hay sesion -> forzar login.php
//    (excepto en setup.php, login.php y logout.php que son publicos)
$scriptActual = basename($_SERVER['SCRIPT_NAME'] ?? '');
$rutasPublicas = ['setup.php', 'login.php', 'logout.php'];

if (!in_array($scriptActual, $rutasPublicas, true)) {
    try {
        $hayUsuarios = (int) App\Database::getInstance()->fetchColumn('SELECT COUNT(*) FROM usuarios') > 0;

        if (!$hayUsuarios) {
            redirect('setup.php');
        }
        if (!App\Auth::check()) {
            redirect('login.php');
        }
    } catch (Throwable $e) {
        // Si falla la consulta, dejamos que cada pagina maneje su auth
    }
}