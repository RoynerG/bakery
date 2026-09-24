<?php
/**
 * Captura errores y los loguea a un archivo accesible via .txt.
 * Borralo despues de debuggear.
 */
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$logFile = __DIR__ . '/debug-capture.txt';

function captureErrors(string $logFile): void {
    set_error_handler(function($severity, $message, $file, $line) use ($logFile) {
        $entry = sprintf("[%s] %s: %s in %s:%d\n",
            date('H:i:s'), $severity, $message, $file, $line);
        @file_put_contents($logFile, $entry, FILE_APPEND);
        return false;
    });
    set_exception_handler(function($e) use ($logFile) {
        $entry = sprintf("[%s] EXCEPTION %s: %s in %s:%d\n  trace: %s\n",
            date('H:i:s'), get_class($e), $e->getMessage(),
            $e->getFile(), $e->getLine(),
            str_replace("\n", " | ", $e->getTraceAsString()));
        @file_put_contents($logFile, $entry, FILE_APPEND);
        http_response_code(500);
        echo "Error capturado. Ver debug-capture.txt\n";
    });
}

captureErrors($logFile);

try {
    require_once __DIR__ . '/../src/bootstrap.php';
    require_once __DIR__ . '/../src/helpers.php';

    $script = $_GET['script'] ?? 'usuarios.php';
    $full = __DIR__ . '/' . $script;

    if (!preg_match('/^[a-z0-9_\-]+\.php$/i', $script)) {
        throw new RuntimeException('Nombre invalido');
    }
    if (!is_file($full)) {
        throw new RuntimeException("Archivo no existe: $full");
    }

    // Capturamos el output del script
    ob_start();
    include $full;
    $output = ob_get_clean();
    echo $output;

} catch (Throwable $e) {
    @file_put_contents($logFile,
        sprintf("[%s] CAPTURED %s: %s in %s:%d\n",
            date('H:i:s'), get_class($e), $e->getMessage(),
            $e->getFile(), $e->getLine()), FILE_APPEND);
    http_response_code(500);
    echo "ERROR capturado a debug-capture.txt\n";
    echo "Tipo: " . get_class($e) . "\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
