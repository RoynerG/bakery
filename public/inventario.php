<?php
/**
 * Página: Inventario de Insumos
 * CRUD completo: listar, crear, editar y eliminar ingredientes (con imagen opcional).
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Ingrediente;
use App\Auth;

// Toda la página de inventario requiere autenticación
Auth::require();

$accion = $_GET['accion'] ?? 'listar';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ============ POST: Crear / Actualizar / Eliminar ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        if ($accion === 'crear') {
            $up = handle_upload('imagen');
            if (!$up['ok']) {
                throw new RuntimeException($up['error'] ?? 'Error al subir la imagen.');
            }
            $nuevo = Ingrediente::create($_POST, $up['filename'] ?? null);
            flash('success', '🎉 Ingrediente "' . $_POST['nombre'] . '" agregado.');
            redirect('inventario.php');
        }
        if ($accion === 'editar' && $id > 0) {
            $up = handle_upload('imagen');
            if (!$up['ok']) {
                throw new RuntimeException($up['error'] ?? 'Error al subir la imagen.');
            }
            $hayNueva = ($up['filename'] ?? null) !== null;
            Ingrediente::update($id, $_POST, $up['filename'] ?? null, $hayNueva);
            flash('success', '✅ Ingrediente actualizado.');
            redirect('inventario.php');
        }
        if ($accion === 'eliminar' && $id > 0) {
            $ok = Ingrediente::delete($id);
            flash($ok ? 'success' : 'error', $ok
                ? '🗑️ Ingrediente eliminado.'
                : '😢 No se puede eliminar: el ingrediente está en uso por una receta.');
            redirect('inventario.php');
        }
    } catch (Throwable $e) {
        keep_old($_POST);
        flash('error', $e->getMessage());
        redirect('inventario.php' . ($accion === 'editar' ? '?accion=editar&id=' . $id : '?accion=crear'));
    }
}

// ============ Cargar datos para edición ============
$ingredienteEditar = null;
if ($accion === 'editar' && $id > 0) {
    $ingredienteEditar = Ingrediente::find($id);
    if (!$ingredienteEditar) {
        flash('error', 'El ingrediente no existe.');
        redirect('inventario.php');
    }
}

$ingredientes = Ingrediente::all();
$titulo = 'Inventario';
?>
<?php require_once __DIR__ . '/../src/layout/header.php'; ?>

<!-- HEADER -->
<section class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
  <div>
    <h1 class="section-title">Inventario de Insumos</h1>
    <p class="text-chocolate-700 mt-2">Administra tus ingredientes y sus costos por unidad.</p>
  </div>
  <a href="<?= url('inventario.php?accion=crear') ?>" class="btn btn-primary">
    <span>➕</span> Nuevo ingrediente
  </a>
</section>

<?php if ($accion === 'crear' || $accion === 'editar'): ?>
  <!-- ============ FORMULARIO ============ -->
  <section class="card max-w-2xl mx-auto mb-10">
    <h2 class="font-sweet text-2xl text-rose-500 mb-1">
      <?= $accion === 'crear' ? 'Nuevo ingrediente' : 'Editar ingrediente' ?>
    </h2>
    <p class="text-sm text-chocolate-500 mb-6">
      <?= $accion === 'crear' ? 'Todos los campos son obligatorios.' : 'Edita los datos. La imagen es opcional.' ?>
    </p>

    <form method="post"
          action="<?= url('inventario.php?accion=' . $accion . ($id ? '&id=' . $id : '')) ?>"
          enctype="multipart/form-data"
          class="space-y-5">
      <?= csrf_field() ?>

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">Nombre del ingrediente</label>
        <input type="text" name="nombre" required maxlength="120"
               value="<?= e(old('nombre', $ingredienteEditar['nombre'] ?? '')) ?>"
               placeholder="Ej. Harina de trigo, Mantequilla, Huevo...">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-chocolate-700 mb-1">Unidad de medida (recetas)</label>
          <select name="unidad_medida" required>
            <?php
            $unidadActual = old('unidad_medida', $ingredienteEditar['unidad_medida'] ?? 'pieza');
            $unidades = [
              'kilo'      => 'Kilo (kg)',
              'litro'     => 'Litro (L)',
              'pieza'     => 'Pieza (unidad)',
              'gramo'     => 'Gramo (g)',
              'mililitro' => 'Mililitro (ml)',
            ];
            foreach ($unidades as $key => $label):
              $sel = $unidadActual === $key ? 'selected' : '';
            ?>
              <option value="<?= e($key) ?>" <?= $sel ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div></div>
      </div>

      <fieldset class="border-2 border-dashed border-rose-200 rounded-2xl p-4 bg-rose-50/40">
        <legend class="px-2 text-sm font-bold text-chocolate-700">🧾 Datos de compra</legend>
        <p class="text-xs text-chocolate-500 mb-3">
          Cómo lo compraste en el proveedor. La app calcula el costo por kilo/litro/pieza.
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3" x-data='calcCostoBase({
          cantidad: <?= (float)old("cantidad_compra", $ingredienteEditar["cantidad_compra"] ?? 1) ?>,
          unidad:   "<?= e(old("unidad_compra", $ingredienteEditar["unidad_compra"] ?? "gramo")) ?>",
          precio:   <?= (float)old("precio_compra", $ingredienteEditar["precio_compra"] ?? 0) ?>
        })'>
          <div>
            <label class="block text-xs font-bold text-chocolate-700 mb-1">Cantidad comprada</label>
            <input type="number" name="cantidad_compra" step="0.0001" min="0.0001" required
                   x-model.number="cantidad"
                   value="<?= e(old('cantidad_compra', $ingredienteEditar['cantidad_compra'] ?? '1')) ?>"
                   placeholder="1000">
          </div>
          <div>
            <label class="block text-xs font-bold text-chocolate-700 mb-1">Unidad de compra</label>
            <select name="unidad_compra" required x-model="unidad">
              <?php
              $unidadCompraActual = old('unidad_compra', $ingredienteEditar['unidad_compra'] ?? 'gramo');
              foreach ($unidades as $key => $label):
                $sel = $unidadCompraActual === $key ? 'selected' : '';
              ?>
                <option value="<?= e($key) ?>" <?= $sel ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-chocolate-700 mb-1">Precio de compra (CLP)</label>
            <div class="flex items-stretch gap-2">
              <span class="inline-flex items-center justify-center px-3 text-chocolate-700 font-bold bg-rose-50 border-2 border-rose-100 rounded-2xl">$</span>
              <input type="text" inputmode="numeric"
                     name="precio_compra"
                     :value="precioFmt"
                     @input="onPrecioInput($event)"
                     placeholder="1.200"
                     required
                     class="flex-1">
            </div>
            <p class="text-xs text-chocolate-500 mt-1">Pesos chilenos. Use punto para miles: 1.200 = mil doscientos.</p>
          </div>
          <div class="sm:col-span-3 mt-2 p-3 bg-white rounded-xl border-2 border-rose-100">
            <p class="text-xs text-chocolate-500">Costo calculado por unidad base:</p>
            <p class="font-bold text-rose-500 text-lg" x-text="money(costoBase) + ' / ' + unidadBaseLabel()"></p>
            <p class="text-xs text-chocolate-500 mt-1" x-show="costoBasePorUnidadCompra() > 0">
              Equivale a <span class="font-semibold" x-text="money(costoBasePorUnidadCompra())"></span>
              por cada <span x-text="unidad"></span>.
            </p>
          </div>
        </div>
      </fieldset>

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">Notas <span class="text-chocolate-500 font-normal">(opcional)</span></label>
        <input type="text" name="notas" maxlength="255"
               value="<?= e(old('notas', $ingredienteEditar['notas'] ?? '')) ?>"
               placeholder="Marca, proveedor, descripción...">
      </div>

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">
          Imagen <span class="text-chocolate-500 font-normal">(opcional, JPG/PNG/WebP/GIF, máx 5 MB)</span>
        </label>

        <?php if (!empty($ingredienteEditar['imagen'])): ?>
          <div class="mb-3 flex items-center gap-3">
            <img src="<?= upload_url($ingredienteEditar['imagen']) ?>"
                 alt="<?= e($ingredienteEditar['nombre']) ?>"
                 class="h-16 w-16 rounded-xl object-cover border-2 border-rose-100">
            <span class="text-xs text-chocolate-500">Imagen actual. Sube una nueva para reemplazarla.</span>
          </div>
        <?php endif; ?>

        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp,image/gif"
               class="block w-full text-sm text-chocolate-700 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-rose-100 file:text-rose-700 file:font-semibold hover:file:bg-rose-200">
      </div>

      <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="btn btn-primary">
          <span>💾</span> <?= $accion === 'crear' ? 'Guardar ingrediente' : 'Actualizar' ?>
        </button>
        <a href="<?= url('inventario.php') ?>" class="btn btn-ghost">Cancelar</a>
      </div>
    </form>
  </section>

<?php else: ?>
  <!-- ============ LISTADO ============ -->
  <?php if (empty($ingredientes)): ?>
    <div class="card text-center py-16">
      <div class="text-7xl mb-4 animate-wiggle inline-block">📦</div>
      <h3 class="font-sweet text-2xl text-rose-500 mb-2">Tu inventario está vacío</h3>
      <p class="text-chocolate-700 mb-6">Agrega tu primer ingrediente para empezar a crear recetas.</p>
      <a href="<?= url('inventario.php?accion=crear') ?>" class="btn btn-primary">
        <span>➕</span> Agregar primer ingrediente
      </a>
    </div>
  <?php else: ?>
    <div class="card overflow-x-auto">
      <table class="sweet-table">
        <thead>
          <tr>
            <th class="w-20">Imagen</th>
            <th>Ingrediente</th>
            <th>Unidad</th>
            <th>Compra</th>
            <th>Costo / unidad base</th>
            <th>Notas</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ingredientes as $ing): ?>
            <?php
              $cantCompra = (float)($ing['cantidad_compra'] ?? 1);
              $uniCompra  = $ing['unidad_compra'] ?? $ing['unidad_medida'];
              $preCompra  = (float)($ing['precio_compra'] ?? 0);
              $uniBaseLabel = ['kilo' => 'kg', 'litro' => 'L', 'pieza' => 'pieza'][$ing['unidad_medida']] ?? $ing['unidad_medida'];
            ?>
            <tr>
              <td>
                <?php if (!empty($ing['imagen'])): ?>
                  <img src="<?= upload_url($ing['imagen']) ?>" alt="<?= e($ing['nombre']) ?>"
                       class="h-12 w-12 rounded-xl object-cover border-2 border-rose-100">
                <?php else: ?>
                  <div class="h-12 w-12 rounded-xl bg-rose-50 flex items-center justify-center text-2xl border-2 border-rose-100">🧂</div>
                <?php endif; ?>
              </td>
              <td>
                <span class="font-semibold text-chocolate-900"><?= e($ing['nombre']) ?></span>
              </td>
              <td>
                <span class="badge"><?= e($ing['unidad_medida']) ?></span>
              </td>
              <td class="text-sm">
                <span class="text-chocolate-700">
                  <?= e(rtrim(rtrim(number_format($cantCompra, 3, '.', ''), '0'), '.')) ?>
                  <?= e($uniCompra) ?>
                </span>
                <br>
                <span class="text-chocolate-500">por <?= format_money($preCompra) ?></span>
              </td>
              <td>
                <span class="font-bold text-rose-500"><?= format_money((float)$ing['costo_base']) ?></span>
                <span class="text-xs text-chocolate-500">/ <?= e($uniBaseLabel) ?></span>
              </td>
              <td class="text-chocolate-700 text-sm">
                <?= e($ing['notas'] ?? '—') ?>
              </td>
              <td class="text-right">
                <div class="inline-flex gap-2" x-data="confirmDelete('¿Eliminar este ingrediente?')">
                  <a href="<?= url('inventario.php?accion=editar&id=' . (int)$ing['id']) ?>"
                     class="btn btn-secondary !py-1 !px-3 !text-xs">✏️ Editar</a>
                  <form method="post" action="<?= url('inventario.php?accion=eliminar&id=' . (int)$ing['id']) ?>" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit" @click.prevent="ask(() => $event.target.form.submit())"
                            class="btn btn-danger !py-1 !px-3 !text-xs">🗑️</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php clear_old(); ?>
<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>