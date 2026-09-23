<?php
/**
 * API: eventos de la agenda en formato FullCalendar.
 *
 * Parámetros GET que envía FullCalendar:
 *   start  -> YYYY-MM-DD (inicio del rango visible)
 *   end    -> YYYY-MM-DD (fin del rango visible)
 *
 * Devuelve JSON con eventos: [{id,title,start,allDay,backgroundColor,...}]
 */
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/helpers.php';

use App\Models\Agenda;
use App\Auth;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Protegemos con login: solo el admin ve su agenda
Auth::require();

$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end']   ?? date('Y-m-t');

// Sanitizar fechas (FullCalendar envía YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end))   $end   = date('Y-m-t');

try {
    $eventos = Agenda::between($start, $end);
    $payload = array_map(fn($e) => Agenda::toCalendarEvent($e), $eventos);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}