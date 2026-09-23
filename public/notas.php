<?php
/**
 * Página: Bloc de Notas
 * CRUD completo con notas estilo Post-it.
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Nota;
use App\Auth;

Auth::require();

$accion = $_GET['accion'] ?? 'listar';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ============ POST: Crear / Actualizar / Eliminar ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        if ($accion === 'crear') {
            Nota::create($_POST);
            flash('success', '📝 Nota creada.');
            redirect('notas.php');
        }
        if ($accion === 'editar' && $id > 0) {
            Nota::update($id, $_POST);
            flash('success', '✅ Nota actualizada.');
            redirect('notas.php');
        }
        if ($accion === 'eliminar' && $id > 0) {
            $ok = Nota::delete($id);
            flash($ok ? 'success' : 'error', $ok ? '🗑️ Nota eliminada.' : '😢 No se pudo eliminar.');
            redirect('notas.php');
        }
    } catch (Throwable $e) {
        keep_old($_POST);
        flash('error', $e->getMessage());
        redirect('notas.php' . ($accion === 'editar' ? '?accion=editar&id=' . $id : '?accion=crear'));
    }
}

// ============ Cargar nota para edición ============
$notaEditar = null;
if ($accion === 'editar' && $id > 0) {
    $notaEditar = Nota::find($id);
    if (!$notaEditar) {
        flash('error', 'La nota no existe.');
        redirect('notas.php');
    }
}

$notas = Nota::all();
$titulo = 'Notas';
?>
<?php require_once __DIR__ . '/../src/layout/header.php'; ?>

<!-- HEADER -->
<section class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
  <div>
    <h1 class="section-title">Bloc de Notas</h1>
    <p class="text-chocolate-700 mt-2">Anota ideas, pendientes y secretos de la cocina. 📝</p>
  </div>
  <a href="<?= url('notas.php?accion=crear') ?>" class="btn btn-primary">
    <span>➕</span> Nueva nota
  </a>
</section>

<?php if ($accion === 'crear' || $accion === 'editar'): ?>
  <!-- ============ FORMULARIO ============ -->
  <section class="card max-w-3xl mx-auto mb-10">
    <h2 class="font-sweet text-2xl text-rose-500 mb-1">
      <?= $accion === 'crear' ? 'Nueva nota' : 'Editar nota' ?>
    </h2>
    <p class="text-sm text-chocolate-500 mb-6">Cuéntale a tu yo del futuro. ✨</p>

    <form method="post"
          action="<?= url('notas.php?accion=' . $accion . ($id ? '&id=' . $id : '')) ?>"
          class="space-y-5">
      <?= csrf_field() ?>

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">Título</label>
        <input type="text" name="titulo" required maxlength="180"
               value="<?= e(old('titulo', $notaEditar['titulo'] ?? '')) ?>"
               placeholder="Ej. Ideas para el cumpleaños de Valentina 🎂">
      </div>

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">Contenido</label>
        <textarea name="contenido" rows="8"
                  placeholder="Escribe lo que quieras... puede ser texto, una lista, recordatorios..."
                  class="font-display"><?= e(old('contenido', $notaEditar['contenido'] ?? '')) ?></textarea>
      </div>

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-2">Color</label>
        <div class="flex flex-wrap gap-3">
          <?php
          $colorActual = old('color', $notaEditar['color'] ?? 'rosa');
          $colores = [
            'rosa'      => ['#ffd6e7', '🌸 Rosa'],
            'crema'     => ['#ffe5b4', '🍯 Crema'],
            'menta'     => ['#d4f4e2', '🌿 Menta'],
            'chocolate' => ['#e8d4bd', '🍫 Chocolate'],
          ];
          foreach ($colores as $key => [$hex, $label]):
            $sel = $colorActual === $key ? 'selected' : '';
          ?>
            <label class="nota-color-pick cursor-pointer">
              <input type="radio" name="color" value="<?= e($key) ?>" <?= $sel ?> class="sr-only peer" required>
              <div class="nota-color-swatch peer-checked:ring-4 peer-checked:ring-rose-400"
                   style="background: <?= e($hex) ?>"></div>
              <span class="text-xs font-semibold mt-1"><?= e($label) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="btn btn-primary">
          <span>💾</span> <?= $accion === 'crear' ? 'Guardar nota' : 'Actualizar' ?>
        </button>
        <a href="<?= url('notas.php') ?>" class="btn btn-ghost">Cancelar</a>
      </div>
    </form>
  </section>

<?php else: ?>
  <!-- ============ LISTADO DE NOTAS ============ -->
  <?php if (empty($notas)): ?>
    <div class="card text-center py-16">
      <div class="text-7xl mb-4 animate-wiggle inline-block">📝</div>
      <h3 class="font-sweet text-2xl text-rose-500 mb-2">No tienes notas aún</h3>
      <p class="text-chocolate-700 mb-6">Crea tu primera nota para empezar a recordar esas ideas dulces.</p>
      <a href="<?= url('notas.php?accion=crear') ?>" class="btn btn-primary">
        <span>➕</span> Crear primera nota
      </a>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
      <?php foreach ($notas as $n): ?>
        <?php
        $bg = match ($n['color']) {
            'rosa'      => 'linear-gradient(135deg, #fff0f5 0%, #ffd6e7 100%)',
            'crema'     => 'linear-gradient(135deg, #fffaf3 0%, #ffe5b4 100%)',
            'menta'     => 'linear-gradient(135deg, #f0fff5 0%, #d4f4e2 100%)',
            'chocolate' => 'linear-gradient(135deg, #f7efe6 0%, #e8d4bd 100%)',
        };
        ?>
        <article class="nota-card group" style="background: <?= e($bg) ?>;">
          <div class="flex items-start justify-between gap-2 mb-3">
            <h3 class="font-bold text-lg text-chocolate-900 leading-tight flex-1">
              <?= e($n['titulo']) ?>
            </h3>
            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <a href="<?= url('notas.php?accion=editar&id=' . (int)$n['id']) ?>"
                 class="nota-icon-btn" title="Editar">✏️</a>
              <form method="post" action="<?= url('notas.php?accion=eliminar&id=' . (int)$n['id']) ?>"
                    x-data="confirmDelete('¿Eliminar esta nota?')" class="inline">
                <?= csrf_field() ?>
                <button type="submit" @click.prevent="ask(() => $event.target.form.submit())"
                        class="nota-icon-btn" title="Eliminar">🗑️</button>
              </form>
            </div>
          </div>
          <p class="text-sm text-chocolate-800 whitespace-pre-line leading-relaxed">
            <?= nl2br(e($n['contenido'])) ?>
          </p>
          <div class="mt-4 pt-3 border-t border-white/50 flex items-center justify-between text-xs text-chocolate-700">
            <span>📅 <?= date('d/m/Y H:i', strtotime($n['updated_at'])) ?></span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php clear_old(); ?>
<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>