<?php
/**
 * Instalador automático: crea las tablas y carga datos de ejemplo
 * si la base de datos está vacía.
 *
 * Soporta SQLite y MySQL/MariaDB.
 *
 * Se ejecuta desde src/bootstrap.php la primera vez que se detecta
 * que la tabla `ingredientes` no existe.
 */
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/helpers.php';

try {
    $db = App\Database::getInstance();

    if (DB_DRIVER === 'sqlite') {
        // Crear archivo SQLite si no existe
        $sqlPath = SQLITE_PATH;
        if (!file_exists($sqlPath)) {
            @mkdir(dirname($sqlPath), 0775, true);
            @touch($sqlPath);
        }

        $schema = file_get_contents(__DIR__ . '/database/schema.sqlite.sql');
        if ($schema === false) {
            throw new RuntimeException('No se pudo leer database/schema.sqlite.sql');
        }

        // SQLite ejecuta múltiples sentencias separadas por ;
        $db->pdo()->exec($schema);
    }
    elseif (DB_DRIVER === 'mysql') {
        $schema = file_get_contents(__DIR__ . '/database/schema.mysql.sql');
        if ($schema === false) {
            throw new RuntimeException('No se pudo leer database/schema.mysql.sql');
        }

        // MySQL no ejecuta múltiples sentencias en un solo exec()
        // cuando emulated prepares están desactivadas. Las separamos.
        $statements = array_filter(
            array_map('trim', explode(';', $schema)),
            fn($s) => $s !== '' && !str_starts_with($s, '--')
        );

        foreach ($statements as $stmt) {
            $db->pdo()->exec($stmt);
        }
    }
} catch (Throwable $e) {
    error_log('Error en install.php: ' . $e->getMessage());
    // No abortamos: la página mostrará un error descriptivo si la conexión falla
}