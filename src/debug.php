<?php
declare(strict_types=1);

/**
 * Helpers de debug.
 * Escriben en public/uploads/debug.log (que ya existe) y pueden
 * imprimirse en pantalla si la URL trae ?debug=1.
 */

if (!function_exists('debug_log')) {
    /**
     * Escribe una linea con timestamp en uploads/debug.log.
     */
    function debug_log(mixed $msg): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] '
              . (is_string($msg) ? $msg : json_encode($msg, JSON_UNESCAPED_UNICODE))
              . PHP_EOL;
        $path = __DIR__ . '/../public/uploads/debug.log';
        @file_put_contents($path, $line, FILE_APPEND);
    }
}

if (!function_exists('debug_enabled')) {
    /**
     * True si la URL trae ?debug=1 o ?debug=true (solo dev).
     */
    function debug_enabled(): bool
    {
        if (!isset($_GET['debug'])) return false;
        $v = strtolower((string)$_GET['debug']);
        return in_array($v, ['1', 'true', 'yes'], true);
    }
}

if (!function_exists('debug_install')) {
    /**
     * Activa display_errors + handler que loguea a archivo.
     * Llamar al inicio del bootstrap si debug esta activo.
     */
    function debug_install(): void
    {
        if (!debug_enabled()) return;
        @ini_set('display_errors', '1');
        @ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);

        set_error_handler(function ($severity, $message, $file, $line) {
            debug_log("PHP $severity: $message in $file:$line");
            return false; // deja que PHP siga su manejo normal
        });

        set_exception_handler(function ($e) {
            debug_log('UNCAUGHT ' . get_class($e) . ': ' . $e->getMessage());
            debug_log('  at ' . $e->getFile() . ':' . $e->getLine());
            debug_log('  trace: ' . $e->getTraceAsString());
            http_response_code(500);
            echo '<pre style="background:#1a1a1a;color:#ff8;padding:20px;font-family:monospace;">';
            echo '<b>UNCAUGHT EXCEPTION</b>' . PHP_EOL;
            echo '<b>' . htmlspecialchars(get_class($e)) . ':</b> ' . htmlspecialchars($e->getMessage()) . PHP_EOL;
            echo '<b>at:</b> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . PHP_EOL;
            echo '<b>trace:</b>' . PHP_EOL . htmlspecialchars($e->getTraceAsString());
            echo '</pre>';
            exit;
        });
    }
}

if (!function_exists('debug_view_log')) {
    /**
     * Devuelve las ultimas N lineas del log (para mostrarlas).
     */
    function debug_view_log(int $lines = 50): string
    {
        $path = __DIR__ . '/../public/uploads/debug.log';
        if (!is_file($path)) return '(log vacio)';
        $content = @file_get_contents($path);
        if (!$content) return '(no se pudo leer el log)';
        $arr = explode("\n", trim($content));
        $arr = array_slice($arr, -$lines);
        return implode("\n", $arr);
    }
}
