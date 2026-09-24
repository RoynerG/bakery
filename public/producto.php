<?php
/**
 * Admin: crear / editar un producto del catalogo.
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Producto;
use App\Auth;

Auth::require();

$accion = $_GET['accion'] ?? 'crear';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!in_array($accion, ['crear', 'editar'], true)) {
    redirect('productos.php');
}

$productoEditar = null;
$variantes = [];
if ($accion === 'editar' && $id > 0) {
    $productoEditar = Producto::find($id);
    if (!$productoEditar) {
        flash('error', 'Producto no encontrado.');
        redirect('productos.php');
    }
    $variantes = Producto::variantes($id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        $up = handle_upload('imagen');
        if (!$up['ok']) {
            throw new RuntimeException($up['error'] ?? 'Error al subir la imagen.');
        }
        $imagenFilename = $up['filename'] ?? null;
        $hayNuevaImagen = $imagenFilename !== null;

        if ($accion === 'crear') {
            $nuevoId = Producto::create($_POST, $imagenFilename);
            flash('success', '🎉 Producto "' . $_POST['nombre'] . '" creado.');
            redirect('productos.php');
        }
        if ($accion === 'editar' && $id > 0) {
            Producto::update($id, $_POST, $imagenFilename, $hayNuevaImagen);
            flash('success', '✅ Producto actualizado.');
            redirect('producto.php?accion=editar&id=' . $id);
        }
    } catch (Throwable $e) {
        keep_old($_POST);
        flash('error', $e->getMessage());
        redirect('producto.php?accion=' . $accion . ($id ? '&id=' . $id : ''));
    }
}

$titulo = $accion === 'crear' ? 'Nuevo producto' : 'Editar producto';
?>
<?php require_once __DIR__ . '/../src/layout/header.php'; ?>

<section class="mb-8">
  <a href="<?= url('productos.php') ?>" class="text-chocolate-500 hover:text-rose-500 text-sm">← Volver al listado</a>
  <h1 class="section-title mt-2"><?= e($titulo) ?></h1>
</section>

<form method="post"
      action="<?= url('producto.php?accion=' . $accion . ($id ? '&id=' . $id : '')) ?>"
      enctype="multipart/form-data"
      class="space-y-6"
      x-data='variantesForm(<?= json_encode($variantes) ?>)'>

  <?= csrf_field() ?>

  <div class="card space-y-5">
    <h2 class="font-bold text-chocolate-900">Datos basicos</h2>

    <div>
      <label class="block text-sm font-bold text-chocolate-700 mb-1">Nombre del producto *</label>
      <input type="text" name="nombre" required maxlength="160"
             value="<?= e(old('nombre', $productoEditar['nombre'] ?? '')) ?>"
             placeholder="Ej. Kuchen de manzana, Torta Selva Negra...">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">Tipo *</label>
        <select name="tipo" required>
          <?php
          $tipoActual = old('tipo', $productoEditar['tipo'] ?? 'clasica');
          foreach (Producto::TIPOS as $key => $label):
            $sel = $tipoActual === $key ? 'selected' : '';
          ?>
            <option value="<?= e($key) ?>" <?= $sel ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-chocolate-500 mt-1">Determina en que pagina del libro aparece.</p>
      </div>
      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">Precio desde (CLP)</label>
        <input type="text" inputmode="numeric" name="precio_desde"
               value="<?= e(old('precio_desde', $productoEditar['precio_desde'] ?? '')) ?>"
               placeholder="23.900">
      </div>
      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">Orden</label>
        <input type="number" name="orden"
               value="<?= e(old('orden', $productoEditar['orden'] ?? '0')) ?>">
        <p class="text-xs text-chocolate-500 mt-1">Menor = aparece primero.</p>
      </div>
    </div>

    <div>
      <label class="block text-sm font-bold text-chocolate-700 mb-1">Descripcion</label>
      <textarea name="descripcion" rows="4"
                placeholder="Texto descriptivo del producto."><?= e(old('descripcion', $productoEditar['descripcion'] ?? '')) ?></textarea>
    </div>

    <div>
      <label class="block text-sm font-bold text-chocolate-700 mb-1">Imagen</label>
      <?php if (!empty($productoEditar['imagen'])): ?>
        <div class="mb-3 flex items-center gap-3">
          <img src="<?= upload_url($productoEditar['imagen']) ?>" alt="<?= e($productoEditar['nombre']) ?>"
               id="producto-imagen-preview"
               class="h-20 w-20 rounded-xl object-cover border-2 border-rose-100">
          <span class="text-xs text-chocolate-500">Imagen actual. Subi una nueva para reemplazarla.</span>
        </div>
      <?php else: ?>
        <img src="" alt="Vista previa de la imagen"
             id="producto-imagen-preview"
             class="hidden mb-3 h-20 w-20 rounded-xl object-cover border-2 border-rose-100">
      <?php endif; ?>
      <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp,image/gif"
             onchange="previewProductoImagen(event)"
             class="block w-full text-sm text-chocolate-700 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-rose-100 file:text-rose-700 file:font-semibold hover:file:bg-rose-200">
    </div>

    <div class="flex items-center gap-2">
      <input type="checkbox" id="visible" name="visible" value="1"
             <?= old('visible', $accion === 'crear' || !empty($productoEditar['visible']) ? '1' : '') === '1' ? 'checked' : '' ?>
             class="w-5 h-5 accent-rose-400">
      <label for="visible" class="text-sm font-bold text-chocolate-700">Visible en el catalogo publico</label>
    </div>
  </div>

  <div class="card space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="font-bold text-chocolate-900">Variantes / Tamanos / Precios</h2>
      <button type="button" @click="add()" class="btn btn-secondary !py-1 !px-3 !text-xs">
        ➕ Agregar variante
      </button>
    </div>
    <p class="text-sm text-chocolate-500">
      Ej: "10 personas", "Mediano 26cm", "Con pastelera + $1.200".
      Dejalas vacias para no guardar ninguna.
    </p>

    <div class="space-y-2">
      <template x-for="(v, i) in items" :key="i">
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
          <div class="sm:col-span-7">
            <input type="text" :name="`variantes[${i}][label]`" x-model="v.label"
                   placeholder="Etiqueta (ej. 10 personas)">
          </div>
          <div class="sm:col-span-3">
            <input type="text" inputmode="numeric" :name="`variantes[${i}][precio]`" x-model="v.precio"
                   placeholder="Precio CLP">
          </div>
          <div class="sm:col-span-2 text-right">
            <button type="button" @click="remove(i)"
                    class="btn btn-danger !py-1 !px-3 !text-xs w-full sm:w-auto">🗑️</button>
          </div>
        </div>
      </template>

      <p x-show="items.length === 0" class="text-sm text-chocolate-400 italic text-center py-4">
        Aun no hay variantes. Si tu producto tiene un solo precio, agregalo como una variante.
      </p>
    </div>
  </div>

  <div class="flex flex-wrap gap-3">
    <button type="submit" class="btn btn-primary">
      <span>💾</span> <?= $accion === 'crear' ? 'Crear producto' : 'Guardar cambios' ?>
    </button>
    <a href="<?= url('productos.php') ?>" class="btn btn-ghost">Cancelar</a>
  </div>
</form>

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('variantesForm', (initial) => ({
    items: Array.isArray(initial) && initial.length > 0
      ? initial.map(v => ({ label: v.label || '', precio: String(v.precio || '').replace(/\B(?=(\d{3})+(?!\d))/g, '.') }))
      : [{ label: '', precio: '' }],
    add() { this.items.push({ label: '', precio: '' }); },
    remove(i) {
      this.items.splice(i, 1);
      if (this.items.length === 0) this.add();
    }
  }));
});

function previewProductoImagen(event) {
  var file = event.target.files && event.target.files[0];
  var preview = document.getElementById('producto-imagen-preview');
  if (!file || !preview) return;
  preview.src = URL.createObjectURL(file);
  preview.classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>
