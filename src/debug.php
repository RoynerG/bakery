<?php
declare(strict_types=1);

/**
 * Helpers de debug.
 * Escriben en public/uploads/debug.log y pueden imprimirse en pantalla
 * si la URL trae ?debug=1.
 *
 * Defensivo: cualquier error dentro de debug NO debe romper la pagina.
 */

if (!function_exists('debug_log')) {
    function debug_log($msg): void
    {
        try {
            $line = '[' . date('Y-m-d H:i:s') . '] '
                  . (is_string($msg) ? $msg : json_encode($msg))
                  . PHP_EOL;
            $path = __DIR__ . '/../public/uploads/debug.log';
            @file_put_contents($path, $line, FILE_APPEND);
        } catch (\Throwable $e) { /* silenciar */ }
    }
}

if (!function_exists('debug_enabled')) {
    function debug_enabled(): bool
    {
        if (!isset($_GET['debug'])) return false;
        $v = strtolower((string)$_GET['debug']);
        return in_array($v, ['1', 'true', 'yes'], true);
    }
}

if (!function_exists('debug_install')) {
    function debug_install(): void
    {
        try {
            if (!debug_enabled()) return;
            @ini_set('display_errors', '1');
            @ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);

            set_error_handler(function ($severity, $message, $file, $line) {
                debug_log("PHP $severity: $message in $file:$line");
                return false;
            });

            set_exception_handler(function ($e) {
                debug_log('UNCAUGHT ' . get_class($e) . ': ' . $e->getMessage());
                debug_log('  at ' . $e->getFile() . ':' . $e->getLine());
                debug_log('  trace: ' . $e->getTraceAsString());
                if (!headers_sent()) {
                    http_response_code(500);
                }
                echo '<pre style="background:#1a1a1a;color:#ff8;padding:20px;font-family:monospace;white-space:pre-wrap;">';
                echo '<b>UNCAUGHT EXCEPTION</b>' . PHP_EOL;
                echo '<b>' . htmlspecialchars(get_class($e)) . ':</b> ' . htmlspecialchars($e->getMessage()) . PHP_EOL;
                echo '<b>at:</b> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . PHP_EOL;
                echo '<b>trace:</b>' . PHP_EOL . htmlspecialchars($e->getTraceAsString());
                echo '</pre>';
                exit;
            });
        } catch (\Throwable $e) { /* no romper nada */ }
    }
}

if (!function_exists('debug_view_log')) {
    function debug_view_log(int $lines = 50): string
    {
        try {
            $path = __DIR__ . '/../public/uploads/debug.log';
            if (!is_file($path)) return '(log vacio)';
            $content = @file_get_contents($path);
            if (!$content) return '(no se pudo leer el log)';
            $arr = explode("\n", trim($content));
            $arr = array_slice($arr, -$lines);
            return implode("\n", $arr);
        } catch (\Throwable $e) {
            return '(error leyendo log)';
        }
    }
}
