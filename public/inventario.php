<?php
/**
 * Página: Inventario de Insumos
 * CRUD completo: listar, crear, editar y eliminar ingredientes.
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Ingrediente;

$accion = $_GET['accion'] ?? 'listar';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ============ POST: Crear / Actualizar / Eliminar ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        if ($accion === 'crear') {
            $nuevo = Ingrediente::create($_POST);
            flash('success', '🎉 Ingrediente "' . $_POST['nombre'] . '" agregado.');
            redirect('inventario.php');
        }
        if ($accion === 'editar' && $id > 0) {
            Ingrediente::update($id, $_POST);
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
    <p class="text-sm text-chocolate-500 mb-6">Todos los campos son obligatorios.</p>

    <form method="post"
          action="<?= url('inventario.php?accion=' . $accion . ($id ? '&id=' . $id : '')) ?>"
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
          <label class="block text-sm font-bold text-chocolate-700 mb-1">Unidad de medida</label>
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

        <div>
          <label class="block text-sm font-bold text-chocolate-700 mb-1">Costo por unidad</label>
          <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-chocolate-500 font-bold">$</span>
            <input type="number" name="costo_base" step="0.0001" min="0" required
                   value="<?= e(old('costo_base', $ingredienteEditar['costo_base'] ?? '0')) ?>"
                   class="pl-8"
                   placeholder="0.00">
          </div>
          <p class="text-xs text-chocolate-500 mt-1">Costo de 1 kilo, 1 litro o 1 pieza.</p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">Notas <span class="text-chocolate-500 font-normal">(opcional)</span></label>
        <input type="text" name="notas" maxlength="255"
               value="<?= e(old('notas', $ingredienteEditar['notas'] ?? '')) ?>"
               placeholder="Marca, proveedor, descripción...">
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
            <th>Ingrediente</th>
            <th>Unidad</th>
            <th>Costo / unidad</th>
            <th>Notas</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ingredientes as $ing): ?>
            <tr>
              <td>
                <div class="flex items-center gap-3">
                  <span class="text-2xl">🧂</span>
                  <span class="font-semibold text-chocolate-900"><?= e($ing['nombre']) ?></span>
                </div>
              </td>
              <td>
                <span class="badge"><?= e($ing['unidad_medida']) ?></span>
              </td>
              <td>
                <span class="font-bold text-rose-500"><?= format_money((float)$ing['costo_base']) ?></span>
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
