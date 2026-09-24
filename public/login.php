<?php
/**
 * Login: formulario + procesamiento.
 * Si no existe ningún usuario, redirige al setup inicial.
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Usuario;
use App\Auth;

// Si no hay usuarios, forzar setup
if (!Usuario::existsAny()) {
    redirect('setup.php');
}

// Si ya está autenticado, mandarlo al inicio
if (Auth::check()) {
    redirect('index.php');
}

$errores = [];
$usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $usuario = trim($_POST['usuario']  ?? '');
    $pass    =         $_POST['password'] ?? '';

    if ($usuario === '' || $pass === '') {
        $errores[] = 'Usuario y contraseña son obligatorios.';
    } else {
        $user = Auth::attempt($usuario, $pass);
        if (!$user) {
            $errores[] = 'Usuario o contraseña incorrectos.';
        } else {
            flash('success', '¡Bienvenido/a ' . ($user['nombre'] ?: $user['usuario']) . '!');
            redirect('index.php');
        }
    }
}

$titulo = 'Iniciar sesión';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo) ?> · <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= asset('css/styles.css') ?>?v=5">
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
      <h1 class="font-sweet text-3xl text-rose-500"><?= e(APP_NAME) ?></h1>
      <p class="text-chocolate-700 mt-2">Inicia sesión para administrar tu recetario.</p>
    </div>

    <?php if (!empty($errores)): ?>
      <div class="bg-rose-100 border-2 border-rose-300 text-chocolate-700 rounded-2xl px-4 py-3 mb-4 text-sm">
        <?php foreach ($errores as $err): ?>
          <p>😢 <?= e($err) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
      <div class="bg-mint-100 border-2 border-mint-300 text-chocolate-700 rounded-2xl px-4 py-3 mb-4 text-sm">
        🎉 <?= e($msg) ?>
      </div>
    <?php endif; ?>
    <?php if ($msg = flash('error')): ?>
      <div class="bg-rose-100 border-2 border-rose-300 text-chocolate-700 rounded-2xl px-4 py-3 mb-4 text-sm">
        <?= e($msg) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-4" autocomplete="on">
      <?= csrf_field() ?>

      <div>
        <label class="block text-sm font-semibold text-chocolate-700 mb-1">Usuario</label>
        <input type="text" name="usuario" value="<?= e($usuario) ?>" required autofocus
               class="w-full px-4 py-3 rounded-2xl border-2 border-rose-100 focus:border-rose-400 focus:outline-none bg-white"
               placeholder="tu usuario">
      </div>

      <div>
        <label class="block text-sm font-semibold text-chocolate-700 mb-1">Contraseña</label>
        <input type="password" name="password" required
               class="w-full px-4 py-3 rounded-2xl border-2 border-rose-100 focus:border-rose-400 focus:outline-none bg-white"
               placeholder="••••••••">
      </div>

      <button type="submit"
              class="w-full bg-rose-400 hover:bg-rose-500 text-white font-bold py-3 px-6 rounded-2xl shadow-md transition-all">
        🔑 Entrar
      </button>
    </form>
  </div>
</body>
</html>