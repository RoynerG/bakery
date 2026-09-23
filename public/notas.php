<?php
/**
 * Página: Bloc de Notas
 * CRUD de notas + gestión de categorías (con emoji + nombre).
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Nota;
use App\Models\Categoria;
use App\Auth;

Auth::require();

$accion = $_GET['accion'] ?? 'listar';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ============ POST: Crear / Actualizar / Eliminar NOTAS ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['tipo'] ?? '') === 'nota') {
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

// ============ POST: Crear / Actualizar / Eliminar CATEGORÍAS ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['tipo'] ?? '') === 'categoria') {
    csrf_verify();
    try {
        $catAccion = $_POST['cat_accion'] ?? '';
        if ($catAccion === 'crear') {
            Categoria::create($_POST['emoji'] ?? '', $_POST['nombre'] ?? '');
            flash('success', '🏷️ Categoría creada.');
        }
        if ($catAccion === 'editar') {
            $cid = (int)($_POST['id'] ?? 0);
            if ($cid > 0) {
                Categoria::update($cid, $_POST['emoji'] ?? '', $_POST['nombre'] ?? '');
                flash('success', '✅ Categoría actualizada.');
            }
        }
        if ($catAccion === 'eliminar') {
            $cid = (int)($_POST['id'] ?? 0);
            if ($cid > 0) {
                Categoria::delete($cid);
                flash('success', '🗑️ Categoría eliminada.');
            }
        }
        redirect('notas.php?modal=categorias');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect('notas.php?modal=categorias');
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

$notas     = Nota::all();
$categorias = Categoria::all();
$titulo    = 'Notas';
?>
<?php require_once __DIR__ . '/../src/layout/header.php'; ?>

<!-- HEADER -->
<section class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
  <div>
    <h1 class="section-title">Bloc de Notas</h1>
    <p class="text-chocolate-700 mt-2">Anota ideas, pendientes y secretos de la cocina. 📝</p>
  </div>
  <div class="flex gap-2">
    <button type="button"
            onclick="var m=document.getElementById('categorias-modal'); if(m){m.classList.add('open');} return false;"
            class="btn btn-secondary">
      <span>🏷️</span> Categorías
    </button>
    <a href="<?= url('notas.php?accion=crear') ?>" class="btn btn-primary">
      <span>➕</span> Nueva nota
    </a>
  </div>
</section>

<?php if ($accion === 'crear' || $accion === 'editar'): ?>
  <!-- ============ FORMULARIO DE NOTA ============ -->
  <section class="card max-w-3xl mx-auto mb-10">
    <h2 class="font-sweet text-2xl text-rose-500 mb-1">
      <?= $accion === 'crear' ? 'Nueva nota' : 'Editar nota' ?>
    </h2>
    <p class="text-sm text-chocolate-500 mb-6">Cuéntale a tu yo del futuro. ✨</p>

    <form method="post"
          action="<?= url('notas.php?accion=' . $accion . ($id ? '&id=' . $id : '')) ?>"
          class="space-y-5">
      <?= csrf_field() ?>
      <input type="hidden" name="tipo" value="nota">

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
        <label class="block text-sm font-bold text-chocolate-700 mb-2">Categoría</label>
        <div class="flex flex-wrap gap-2">
          <label class="cat-pick cursor-pointer">
            <input type="radio" name="categoria_id" value="" class="sr-only peer"
                   <?= old('categoria_id', $notaEditar['categoria_id'] ?? '') === null || old('categoria_id', $notaEditar['categoria_id'] ?? '') === '' ? 'checked' : '' ?>>
            <span class="cat-pill peer-checked:ring-2 peer-checked:ring-rose-400">Sin categoría</span>
          </label>
          <?php foreach ($categorias as $c): ?>
            <?php $selCat = (int)old('categoria_id', $notaEditar['categoria_id'] ?? 0) === (int)$c['id']; ?>
            <label class="cat-pick cursor-pointer">
              <input type="radio" name="categoria_id" value="<?= (int)$c['id'] ?>" class="sr-only peer" <?= $selCat ? 'checked' : '' ?>>
              <span class="cat-pill peer-checked:ring-2 peer-checked:ring-rose-400">
                <span class="text-base"><?= e($c['emoji']) ?></span> <?= e($c['nombre']) ?>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
        <p class="text-xs text-chocolate-500 mt-2">
          ¿No encuentras una? <button type="button"
                                       onclick="var m=document.getElementById('categorias-modal'); if(m){m.classList.add('open');} return false;"
                                       class="text-rose-500 font-bold underline">Gestionar categorías</button>
        </p>
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
        <article class="nota-card group">
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
                <input type="hidden" name="tipo" value="nota">
                <button type="submit" @click.prevent="ask(() => $event.target.form.submit())"
                        class="nota-icon-btn" title="Eliminar">🗑️</button>
              </form>
            </div>
          </div>
          <?php if (!empty($n['cat_nombre'])): ?>
            <span class="nota-cat-tag">
              <span><?= e($n['cat_emoji']) ?></span> <?= e($n['cat_nombre']) ?>
            </span>
          <?php endif; ?>
          <p class="text-sm text-chocolate-800 whitespace-pre-line leading-relaxed mt-3">
            <?= nl2br(e($n['contenido'])) ?>
          </p>
          <div class="mt-auto pt-3 border-t border-white/50 flex items-center justify-between text-xs text-chocolate-700">
            <span>📅 <?= date('d/m/Y H:i', strtotime($n['updated_at'])) ?></span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<!-- ============ MODAL DE CATEGORÍAS ============ -->
<div id="categorias-modal" class="modal-backdrop">
  <div x-data="categoriasModal(<?= htmlspecialchars(json_encode(array_map(fn($c) => [
    'id' => (int)$c['id'], 'emoji' => $c['emoji'], 'nombre' => $c['nombre']
], $categorias)), ENT_QUOTES, 'UTF-8') ?>)"
       @click.outside="var m=document.getElementById('categorias-modal'); if(m){m.classList.remove('open');}"
       class="card max-w-lg w-full p-6 animate-pop max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-sweet text-2xl text-rose-500">🏷️ Categorías</h3>
      <button type="button"
              onclick="var m=document.getElementById('categorias-modal'); if(m){m.classList.remove('open');} return false;"
              class="w-9 h-9 rounded-full bg-rose-50 hover:bg-rose-100 flex items-center justify-center">✕</button>
    </div>
    <p class="text-sm text-chocolate-500 mb-4">
      Crea etiquetas con emoji para clasificar tus notas.
    </p>

    <!-- Crear nueva -->
    <form method="post" class="flex gap-2 mb-5 p-3 bg-rose-50/60 rounded-2xl">
      <?= csrf_field() ?>
      <input type="hidden" name="tipo" value="categoria">
      <input type="hidden" name="cat_accion" value="crear">
      <input type="text" name="emoji" maxlength="8" required
             placeholder="🍰" class="w-16 text-center text-xl">
      <input type="text" name="nombre" maxlength="60" required
             placeholder="Nombre de la categoría"
             class="flex-1 px-3 py-2 rounded-xl border-2 border-rose-100 focus:border-rose-400 focus:outline-none">
      <button type="submit" class="btn btn-primary !py-2 !px-4">＋</button>
    </form>

    <!-- Lista -->
    <div class="space-y-2">
      <template x-for="cat in lista" :key="cat.id">
        <div class="flex items-center gap-2 p-3 bg-white rounded-2xl border-2 border-rose-100">
          <span class="text-2xl w-10 text-center" x-text="cat.emoji"></span>
          <input type="text" x-model="cat.emoji" maxlength="8"
                 class="w-12 text-center px-1 py-1 rounded-lg border border-rose-100">
          <input type="text" x-model="cat.nombre" maxlength="60"
                 class="flex-1 px-3 py-1 rounded-lg border border-rose-100">
          <button type="button" @click="guardar(cat)"
                  class="nota-icon-btn" title="Guardar">💾</button>
          <button type="button" @click="eliminar(cat)"
                  class="nota-icon-btn" title="Eliminar">🗑️</button>
        </div>
      </template>
      <template x-if="lista.length === 0">
        <p class="text-center text-chocolate-500 py-4">No tienes categorías. Crea la primera arriba.</p>
      </template>
    </div>
  </div>
</div>

<script>
// Funcion inline DEFINITIVA: abre/cierra el modal de categorias
// con classList.add('open'). No depende de Alpine ni de app.js.
// Si por alguna razon app.js falla, ESTO sigue funcionando.
(function () {
  function abrir() {
    var m = document.getElementById('categorias-modal');
    if (m) m.classList.add('open');
  }
  function cerrar() {
    var m = document.getElementById('categorias-modal');
    if (m) m.classList.remove('open');
  }
  window.abrirCategorias = abrir;
  window.cerrarCategorias = cerrar;
  // ESC para cerrar
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') cerrar();
  });
  // Si llega la pagina con ?modal=categorias, abrir
  if (new URLSearchParams(location.search).get('modal') === 'categorias') {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', abrir);
    } else {
      abrir();
    }
  }
})();
</script>

<script>
document.addEventListener('alpine:init', () => {
  // Estado UI global (compartido entre notas.php y agenda.php)
  if (!Alpine.store('ui')) {
    Alpine.store('ui', {
      categoriasOpen: new URLSearchParams(location.search).get('modal') === 'categorias',
    });
  }

  Alpine.data('categoriasModal', (inicial = []) => ({
    lista: inicial,

    async guardar(cat) {
      const fd = new FormData();
      const csrf = document.querySelector('input[name=_csrf]');
      if (csrf) fd.append('_csrf', csrf.value);
      fd.append('tipo',       'categoria');
      fd.append('cat_accion', 'editar');
      fd.append('id',         cat.id);
      fd.append('emoji',      cat.emoji);
      fd.append('nombre',     cat.nombre);
      const r = await fetch('notas.php', { method: 'POST', body: fd });
      if (r.ok) location.reload();
    },

    async eliminar(cat) {
      if (!confirm('¿Eliminar la categoría "' + cat.nombre + '"?\n\nLas notas que la usan quedarán sin categoría.')) return;
      const fd = new FormData();
      const csrf = document.querySelector('input[name=_csrf]');
      if (csrf) fd.append('_csrf', csrf.value);
      fd.append('tipo',       'categoria');
      fd.append('cat_accion', 'eliminar');
      fd.append('id',         cat.id);
      const r = await fetch('notas.php', { method: 'POST', body: fd });
      if (r.ok) location.reload();
    },
  }));
});
</script>

<?php clear_old(); ?>
<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>