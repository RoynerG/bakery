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

// 3. Autoload manual de clases del namespace App\
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require_once $file;
});

// 4. Asegurar que la base de datos exista (sólo SQLite)
if (DB_DRIVER === 'sqlite' && !file_exists(SQLITE_PATH)) {
    @touch(SQLITE_PATH);
}

// 5. Disparar el instalador si la BD no tiene tablas (solo SQLite)
try {
    if (DB_DRIVER === 'sqlite') {
        $db = App\Database::getInstance();
        $exists = $db->fetchColumn("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='ingredientes'");
        if (!$exists) {
            // La primera carga ejecutará install.php automáticamente
            if (is_file(__DIR__ . '/../install.php')) {
                require_once __DIR__ . '/../install.php';
            }
        }
    }
    // Para MySQL se asume que el usuario importó database/schema.mysql.sql
    // desde el panel (phpMyAdmin / Hostinger).
} catch (Throwable $e) {
    // Silenciar: la página mostrará el mensaje si lo requiere
}
