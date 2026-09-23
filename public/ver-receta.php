<?php
/**
 * Página: Ver receta (detalle)
 * Muestra la receta completa con su imagen, ingredientes y costo.
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Receta;

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$receta = $id > 0 ? Receta::find($id) : null;

if (!$receta) {
    flash('error', 'La receta solicitada no existe.');
    redirect('index.php');
}

$titulo = $receta['nombre'];
?>
<?php require_once __DIR__ . '/../src/layout/header.php'; ?>

<!-- ============ HERO CON IMAGEN ============ -->
<section class="card overflow-hidden p-0 mb-8">
  <div class="grid grid-cols-1 md:grid-cols-5 gap-0">
    <!-- Imagen -->
    <div class="md:col-span-2 relative bg-cream-100">
      <img src="<?= upload_url($receta['imagen'] ?? null) ?>" alt="<?= e($receta['nombre']) ?>"
           class="w-full h-64 md:h-full object-cover">
      <?php if (empty($receta['imagen'])): ?>
        <div class="absolute inset-0 flex items-center justify-center text-7xl opacity-30">🧁</div>
      <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="md:col-span-3 p-8">
      <a href="<?= url('index.php') ?>" class="btn btn-ghost !py-1 !px-3 mb-4">← Volver al recetario</a>

      <div class="flex flex-wrap gap-2 mb-3">
        <span class="badge">🍽️ <?= (int)$receta['porciones'] ?> porciones</span>
        <span class="badge">💰 <?= format_money((float)$receta['costo_total']) ?> costo</span>
        <span class="badge">📅 <?= date('d/m/Y', strtotime($receta['created_at'])) ?></span>
      </div>

      <h1 class="font-sweet text-3xl sm:text-4xl text-rose-500 mb-3 leading-tight">
        <?= e($receta['nombre']) ?>
      </h1>

      <?php if (!empty($receta['descripcion'])): ?>
        <p class="text-chocolate-700 mb-4"><?= nl2br(e($receta['descripcion'])) ?></p>
      <?php endif; ?>

      <!-- Costo / Porción destacado -->
      <div class="cost-card mt-4">
        <span class="drip-top"></span>
        <div class="grid grid-cols-3 gap-4 text-center">
          <div>
            <div class="text-xs uppercase tracking-wider text-chocolate-500">Costo total</div>
            <div class="font-bold text-rose-500 text-xl"><?= format_money((float)$receta['costo_total']) ?></div>
          </div>
          <div>
            <div class="text-xs uppercase tracking-wider text-chocolate-500">Porciones</div>
            <div class="font-bold text-chocolate-700 text-xl"><?= (int)$receta['porciones'] ?></div>
          </div>
          <div>
            <div class="text-xs uppercase tracking-wider text-chocolate-500">Costo / porción</div>
            <div class="font-bold text-rose-500 text-xl">
              <?= format_money((float)$receta['costo_total'] / max(1, (int)$receta['porciones'])) ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Acciones -->
      <div class="mt-6 flex flex-wrap gap-3" x-data="confirmDelete('¿Eliminar esta receta?')">
        <a href="<?= url('receta.php?editar=' . (int)$receta['id']) ?>" class="btn btn-secondary">
          <span>✏️</span> Editar
        </a>
        <form method="post" action="<?= url('acciones.php') ?>" class="inline">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="eliminar_receta">
          <input type="hidden" name="id" value="<?= (int)$receta['id'] ?>">
          <button type="submit" @click.prevent="ask(() => $event.target.form.submit())" class="btn btn-danger">
            <span>🗑️</span> Eliminar
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- ============ INGREDIENTES ============ -->
<section class="card mb-8">
  <h2 class="font-bold text-xl text-chocolate-900 flex items-center gap-2 mb-4">
    <span class="text-2xl">🥣</span> Ingredientes
  </h2>

  <?php if (empty($receta['ingredientes'])): ?>
    <p class="text-chocolate-500">Sin ingredientes registrados.</p>
  <?php else: ?>
    <table class="sweet-table">
      <thead>
        <tr>
          <th>Ingrediente</th>
          <th>Cantidad</th>
          <th>Costo unitario</th>
          <th class="text-right">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($receta['ingredientes'] as $ri): ?>
          <tr>
            <td>
              <span class="font-semibold text-chocolate-900"><?= e($ri['nombre']) ?></span>
            </td>
            <td>
              <span class="badge"><?= format_unidad($ri['unidad_medida'], (float)$ri['cantidad']) ?></span>
            </td>
            <td class="text-chocolate-700">
              <?= format_money((float)$ri['costo_base']) ?>
            </td>
            <td class="text-right font-bold text-rose-500">
              <?= format_money((float)$ri['subtotal']) ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <tr class="font-bold">
          <td colspan="3" class="text-right">Total</td>
          <td class="text-right text-rose-500 text-lg"><?= format_money((float)$receta['costo_total']) ?></td>
        </tr>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<!-- ============ INSTRUCCIONES ============ -->
<section class="card">
  <h2 class="font-bold text-xl text-chocolate-900 flex items-center gap-2 mb-4">
    <span class="text-2xl">👩‍🍳</span> Preparación
  </h2>
  <div class="prose prose-rose max-w-none text-chocolate-800 leading-relaxed whitespace-pre-line">
    <?= e($receta['instrucciones']) ?>
  </div>
</section>

<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>
