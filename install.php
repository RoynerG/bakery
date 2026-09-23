<?php
/**
 * Instalador automático: crea la base de datos SQLite y carga
 * el esquema inicial + datos de ejemplo si las tablas están vacías.
 *
 * Se ejecuta desde src/bootstrap.php la primera vez que se
 * detecta que la BD no tiene la tabla `ingredientes`.
 */
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/helpers.php';

if (DB_DRIVER !== 'sqlite') {
    return;
}

$sqlPath = SQLITE_PATH;
if (!file_exists($sqlPath)) {
    @mkdir(dirname($sqlPath), 0775, true);
    @touch($sqlPath);
}

try {
    $pdo = new PDO('sqlite:' . $sqlPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    $schema = file_get_contents(__DIR__ . '/database/schema.sqlite.sql');
    if ($schema === false) {
        throw new RuntimeException('No se pudo leer database/schema.sqlite.sql');
    }
    $pdo->exec($schema);

} catch (Throwable $e) {
    error_log('Error en install.php: ' . $e->getMessage());
    // No abortamos: la página mostrará un error descriptivo si la conexión falla
}
