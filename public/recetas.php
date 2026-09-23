<?php
/**
 * Página: Recetas (listado limpio)
 * Solo el grid de recetas + botón para crear una nueva.
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Receta;
use App\Models\Ingrediente;

$recetas  = Receta::all();
$totalIng = count(Ingrediente::all());
$titulo   = 'Recetas';
?>
<?php require_once __DIR__ . '/../src/layout/header.php'; ?>

<!-- HEADER -->
<section class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
  <div>
    <h1 class="section-title">Mis recetas</h1>
    <p class="text-chocolate-700 mt-2">
      <?= count($recetas) ?> en total ·
      <a href="<?= url('inventario.php') ?>" class="text-rose-500 hover:underline font-semibold">
        <?= $totalIng ?> insumos en inventario
      </a>
    </p>
  </div>
  <a href="<?= url('receta.php') ?>" class="btn btn-primary">
    <span>✨</span> Nueva receta
  </a>
</section>

<?php if (empty($recetas)): ?>
  <div class="card text-center py-16">
    <div class="text-7xl mb-4 animate-wiggle inline-block">🥧</div>
    <h3 class="font-sweet text-2xl text-rose-500 mb-2">¡Aún no hay recetas!</h3>
    <p class="text-chocolate-700 mb-6 max-w-md mx-auto">
      Comienza agregando ingredientes al inventario y luego crea tu primera receta dulce.
    </p>
    <div class="flex flex-wrap gap-3 justify-center">
      <a href="<?= url('inventario.php?accion=crear') ?>" class="btn btn-secondary">
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

<!-- FAB para crear receta rápidamente -->
<a href="<?= url('receta.php') ?>" class="fab" title="Crear nueva receta">＋</a>

<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>