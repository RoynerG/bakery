<?php
/** @var string $title */
$title = $title ?? APP_NAME;
$current = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?> · <?= e(APP_NAME) ?></title>

  <meta name="description" content="Sistema integral para administrar recetas de repostería con costeo automático.">
  <meta name="theme-color" content="#ff6b9d">

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            display: ['"Quicksand"', 'system-ui', 'sans-serif'],
            sweet:   ['"Pacifico"', 'cursive'],
          },
          colors: {
            cream:    { 50: '#fffaf3', 100: '#fff3e0', 200: '#ffe5b4' },
            rose:     { 50: '#fff0f5', 100: '#ffd6e7', 200: '#ffb3d1', 300: '#ff8fb7', 400: '#ff6b9d', 500: '#e8528a', 600: '#c93a73' },
            chocolate:{ 50: '#f7efe6', 100: '#e8d4bd', 500: '#8b4513', 700: '#5d2f0c', 900: '#3a1d05' },
            mint:     { 100: '#d4f4e2', 300: '#7dd3a8', 500: '#3eb97a' },
          },
          keyframes: {
            floaty:  { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-10px)' } },
            wiggle:  { '0%,100%': { transform: 'rotate(-3deg)' }, '50%': { transform: 'rotate(3deg)' } },
            pop:     { '0%': { transform: 'scale(.85)', opacity: '0' }, '100%': { transform: 'scale(1)', opacity: '1' } },
            drip:    { '0%,100%': { transform: 'translateY(0) scaleY(1)' }, '50%': { transform: 'translateY(4px) scaleY(.95)' } },
            shimmer: { '0%': { backgroundPosition: '-200% 0' }, '100%': { backgroundPosition: '200% 0' } },
            sprinkle:{ '0%': { transform: 'translate(0,0) rotate(0)' }, '100%': { transform: 'translate(20px,-30px) rotate(180deg)' } },
          },
          animation: {
            floaty:  'floaty 4s ease-in-out infinite',
            wiggle:  'wiggle 1.4s ease-in-out infinite',
            pop:     'pop .35s ease-out both',
            drip:    'drip 2.4s ease-in-out infinite',
            shimmer: 'shimmer 2.5s linear infinite',
            sprinkle:'sprinkle 8s linear infinite',
          },
        }
      }
    }
  </script>

  <!-- Fuentes -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- App JS (define componentes Alpine.js: recipeWizard, confirmDelete) -->
  <script defer src="<?= asset('js/app.js') ?>"></script>

  <!-- Alpine.js -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- Estilos personalizados -->
  <link rel="stylesheet" href="<?= asset('css/styles.css') ?>">

  <!-- Favicon: preferimos el logo real si existe, fallback al cupcake SVG -->
  <link rel="icon" type="image/jpeg" href="<?= asset('img/logo.jpg') ?>">
  <link rel="shortcut icon" href="<?= asset('img/logo.jpg') ?>">
</head>
<body class="font-display bg-cream-50 text-chocolate-900 antialiased min-h-screen relative overflow-x-hidden">

  <!-- Fondo animado con dulces flotantes -->
  <div class="sweet-bg" aria-hidden="true">
    <span class="sweet sweet-1">🧁</span>
    <span class="sweet sweet-2">🍰</span>
    <span class="sweet sweet-3">🍩</span>
    <span class="sweet sweet-4">🍪</span>
    <span class="sweet sweet-5">🍫</span>
    <span class="sweet sweet-6">🍬</span>
    <span class="sweet sweet-7">🥧</span>
    <span class="sweet sweet-8">🍮</span>
  </div>

  <!-- NAV -->
  <header class="relative z-20">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <div class="flex items-center justify-between bg-white/70 backdrop-blur-md rounded-full shadow-lg px-6 py-3 border-2 border-rose-100">
        <!-- Logo -->
        <a href="<?= url('index.php') ?>" class="flex items-center gap-3 group">
          <img src="<?= asset('img/logo.jpg') ?>" alt="<?= e(APP_NAME) ?>"
               class="h-12 w-12 rounded-full object-cover border-2 border-rose-200 shadow-sm group-hover:scale-105 transition-transform">
          <span class="font-sweet text-2xl text-rose-400 group-hover:text-rose-500 transition-colors"><?= e(APP_NAME) ?></span>
        </a>

        <!-- Menú desktop -->
        <div class="hidden md:flex items-center gap-1">
          <?php
          $navItems = [
            ['index.php',     '🏠', 'Recetario'],
            ['inventario.php','📦', 'Inventario'],
            ['receta.php',    '✨', 'Nueva receta'],
          ];
          foreach ($navItems as [$href, $icon, $label]):
            $active = $current === $href;
          ?>
            <a href="<?= url($href) ?>"
               class="px-4 py-2 rounded-full font-semibold text-sm transition-all flex items-center gap-2
                      <?= $active
                          ? 'bg-rose-400 text-white shadow-md shadow-rose-300/50'
                          : 'text-chocolate-700 hover:bg-rose-50 hover:text-rose-500' ?>">
              <span><?= $icon ?></span><span><?= e($label) ?></span>
            </a>
          <?php endforeach; ?>
        </div>

        <!-- Usuario / login (desktop) -->
        <div class="hidden md:flex items-center gap-2 ml-2">
          <?php if (\App\Auth::check()): ?>
            <span class="text-sm text-chocolate-700 font-semibold flex items-center gap-2">
              <span class="w-8 h-8 rounded-full bg-rose-200 text-rose-700 flex items-center justify-center text-sm font-bold">
                <?= e(mb_substr(\App\Auth::displayName(), 0, 1)) ?>
              </span>
              <?= e(\App\Auth::displayName()) ?>
            </span>
            <a href="<?= url('logout.php') ?>"
               class="px-3 py-2 rounded-full font-semibold text-sm text-chocolate-700 hover:bg-rose-50 hover:text-rose-500 transition-all flex items-center gap-1"
               title="Cerrar sesión">
              <span>🚪</span> Salir
            </a>
          <?php else: ?>
            <a href="<?= url('login.php') ?>"
               class="px-4 py-2 rounded-full font-semibold text-sm bg-rose-400 hover:bg-rose-500 text-white shadow-md transition-all flex items-center gap-1">
              <span>🔑</span> Entrar
            </a>
          <?php endif; ?>
        </div>

        <!-- Menú móvil -->
        <button x-data="{open:false}" @click="open=!open" class="md:hidden p-2 rounded-full hover:bg-rose-50">
          <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>

      <!-- Menú móvil expandido -->
      <div x-data="{open:false}" @click.outside="open=false" class="md:hidden mt-2">
        <button @click="open=!open" class="sr-only">menú</button>
        <div x-show="open" x-transition x-cloak class="bg-white rounded-3xl shadow-xl border-2 border-rose-100 p-3 space-y-1">
          <?php foreach ($navItems as [$href, $icon, $label]): ?>
            <a href="<?= url($href) ?>" class="block px-4 py-3 rounded-2xl hover:bg-rose-50 font-semibold flex items-center gap-2">
              <span><?= $icon ?></span><?= e($label) ?>
            </a>
          <?php endforeach; ?>
          <div class="border-t border-rose-100 my-1"></div>
          <?php if (\App\Auth::check()): ?>
            <div class="px-4 py-2 text-sm text-chocolate-700">
              👤 <?= e(\App\Auth::displayName()) ?>
            </div>
            <a href="<?= url('logout.php') ?>" class="block px-4 py-3 rounded-2xl hover:bg-rose-50 font-semibold flex items-center gap-2">
              <span>🚪</span> Salir
            </a>
          <?php else: ?>
            <a href="<?= url('login.php') ?>" class="block px-4 py-3 rounded-2xl bg-rose-400 text-white font-semibold flex items-center gap-2">
              <span>🔑</span> Entrar
            </a>
          <?php endif; ?>
        </div>
      </div>
    </nav>
  </header>

  <!-- FLASH MESSAGES -->
  <?php if ($msg = flash('success')): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-2 relative z-10">
      <div class="bg-mint-100 border-2 border-mint-300 text-chocolate-700 rounded-2xl px-5 py-3 shadow-md flex items-center gap-3 animate-pop">
        <span class="text-2xl">🎉</span>
        <span class="font-semibold"><?= e($msg) ?></span>
      </div>
    </div>
  <?php endif; ?>
  <?php if ($msg = flash('error')): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-2 relative z-10">
      <div class="bg-rose-100 border-2 border-rose-300 text-chocolate-700 rounded-2xl px-5 py-3 shadow-md flex items-center gap-3 animate-pop">
        <span class="text-2xl">😢</span>
        <span class="font-semibold"><?= e($msg) ?></span>
      </div>
    </div>
  <?php endif; ?>

  <!-- MAIN -->
  <main class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
