<?php
/**
 * Página principal: Recetario
 * Muestra todas las recetas en tarjetas bonitas.
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Receta;
use App\Models\Ingrediente;

$recetas    = Receta::all();
$totalIng   = count(Ingrediente::all());
$titulo     = 'Recetario';
?>
<?php require_once __DIR__ . '/../src/layout/header.php'; ?>

<!-- HERO -->
<section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-rose-100 via-cream-100 to-cream-200 border-2 border-rose-100 p-8 sm:p-12 mb-10 shadow-lg shadow-rose-200/40">
  <div class="absolute -top-10 -right-8 text-[10rem] opacity-25 select-none animate-wiggle">🧁</div>
  <div class="absolute -bottom-8 -left-6 text-[8rem] opacity-20 select-none animate-floaty">🍰</div>

  <div class="relative max-w-3xl">
    <p class="badge mb-3"><span>✨</span> <?= e(APP_TAGLINE) ?></p>
    <h1 class="font-sweet text-4xl sm:text-5xl text-rose-500 leading-tight">
      Bienvenido a <span class="text-chocolate-500"><?= e(APP_NAME) ?></span>
    </h1>
    <p class="mt-4 text-chocolate-700 text-lg max-w-xl">
      Organiza tus recetas de repostería, calcula el costo de cada platillo al instante
      y comparte tus creaciones más hermosas. ✨
    </p>

    <div class="mt-6 flex flex-wrap gap-3">
      <a href="<?= url('receta.php') ?>" class="btn btn-primary">
        <span>✨</span> Crear nueva receta
      </a>
      <a href="<?= url('inventario.php') ?>" class="btn btn-secondary">
        <span>📦</span> Ver inventario (<?= (int)$totalIng ?>)
      </a>
    </div>
  </div>
</section>

<!-- STATS -->
<section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
  <?php
  $stats = [
    ['🍰', count($recetas), 'Recetas guardadas', 'rose'],
    ['📦', $totalIng, 'Insumos en inventario', 'mint'],
    ['💰', array_sum(array_column($recetas, 'costo_total')), 'Costo total acumulado', 'chocolate'],
    ['📸', count(array_filter($recetas, fn($r) => !empty($r['imagen']))), 'Recetas con foto', 'cream'],
  ];
  foreach ($stats as [$icon, $val, $label, $color]):
    $isMoney = $label === 'Costo total acumulado';
  ?>
    <div class="card card-hover text-center">
      <div class="text-4xl mb-1 animate-floaty inline-block"><?= $icon ?></div>
      <div class="font-bold text-2xl text-<?= $color === 'rose' ? 'rose-500' : ($color === 'mint' ? 'mint-500' : ($color === 'chocolate' ? 'chocolate-700' : 'chocolate-500')) ?>">
        <?= $isMoney ? format_money((float)$val) : (int)$val ?>
      </div>
      <div class="text-xs uppercase tracking-wide text-chocolate-500 mt-1"><?= e($label) ?></div>
    </div>
  <?php endforeach; ?>
</section>

<!-- LISTADO DE RECETAS -->
<section>
  <div class="flex items-end justify-between mb-6">
    <h2 class="section-title">Mis recetas</h2>
    <span class="text-sm text-chocolate-500"><?= count($recetas) ?> en total</span>
  </div>

  <?php if (empty($recetas)): ?>
    <div class="card text-center py-16">
      <div class="text-7xl mb-4 animate-wiggle inline-block">🥧</div>
      <h3 class="font-sweet text-2xl text-rose-500 mb-2">¡Aún no hay recetas!</h3>
      <p class="text-chocolate-700 mb-6 max-w-md mx-auto">
        Comienza agregando ingredientes al inventario y luego crea tu primera receta dulce.
      </p>
      <div class="flex flex-wrap gap-3 justify-center">
        <a href="<?= url('inventario.php') ?>" class="btn btn-secondary">
          <span>📦</span> Agregar ingredientes
        </a>
        <a href="<?= url('receta.php') ?>" class="btn btn-primary">
          <span>✨</span> Crear primera receta
        </a>
      </div>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($recetas as $r): ?>
        <article class="card card-hover recipe-card group">
          <a href="<?= url('ver-receta.php?id=' . (int)$r['id']) ?>" class="block">
            <div class="recipe-img-wrap mb-4 shadow-md">
              <img src="<?= upload_url($r['imagen'] ?? null) ?>" alt="<?= e($r['nombre']) ?>" loading="lazy">
            </div>
            <h3 class="font-bold text-lg text-chocolate-900 group-hover:text-rose-500 transition-colors">
              <?= e($r['nombre']) ?>
            </h3>
            <?php if (!empty($r['descripcion'])): ?>
              <p class="text-sm text-chocolate-700 mt-1 line-clamp-2">
                <?= e($r['descripcion']) ?>
              </p>
            <?php endif; ?>
          </a>

          <div class="mt-4 pt-4 border-t border-rose-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="badge">🍽️ <?= (int)$r['porciones'] ?> porciones</span>
            </div>
            <div class="text-right">
              <div class="text-xs text-chocolate-500">Costo</div>
              <div class="font-bold text-rose-500"><?= format_money((float)$r['costo_total']) ?></div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- FAB -->
<a href="<?= url('receta.php') ?>" class="fab" title="Crear nueva receta">＋</a>

<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>
