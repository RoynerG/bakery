<?php
/**
 * Controlador de acciones POST.
 * Centraliza operaciones que no son submits de formularios
 * en una página visible (por ejemplo, eliminar receta).
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Receta;
use App\Models\Ingrediente;
use App\Auth;

// Toda acción requiere autenticación
Auth::require();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

csrf_verify();

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'eliminar_receta':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0 && Receta::delete($id)) {
                flash('success', '🗑️ Receta eliminada.');
            } else {
                flash('error', 'No se pudo eliminar la receta.');
            }
            redirect('index.php');
            break;

        case 'eliminar_ingrediente':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0 && Ingrediente::delete($id)) {
                flash('success', '🗑️ Ingrediente eliminado.');
            } else {
                flash('error', 'No se puede eliminar: el ingrediente está en uso.');
            }
            redirect('inventario.php');
            break;

        default:
            flash('error', 'Acción desconocida.');
            redirect('index.php');
    }
} catch (Throwable $e) {
    flash('error', $e->getMessage());
    redirect('index.php');
}
