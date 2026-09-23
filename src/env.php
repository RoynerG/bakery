<?php
declare(strict_types=1);

/**
 * Carga variables de entorno desde un archivo .env (si existe).
 *
 * En Hostinger y otros hostings compartidos, normalmente NO se usa un
 * archivo .env en el repositorio: las variables se configuran desde el
 * panel del proveedor y quedan disponibles vía getenv() / $_ENV.
 *
 * Esta función:
 *   1. Lee /reposteria/.env si existe (ideal para desarrollo local).
 *   2. Define las variables que aún no estén en el entorno real.
 *
 * Uso:
 *   require_once __DIR__ . '/env.php';
 *   $dbHost = env('DB_HOST', 'localhost');
 */

if (!function_exists('env_path')) {
    function env_path(): string
    {
        // Un nivel arriba de /src (es decir, en la raíz del proyecto)
        return dirname(__DIR__) . '/.env';
    }
}

if (!function_exists('load_env')) {
    /**
     * Carga el archivo .env en el entorno si aún no fue cargado.
     * Idempotente: se puede llamar varias veces sin duplicar.
     */
    function load_env(?string $path = null): void
    {
        static $loaded = false;
        if ($loaded) return;

        $file = $path ?? env_path();
        if (!is_file($file) || !is_readable($file)) {
            $loaded = true;
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            $loaded = true;
            return;
        }

        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '' || str_starts_with($trim, '#')) continue;

            if (!preg_match('/^\s*([A-Z0-9_]+)\s*=\s*(.*)$/i', $trim, $m)) continue;

            $key   = $m[1];
            $value = $m[2];

            // Quitar comillas si las tiene
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            // Comentarios trailing tipo  KEY=value # comentario
            if (($pos = strpos($value, ' #')) !== false) {
                $value = substr($value, 0, $pos);
            }

            $value = trim($value);

            // No pisar variables del entorno real (tienen prioridad)
            if (getenv($key) === false && !isset($_ENV[$key]) && !isset($_SERVER[$key])) {
                putenv("$key=$value");
                $_ENV[$key]    = $value;
                $_SERVER[$key] = $value;
            }
        }

        $loaded = true;
    }
}

if (!function_exists('env')) {
    /**
     * Obtiene una variable de entorno con valor por defecto.
     * Busca primero en el .env cargado, luego en el entorno real.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = $_ENV[$key]    ?? $_SERVER[$key] ?? null;
        }
        if ($value === null || $value === '') {
            return $default;
        }

        // Conversión automática de booleanos y números
        $lower = strtolower((string)$value);
        if ($lower === 'true')  return true;
        if ($lower === 'false') return false;
        if ($lower === 'null')  return null;

        return $value;
    }
}