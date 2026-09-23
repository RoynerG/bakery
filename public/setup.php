<?php
/**
 * Setup inicial: aparece SOLO la primera vez, cuando no existe
 * ningún usuario en la tabla `usuarios`.
 *
 * Crea el primer usuario administrador y lo redirige al login.
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Usuario;
use App\Auth;

// Si ya hay usuarios, el setup está cerrado
if (Usuario::existsAny()) {
    flash('error', 'El setup ya fue completado.');
    redirect('login.php');
}

$errores = [];
$old = ['usuario' => '', 'nombre' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['usuario'] = trim($_POST['usuario'] ?? '');
    $old['nombre']  = trim($_POST['nombre']  ?? '');
    $pass  = $_POST['password']      ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';

    if (mb_strlen($old['usuario']) < 3) {
        $errores[] = 'El usuario debe tener al menos 3 caracteres.';
    }
    if (!preg_match('/^[A-Za-z0-9_.\-]+$/', $old['usuario'])) {
        $errores[] = 'El usuario solo puede contener letras, números, guion bajo, punto o guion.';
    }
    if (mb_strlen($pass) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    }
    if ($pass !== $pass2) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        try {
            Usuario::create($old['usuario'], $pass, $old['nombre'] !== '' ? $old['nombre'] : null);
            flash('success', '¡Cuenta creada! Ya puedes iniciar sesión.');
            redirect('login.php');
        } catch (\Throwable $e) {
            $errores[] = $e->getMessage();
        }
    }
}

$titulo = 'Configuración inicial';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo) ?> · <?= e(APP_NAME) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" type="image/jpeg" href="<?= asset('img/logo.jpg') ?>">
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Quicksand', system-ui, sans-serif; background: linear-gradient(135deg,#fff0f5 0%,#fff3e0 100%); min-height:100vh; }
    .font-sweet { font-family: 'Pacifico', cursive; }
    .card { background: rgba(255,255,255,.9); border-radius: 1.5rem; box-shadow: 0 10px 40px rgba(255,107,157,.15); }
  </style>
</head>
<body class="flex items-center justify-center p-4">
  <div class="card w-full max-w-md p-8 border-2 border-rose-100">
    <div class="text-center mb-6">
      <img src="<?= asset('img/logo.jpg') ?>" alt="<?= e(APP_NAME) ?>"
           class="h-20 w-20 rounded-full mx-auto mb-3 object-cover border-4 border-rose-200 shadow-md">
      <h1 class="font-sweet text-3xl text-rose-500">¡Hola!</h1>
      <p class="text-chocolate-700 mt-2">
        Crea tu cuenta de administrador para empezar a usar <b><?= e(APP_NAME) ?></b>.
      </p>
      <p class="text-xs text-chocolate-500 mt-1">Esta página solo aparece la primera vez.</p>
    </div>

    <?php if (!empty($errores)): ?>
      <div class="bg-rose-100 border-2 border-rose-300 text-chocolate-700 rounded-2xl px-4 py-3 mb-4 text-sm">
        <ul class="list-disc pl-5 space-y-1">
          <?php foreach ($errores as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-4" autocomplete="off">
      <?= csrf_field() ?>

      <div>
        <label class="block text-sm font-semibold text-chocolate-700 mb-1">Nombre (opcional)</label>
        <input type="text" name="nombre" value="<?= e($old['nombre']) ?>"
               class="w-full px-4 py-3 rounded-2xl border-2 border-rose-100 focus:border-rose-400 focus:outline-none bg-white"
               placeholder="Tu nombre">
      </div>

      <div>
        <label class="block text-sm font-semibold text-chocolate-700 mb-1">Usuario *</label>
        <input type="text" name="usuario" value="<?= e($old['usuario']) ?>" required minlength="3"
               class="w-full px-4 py-3 rounded-2xl border-2 border-rose-100 focus:border-rose-400 focus:outline-none bg-white"
               placeholder="ej. admin" pattern="[A-Za-z0-9_.\-]+">
        <p class="text-xs text-chocolate-500 mt-1">Letras, números, guion bajo, punto o guion.</p>
      </div>

      <div>
        <label class="block text-sm font-semibold text-chocolate-700 mb-1">Contraseña *</label>
        <input type="password" name="password" required minlength="6"
               class="w-full px-4 py-3 rounded-2xl border-2 border-rose-100 focus:border-rose-400 focus:outline-none bg-white"
               placeholder="Mínimo 6 caracteres">
      </div>

      <div>
        <label class="block text-sm font-semibold text-chocolate-700 mb-1">Confirma la contraseña *</label>
        <input type="password" name="password_confirm" required minlength="6"
               class="w-full px-4 py-3 rounded-2xl border-2 border-rose-100 focus:border-rose-400 focus:outline-none bg-white">
      </div>

      <button type="submit"
              class="w-full bg-rose-400 hover:bg-rose-500 text-white font-bold py-3 px-6 rounded-2xl shadow-md transition-all">
        ✨ Crear mi cuenta
      </button>
    </form>
  </div>
</body>
</html>