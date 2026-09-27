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

  <!-- Tailwind CSS compilado localmente (tema en tailwind.config.js) -->
  <link rel="stylesheet" href="<?= asset('css/styles.css') ?>?v=6">

  <!-- Fuentes -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- App JS (define componentes Alpine.js: recipeWizard, confirmDelete, calcCostoBase) -->
  <script defer src="<?= asset('js/app.js') ?>?v=9"></script>

  <!-- Alpine.js -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

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

  <?php
  $navItems = [
    ['index.php',     '🏠', 'Inicio', 'main'],
    ['recetas.php',   '🍰', 'Recetas', 'production'],
    ['tortas.php',    '🎂', 'Armar torta', 'production'],
    ['inventario.php','📦', 'Inventario', 'production'],
    ['productos.php', '📖', 'Catalogo', 'main'],
    ['notas.php',     '📝', 'Notas', 'management'],
    ['agenda.php',    '📅', 'Agenda', 'management'],
  ];

  $isActiveNav = static function (string $href, string $label) use ($current): bool {
      if ($label === 'Recetas') {
          return in_array($current, ['recetas.php', 'receta.php', 'ver-receta.php'], true);
      }
      if ($label === 'Catalogo') {
          return in_array($current, ['productos.php', 'producto.php', 'catalogo.php'], true);
      }
      return $current === $href;
  };

  $mainNav = array_values(array_filter($navItems, fn($item) => $item[3] === 'main'));
  $productionNav = array_values(array_filter($navItems, fn($item) => $item[3] === 'production'));
  $managementNav = array_values(array_filter($navItems, fn($item) => $item[3] === 'management'));
  $productionActive = array_reduce($productionNav, fn($carry, $item) => $carry || $isActiveNav($item[0], $item[2]), false);
  $managementActive = array_reduce($managementNav, fn($carry, $item) => $carry || $isActiveNav($item[0], $item[2]), false);
  ?>

  <!-- NAV -->
  <header class="relative z-20">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <div x-data="{ mobileOpen: false, productionOpen: false, managementOpen: false }"
           @keydown.escape.window="mobileOpen=false; productionOpen=false; managementOpen=false"
           @click.outside="mobileOpen=false; productionOpen=false; managementOpen=false"
           class="relative bg-white/75 backdrop-blur-md rounded-3xl lg:rounded-full shadow-lg px-4 sm:px-5 lg:px-6 py-3 border-2 border-rose-100">
        <div class="flex items-center justify-between gap-4">
        <!-- Logo -->
        <a href="<?= url('index.php') ?>" class="flex items-center gap-3 group min-w-0">
          <img src="<?= asset('img/logo.jpg') ?>" alt="<?= e(APP_NAME) ?>"
               class="h-11 w-11 sm:h-12 sm:w-12 rounded-full object-cover border-2 border-rose-200 shadow-sm group-hover:scale-105 transition-transform shrink-0">
          <span class="font-sweet text-xl sm:text-2xl text-rose-400 group-hover:text-rose-500 transition-colors truncate"><?= e(APP_NAME) ?></span>
        </a>

        <!-- Menú desktop -->
        <div class="hidden lg:flex items-center gap-1">
          <?php foreach ($mainNav as [$href, $icon, $label]): ?>
            <?php $staticActive = $isActiveNav($href, $label); ?>
            <a href="<?= url($href) ?>"
               class="px-4 py-2 rounded-full font-semibold text-sm transition-all flex items-center gap-2
                      <?= $staticActive
                          ? 'bg-rose-400 text-white shadow-md shadow-rose-300/50'
                          : 'text-chocolate-700 hover:bg-rose-50 hover:text-rose-500' ?>">
              <span><?= $icon ?></span><span><?= e($label) ?></span>
            </a>
          <?php endforeach; ?>

          <div class="relative" @click.outside="productionOpen=false">
            <button type="button"
                    @click="productionOpen=!productionOpen; managementOpen=false"
                    :aria-expanded="productionOpen.toString()"
                    class="px-4 py-2 rounded-full font-semibold text-sm transition-all flex items-center gap-2
                           <?= $productionActive
                               ? 'bg-rose-400 text-white shadow-md shadow-rose-300/50'
                               : 'text-chocolate-700 hover:bg-rose-50 hover:text-rose-500' ?>">
              <span>🥣</span>
              <span>Produccion</span>
              <svg class="w-4 h-4 transition-transform" :class="productionOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <div x-show="productionOpen" x-transition x-cloak
                 class="absolute left-0 mt-3 w-60 rounded-3xl border-2 border-rose-100 bg-white shadow-xl p-2 z-50">
              <?php foreach ($productionNav as [$href, $icon, $label]): ?>
                <?php $staticActive = $isActiveNav($href, $label); ?>
                <a href="<?= url($href) ?>"
                   class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all
                          <?= $staticActive ? 'bg-rose-50 text-rose-500' : 'text-chocolate-700 hover:bg-cream-50 hover:text-rose-500' ?>">
                  <span class="w-8 h-8 rounded-full bg-rose-50 flex items-center justify-center"><?= $icon ?></span>
                  <span><?= e($label) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="relative" @click.outside="managementOpen=false">
            <button type="button"
                    @click="managementOpen=!managementOpen; productionOpen=false"
                    :aria-expanded="managementOpen.toString()"
                    class="px-4 py-2 rounded-full font-semibold text-sm transition-all flex items-center gap-2
                           <?= $managementActive
                               ? 'bg-rose-400 text-white shadow-md shadow-rose-300/50'
                               : 'text-chocolate-700 hover:bg-rose-50 hover:text-rose-500' ?>">
              <span>🗂️</span>
              <span>Gestion</span>
              <svg class="w-4 h-4 transition-transform" :class="managementOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <div x-show="managementOpen" x-transition x-cloak
                 class="absolute right-0 mt-3 w-56 rounded-3xl border-2 border-rose-100 bg-white shadow-xl p-2 z-50">
              <?php foreach ($managementNav as [$href, $icon, $label]): ?>
                <?php $staticActive = $isActiveNav($href, $label); ?>
                <a href="<?= url($href) ?>"
                   class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all
                          <?= $staticActive ? 'bg-rose-50 text-rose-500' : 'text-chocolate-700 hover:bg-cream-50 hover:text-rose-500' ?>">
                  <span class="w-8 h-8 rounded-full bg-rose-50 flex items-center justify-center"><?= $icon ?></span>
                  <span><?= e($label) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Usuario / login (desktop) -->
        <div class="hidden lg:flex items-center gap-2 ml-2">
          <?php if (\App\Auth::check()): ?>
            <a href="<?= url('index.php') ?>" class="flex items-center gap-2 group">
              <span class="w-8 h-8 rounded-full bg-rose-200 text-rose-700 flex items-center justify-center text-sm font-bold">
                <?= e(mb_substr(\App\Auth::displayName(), 0, 1)) ?>
              </span>
              <span class="text-sm text-chocolate-700 font-semibold group-hover:text-rose-500 transition-colors">
                <?= e(\App\Auth::displayName()) ?>
              </span>
            </a>
            <a href="<?= url('usuarios.php') ?>"
               class="w-9 h-9 rounded-full bg-rose-50 hover:bg-rose-100 text-chocolate-700 hover:text-rose-500 transition-all flex items-center justify-center"
               title="Gestionar usuarios">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
            </a>
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
        <button type="button"
                @click="mobileOpen=!mobileOpen"
                :aria-expanded="mobileOpen.toString()"
                aria-label="Abrir menu"
                class="lg:hidden h-11 w-11 rounded-full hover:bg-rose-50 text-rose-500 transition-all flex items-center justify-center shrink-0">
          <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"/>
          </svg>
          <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
        </div>

      <!-- Menú móvil expandido -->
      <div x-show="mobileOpen" x-transition x-cloak class="lg:hidden mt-3">
        <div class="bg-white rounded-3xl shadow-xl border-2 border-rose-100 p-3 space-y-1">
          <?php foreach ($navItems as [$href, $icon, $label]): ?>
            <?php $staticActive = $isActiveNav($href, $label); ?>
            <a href="<?= url($href) ?>"
               class="px-4 py-3 rounded-2xl font-semibold flex items-center gap-3 transition-all
                      <?= $staticActive ? 'bg-rose-400 text-white shadow-md shadow-rose-300/40' : 'text-chocolate-700 hover:bg-rose-50 hover:text-rose-500' ?>">
              <span class="w-8 h-8 rounded-full bg-white/60 flex items-center justify-center"><?= $icon ?></span>
              <span><?= e($label) ?></span>
            </a>
          <?php endforeach; ?>
          <div class="border-t border-rose-100 my-1"></div>
          <?php if (\App\Auth::check()): ?>
            <div class="px-4 py-2 text-sm text-chocolate-700 flex items-center gap-2">
              <span class="w-8 h-8 rounded-full bg-rose-200 text-rose-700 flex items-center justify-center text-sm font-bold">
                <?= e(mb_substr(\App\Auth::displayName(), 0, 1)) ?>
              </span>
              <?= e(\App\Auth::displayName()) ?>
            </div>
            <a href="<?= url('usuarios.php') ?>" class="block px-4 py-3 rounded-2xl hover:bg-rose-50 font-semibold flex items-center gap-2">
              <span>⚙️</span> Gestionar usuarios
            </a>
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
