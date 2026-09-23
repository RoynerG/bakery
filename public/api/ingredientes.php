<?php
/**
 * API: Lista de ingredientes en formato JSON.
 * Usado por el formulario de recetas (Alpine.js) para llenar
 * dinámicamente los selects sin recargar la página.
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $ingredientes = App\Models\Ingrediente::all();
    echo json_encode([
        'ok'           => true,
        'ingredientes' => $ingredientes,
        'count'        => count($ingredientes),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
