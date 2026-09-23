<?php
/**
 * Página: Gestión de Categorías
 * CRUD completo estilo pastel — se usa desde notas y eventos.
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Categoria;
use App\Auth;

Auth::require();

$accion = $_GET['accion'] ?? 'listar';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$volver = $_GET['volver'] ?? 'notas.php';

// ============ POST: Crear / Actualizar / Eliminar ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        if ($accion === 'crear') {
            Categoria::create($_POST['emoji'] ?? '', $_POST['nombre'] ?? '');
            flash('success', '🏷️ Categoría creada.');
            redirect('categorias.php');
        }
        if ($accion === 'editar' && $id > 0) {
            Categoria::update($id, $_POST['emoji'] ?? '', $_POST['nombre'] ?? '');
            flash('success', '✅ Categoría actualizada.');
            redirect('categorias.php');
        }
        if ($accion === 'eliminar' && $id > 0) {
            $ok = Categoria::delete($id);
            flash($ok ? 'success' : 'error', $ok ? '🗑️ Categoría eliminada.' : '😢 No se pudo eliminar.');
            redirect('categorias.php');
        }
    } catch (Throwable $e) {
        keep_old($_POST);
        flash('error', $e->getMessage());
        redirect('categorias.php' . ($accion === 'editar' ? '?accion=editar&id=' . $id : '?accion=crear'));
    }
}

// ============ Cargar categoría para edición ============
$categoriaEditar = null;
if ($accion === 'editar' && $id > 0) {
    $categoriaEditar = Categoria::find($id);
    if (!$categoriaEditar) {
        flash('error', 'La categoría no existe.');
        redirect('categorias.php');
    }
}

$categorias = Categoria::all();
$titulo = 'Categorías';
?>
<?php require_once __DIR__ . '/../src/layout/header.php'; ?>

<!-- HEADER -->
<section class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
  <div>
    <h1 class="section-title">🏷️ Categorías</h1>
    <p class="text-chocolate-700 mt-2">
      Crea etiquetas con emoji para clasificar tus notas y eventos.
    </p>
  </div>
  <a href="<?= url('categorias.php?accion=crear') ?>" class="btn btn-primary">
    <span>➕</span> Nueva categoría
  </a>
</section>

<?php if ($accion === 'crear' || $accion === 'editar'): ?>
  <!-- ============ FORMULARIO ============ -->
  <section class="card max-w-xl mx-auto mb-10">
    <h2 class="font-sweet text-2xl text-rose-500 mb-1">
      <?= $accion === 'crear' ? 'Nueva categoría' : 'Editar categoría' ?>
    </h2>
    <p class="text-sm text-chocolate-500 mb-6">
      El emoji es lo primero que verás en cada nota. ✨
    </p>

    <form method="post"
          action="<?= url('categorias.php?accion=' . $accion . ($id ? '&id=' . $id : '')) ?>"
          class="space-y-5">
      <?= csrf_field() ?>

      <div class="flex gap-3">
        <div class="w-24">
          <label class="block text-sm font-bold text-chocolate-700 mb-1">Emoji</label>
          <input type="text" name="emoji" required maxlength="8"
                 value="<?= e(old('emoji', $categoriaEditar['emoji'] ?? '')) ?>"
                 placeholder="🍰"
                 class="text-center text-2xl" style="padding: .75rem .5rem;">
        </div>
        <div class="flex-1">
          <label class="block text-sm font-bold text-chocolate-700 mb-1">Nombre</label>
          <input type="text" name="nombre" required maxlength="60"
                 value="<?= e(old('nombre', $categoriaEditar['nombre'] ?? '')) ?>"
                 placeholder="Ej. Pedidos, Cumpleaños, Ideas…">
        </div>
      </div>

      <p class="text-xs text-chocolate-500">
        💡 Sugerencias: 🍰 Pedidos · 🎂 Cumpleaños · 💡 Ideas · 📞 Llamadas · 📦 Inventario · 💰 Ventas
      </p>

      <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="btn btn-primary">
          <span>💾</span> <?= $accion === 'crear' ? 'Guardar' : 'Actualizar' ?>
        </button>
        <a href="<?= url('categorias.php') ?>" class="btn btn-ghost">Cancelar</a>
      </div>
    </form>
  </section>

<?php else: ?>
  <!-- ============ LISTADO ============ -->
  <?php if (empty($categorias)): ?>
    <div class="card text-center py-16">
      <div class="text-7xl mb-4 animate-wiggle inline-block">🏷️</div>
      <h3 class="font-sweet text-2xl text-rose-500 mb-2">No tienes categorías aún</h3>
      <p class="text-chocolate-700 mb-6">
        Crea tu primera categoría para empezar a clasificar tus notas y eventos.
      </p>
      <a href="<?= url('categorias.php?accion=crear') ?>" class="btn btn-primary">
        <span>➕</span> Crear la primera
      </a>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
      <?php foreach ($categorias as $c): ?>
        <article class="categoria-card">
          <div class="flex items-center gap-4">
            <div class="categoria-emoji"><?= e($c['emoji']) ?></div>
            <div class="flex-1 min-w-0">
              <h3 class="font-bold text-lg text-chocolate-900 truncate">
                <?= e($c['nombre']) ?>
              </h3>
              <p class="text-xs text-chocolate-500">
                Creada <?= date('d/m/Y', strtotime($c['created_at'])) ?>
              </p>
            </div>
          </div>
          <div class="mt-4 flex gap-2">
            <a href="<?= url('categorias.php?accion=editar&id=' . (int)$c['id']) ?>"
               class="btn btn-secondary flex-1 justify-center !py-2 !text-xs">
              <span>✏️</span> Editar
            </a>
            <form method="post"
                  action="<?= url('categorias.php?accion=eliminar&id=' . (int)$c['id']) ?>"
                  x-data="confirmDelete('¿Eliminar la categoría «<?= e($c['nombre']) ?>»?\n\nLas notas y eventos que la usan quedarán sin categoría.')"
                  class="flex-1">
              <?= csrf_field() ?>
              <button type="submit"
                      @click.prevent="ask(() => $event.target.form.submit())"
                      class="btn btn-danger w-full justify-center !py-2 !text-xs">
                <span>🗑️</span> Eliminar
              </button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php clear_old(); ?>
<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>