<?php
declare(strict_types=1);

/**
 * Funciones auxiliares de la aplicación.
 */

if (!function_exists('e')) {
    /** Escape HTML seguro */
    function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /** Genera una URL relativa al directorio público */
    function url(string $path = ''): string
    {
        $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $base = $base === '/' || $base === '.' ? '' : rtrim($base, '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /** URL a un asset estático */
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('upload_url')) {
    /** URL a un archivo subido */
    function upload_url(?string $filename): string
    {
        if (empty($filename)) return asset('img/placeholder.svg');
        return url(UPLOADS_URL . '/' . $filename);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('flash')) {
    function flash(?string $key = null, ?string $value = null): ?string
    {
        if ($key === null) return null;

        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        if (isset($_SESSION['_flash'][$key])) {
            $msg = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $msg;
        }
        return null;
    }
}

if (!function_exists('format_money')) {
    /**
     * Formatea un monto como pesos chilenos (CLP):
     *   - Sin decimales
     *   - Punto como separador de miles
     *   - Símbolo $ adelante
     *   Ej: 12500 -> "$12.500"
     */
    function format_money(float $value): string
    {
        return '$' . number_format((float)$value, 0, ',', '.');
    }
}

if (!function_exists('parse_clp')) {
    /**
     * Parsea un monto en formato chileno: acepta "1.200", "1200",
     * "1.200.000". Devuelve float. Si llega vacío o no numérico,
     * devuelve 0. Los CLP no tienen decimales, así que siempre se
     * devuelve un entero (truncando o redondeando).
     */
    function parse_clp(mixed $value): float
    {
        if ($value === null || $value === '') return 0.0;
        $clean = (string)$value;
        $clean = str_replace(['.', ','], ['', '.'], $clean); // "1.200,5" -> "1200.5"
        // Si trae punto decimal, mantenerlo; si no, entero.
        if (strpos($clean, '.') !== false) {
            return (float)$clean;
        }
        return (float)((int)$clean);
    }
}

if (!function_exists('get_config')) {
    /**
     * Lee un valor de la tabla `config`. Si no existe, devuelve $default.
     * Tolerante: si la tabla no existe aun (migracion pendiente),
     * devuelve el default en vez de explotar.
     */
    function get_config(string $clave, ?string $default = null): ?string
    {
        static $cache = [];
        if (array_key_exists($clave, $cache)) return $cache[$clave];
        try {
            $row = \App\Database::getInstance()->fetchOne(
                'SELECT valor FROM config WHERE clave = ?',
                [$clave]
            );
            $val = $row ? ($row['valor'] ?? $default) : $default;
            $cache[$clave] = $val;
            return $val;
        } catch (\Throwable $e) {
            $cache[$clave] = $default;
            return $default;
        }
    }
}

if (!function_exists('set_config')) {
    /**
     * Inserta/actualiza un valor en la tabla `config`.
     */
    function set_config(string $clave, ?string $valor): void
    {
        \App\Database::getInstance()->execute(
            'INSERT INTO config (clave, valor) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)',
            [$clave, $valor]
        );
    }
}

if (!function_exists('categoria_variantes')) {
    /**
     * Devuelve los tamanos/precios compartidos de una categoria del
     * catalogo (clasica, premium, destacado). Ordenados por 'orden'.
     * Tolerante: devuelve array vacio si la tabla no existe.
     */
    function categoria_variantes(string $tipo): array
    {
        try {
            return \App\Database::getInstance()->fetchAll(
                'SELECT * FROM categoria_variantes WHERE tipo = ? ORDER BY orden ASC, id ASC',
                [$tipo]
            );
        } catch (\Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('format_unidad')) {
    function format_unidad(string $unidad, float $cantidad): string
    {
        return rtrim(rtrim(number_format($cantidad, 3, '.', ''), '0'), '.') . ' ' . $unidad . (abs($cantidad) != 1 ? 's' : '');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['_csrf'] ?? '', $token)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }
    }
}

if (!function_exists('handle_upload')) {
    /**
     * Procesa la subida de una imagen.
     * @return array{ok:bool, filename?:string, error?:string}
     */
    function handle_upload(string $field): array
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'filename' => null];
        }
        $f = $_FILES[$field];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Error al subir el archivo (código ' . $f['error'] . ').'];
        }
        if ($f['size'] > MAX_UPLOAD_SIZE) {
            return ['ok' => false, 'error' => 'La imagen excede el tamaño máximo (5 MB).'];
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $f['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
            return ['ok' => false, 'error' => 'Tipo de archivo no permitido. Usa JPG, PNG, WebP o GIF.'];
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'jpg',
        };
        $filename = 'rec_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        if (!is_dir(UPLOADS_PATH)) {
            mkdir(UPLOADS_PATH, 0775, true);
        }
        if (!move_uploaded_file($f['tmp_name'], UPLOADS_PATH . '/' . $filename)) {
            return ['ok' => false, 'error' => 'No se pudo guardar el archivo en el servidor.'];
        }
        return ['ok' => true, 'filename' => $filename];
    }
}

if (!function_exists('old')) {
    /** Recupera valor antiguo tras un error de validación */
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('keep_old')) {
    function keep_old(array $data): void
    {
        $_SESSION['_old'] = $data;
    }
}

if (!function_exists('clear_old')) {
    function clear_old(): void
    {
        unset($_SESSION['_old']);
    }
}
