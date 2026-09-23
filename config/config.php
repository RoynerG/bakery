<?php
/**
 * Configuración general de la aplicación.
 *
 * Las credenciales y datos sensibles se leen desde variables de entorno.
 * En desarrollo local se cargan desde el archivo /.env (NO versionado).
 * En Hostinger (u otro hosting) configúralas desde el panel del proveedor.
 *
 * Variables reconocidas:
 *   APP_ENV        (production | development)     default: production
 *   APP_DEBUG      (true | false)                  default: false
 *   DB_DRIVER      (sqlite | mysql)                default: mysql
 *   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_CHARSET
 *   SQLITE_PATH                                    default: database/reposteria.sqlite
 */

// 1. Cargar variables de entorno
require_once __DIR__ . '/../src/env.php';
load_env();

// ========== ENTORNO ==========
define('APP_ENV',   env('APP_ENV', 'production'));
define('APP_DEBUG', filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN));

// ========== DRIVER DE BASE DE DATOS ==========
// Opciones: 'sqlite' | 'mysql'. En Hostinger normalmente usarás 'mysql'.
define('DB_DRIVER', strtolower((string) env('DB_DRIVER', 'mysql')));

// ========== DATOS GENERALES ==========
define('APP_NAME',     env('APP_NAME', 'Dulce Rinconcito'));
define('APP_TAGLINE',  env('APP_TAGLINE', 'Pastelería y repostería artesanal'));
define('APP_VERSION',  env('APP_VERSION', '1.0.0'));

// ========== MYSQL ==========
define('DB_HOST',     env('DB_HOST', 'localhost'));
define('DB_PORT',     (string) env('DB_PORT', '3306'));
define('DB_NAME',     env('DB_NAME', 'reposteria'));
define('DB_USER',     env('DB_USER', 'root'));
define('DB_PASS',     (string) env('DB_PASS', ''));
define('DB_CHARSET',  env('DB_CHARSET', 'utf8mb4'));

// ========== SQLITE ==========
define('SQLITE_PATH', __DIR__ . '/../database/reposteria.sqlite');

// ========== RUTAS ==========
define('APP_ROOT',      __DIR__ . '/..');
define('PUBLIC_PATH',   APP_ROOT . '/public');
define('UPLOADS_PATH',  PUBLIC_PATH . '/uploads');
define('UPLOADS_URL',   'uploads');

// ========== SEGURIDAD ==========
define('SESSION_NAME', env('SESSION_NAME', 'reposteria_sess'));

// ========== UPLOADS ==========
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// ========== ZONA HORARIA ==========
date_default_timezone_set(env('APP_TIMEZONE', 'America/Mexico_City'));

// ========== ERRORES ==========
if (APP_DEBUG || APP_ENV !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// ========== SESIÓN ==========
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}