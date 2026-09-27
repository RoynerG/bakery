<?php
/**
 * Pagina: Armador de tortas.
 *
 * Costea una torta completa usando una receta base mas ingredientes extra.
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\TortaArmada;
use App\Models\Receta;
use App\Models\Ingrediente;
use App\Auth;

Auth::require();

$accion = $_GET['accion'] ?? 'listar';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!in_array($accion, ['listar', 'crear', 'editar', 'eliminar'], true)) {
    $accion = 'listar';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        if ($accion === 'eliminar' && $id > 0) {
            $ok = TortaArmada::delete($id);
            flash($ok ? 'success' : 'error', $ok ? 'Torta armada eliminada.' : 'No se pudo eliminar.');
            redirect('tortas.php');
        }

        $extras = [];
        if (!empty($_POST['extras_json'])) {
            $decoded = json_decode((string)$_POST['extras_json'], true);
            if (is_array($decoded)) {
                $extras = array_values(array_filter($decoded, fn($i) =>
                    !empty($i['ingrediente_id']) && (float)($i['cantidad'] ?? 0) > 0
                ));
            }
        }

        if ($accion === 'crear') {
            $newId = TortaArmada::create($_POST, $extras);
            flash('success', 'Torta armada guardada con su costo final.');
            redirect('tortas.php?accion=editar&id=' . $newId);
        }

        if ($accion === 'editar' && $id > 0) {
            TortaArmada::update($id, $_POST, $extras);
            flash('success', 'Torta armada actualizada.');
            redirect('tortas.php?accion=editar&id=' . $id);
        }
    } catch (Throwable $e) {
        keep_old($_POST);
        flash('error', $e->getMessage());
        redirect('tortas.php?accion=' . ($accion === 'editar' ? 'editar&id=' . $id : 'crear'));
    }
}

$recetas = Receta::all();
$ingredientes = Ingrediente::all();
$tortas = TortaArmada::all();

$tortaEditar = null;
$extrasEditar = [];
if ($accion === 'editar' && $id > 0) {
    $tortaEditar = TortaArmada::find($id);
    if (!$tortaEditar) {
        flash('error', 'La torta armada no existe.');
        redirect('tortas.php');
    }
    foreach ($tortaEditar['extras'] as $extra) {
        $extrasEditar[] = [
            'ingrediente_id' => (int)$extra['ingrediente_id'],
            'cantidad' => (float)$extra['cantidad'],
            'unidad' => $extra['unidad_medida'],
            'costo_unitario' => (float)$extra['costo_unitario'],
            'subtotal' => (float)$extra['subtotal'],
        ];
    }
}

$mostrarForm = in_array($accion, ['crear', 'editar'], true);
$initial = [
    'nombre' => old('nombre', $tortaEditar['nombre'] ?? ''),
    'descripcion' => old('descripcion', $tortaEditar['descripcion'] ?? ''),
    'receta_base_id' => (int)old('receta_base_id', $tortaEditar['receta_base_id'] ?? 0),
    'porciones_objetivo' => (int)old('porciones_objetivo', $tortaEditar['porciones_objetivo'] ?? 10),
    'otros_costos' => (float)old('otros_costos', $tortaEditar['otros_costos'] ?? 0),
    'iva_porcentaje' => (float)old('iva_porcentaje', $tortaEditar['iva_porcentaje'] ?? 0),
    'margen_porcentaje' => (float)old('margen_porcentaje', $tortaEditar['margen_porcentaje'] ?? 60),
    'extras' => $extrasEditar,
    'recetas' => $recetas,
    'ingredientes' => $ingredientes,
];

$titulo = 'Armador de tortas';
?>
<?php require_once __DIR__ . '/../src/layout/header.php'; ?>

<section class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-8">
  <div>
    <h1 class="section-title">Armador de tortas</h1>
    <p class="text-chocolate-700 mt-2 max-w-3xl">
      Combina una receta base, como un bizcocho, con rellenos y extras para obtener el costo real de una torta completa.
    </p>
  </div>
  <div class="flex flex-wrap gap-2">
    <a href="<?= url('recetas.php') ?>" class="btn btn-secondary">
      <span>🍰</span> Ver recetas base
    </a>
    <a href="<?= url('tortas.php?accion=crear') ?>" class="btn btn-primary">
      <span>＋</span> Armar torta
    </a>
  </div>
</section>

<?php if ($mostrarForm): ?>
  <?php if (empty($recetas) || empty($ingredientes)): ?>
    <div class="card text-center py-12 mb-8">
      <div class="text-6xl mb-3">🥣</div>
      <h2 class="font-sweet text-2xl text-rose-500 mb-2">Falta preparar la base</h2>
      <p class="text-chocolate-700 max-w-xl mx-auto mb-5">
        Para armar una torta necesitas al menos una receta base y algunos ingredientes en inventario.
      </p>
      <div class="flex flex-wrap justify-center gap-3">
        <a href="<?= url('receta.php') ?>" class="btn btn-primary">Crear receta base</a>
        <a href="<?= url('inventario.php?accion=crear') ?>" class="btn btn-secondary">Agregar ingrediente</a>
      </div>
    </div>
  <?php else: ?>
    <form method="post"
          action="<?= url('tortas.php?accion=' . $accion . ($id ? '&id=' . $id : '')) ?>"
          x-data='cakeBuilder(<?= json_encode($initial, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)'
          x-init="init()"
          @submit="prepareSubmit()"
          class="space-y-6 mb-10">
      <?= csrf_field() ?>

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
          <section class="card space-y-5">
            <div>
              <h2 class="font-bold text-chocolate-900 text-lg">Datos de la torta</h2>
              <p class="text-sm text-chocolate-500 mt-1">Guarda este armado para consultar el costo historico despues.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-bold text-chocolate-700 mb-1">Nombre de la torta *</label>
                <input type="text" name="nombre" x-model="nombre" required maxlength="180"
                       placeholder="Ej. Manjar crema durazno">
              </div>
              <div>
                <label class="block text-sm font-bold text-chocolate-700 mb-1">Porciones objetivo *</label>
                <input type="number" name="porciones_objetivo" x-model.number="porcionesObjetivo" min="1" required>
              </div>
            </div>

            <div>
              <label class="block text-sm font-bold text-chocolate-700 mb-1">Descripcion / notas</label>
              <textarea name="descripcion" rows="2" x-model="descripcion"
                        placeholder="Ej. Bizcocho blanco con manjar, crema y durazno."></textarea>
            </div>
          </section>

          <section class="card space-y-5">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
              <div>
                <h2 class="font-bold text-chocolate-900 text-lg">1. Receta base</h2>
                <p class="text-sm text-chocolate-500 mt-1">El costo se escala segun las porciones objetivo.</p>
              </div>
              <span class="badge">Costo base: <b x-text="money(baseCostoUsado)"></b></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
              <div class="md:col-span-8">
                <label class="block text-sm font-bold text-chocolate-700 mb-1">Selecciona bizcocho / receta *</label>
                <select name="receta_base_id" x-model.number="recetaBaseId" @change="onBaseChange()" required>
                  <option value="0">— Selecciona una receta base —</option>
                  <template x-for="r in recetas" :key="r.id">
                    <option :value="Number(r.id)"
                            x-text="`${r.nombre} · ${r.porciones} porciones · ${money(r.costo_total)}`"></option>
                  </template>
                </select>
              </div>
              <div class="md:col-span-4">
                <div class="rounded-2xl border-2 border-rose-100 bg-cream-50 px-4 py-3">
                  <p class="text-xs uppercase tracking-wide text-chocolate-500">Factor usado</p>
                  <p class="font-bold text-chocolate-900" x-text="baseFactor.toFixed(2) + 'x'"></p>
                </div>
              </div>
            </div>
          </section>

          <section class="card space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div>
                <h2 class="font-bold text-chocolate-900 text-lg">2. Rellenos, fruta y extras</h2>
                <p class="text-sm text-chocolate-500 mt-1">Agrega manjar, crema, fruta, caja, decoracion o cualquier insumo adicional.</p>
              </div>
              <button type="button" @click="addExtra()" class="btn btn-secondary !py-2">
                <span>＋</span> Agregar extra
              </button>
            </div>

            <div class="space-y-3">
              <template x-for="(item, index) in extras" :key="index">
                <div class="grid grid-cols-12 gap-3 items-end rounded-2xl border-2 border-rose-100 bg-white/70 p-4">
                  <div class="col-span-12 md:col-span-5">
                    <label class="block text-xs font-bold text-chocolate-700 mb-1">Ingrediente</label>
                    <select x-model.number="item.ingrediente_id" @change="onExtraChange(index)">
                      <option value="0">— Selecciona —</option>
                      <template x-for="ing in ingredientes" :key="ing.id">
                        <option :value="Number(ing.id)"
                                x-text="`${ing.nombre} · ${ing.unidad_medida} · ${money(ing.costo_base)}`"></option>
                      </template>
                    </select>
                  </div>
                  <div class="col-span-6 md:col-span-3">
                    <label class="block text-xs font-bold text-chocolate-700 mb-1">Cantidad</label>
                    <div class="relative">
                      <input type="number" step="0.0001" min="0" x-model.number="item.cantidad" @input="calcExtra(index)">
                      <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-chocolate-500 pointer-events-none" x-text="item.unidad || '—'"></span>
                    </div>
                  </div>
                  <div class="col-span-4 md:col-span-3">
                    <label class="block text-xs font-bold text-chocolate-700 mb-1">Subtotal</label>
                    <div class="px-3 py-3 bg-cream-50 rounded-xl border-2 border-rose-100 font-bold text-rose-500 text-center"
                         x-text="money(item.subtotal)"></div>
                  </div>
                  <div class="col-span-2 md:col-span-1">
                    <button type="button" @click="removeExtra(index)"
                            class="h-11 w-full rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-500 font-bold"
                            aria-label="Quitar extra">×</button>
                  </div>
                </div>
              </template>

              <p x-show="extras.length === 0" class="rounded-2xl border-2 border-dashed border-rose-100 bg-cream-50 p-6 text-center text-chocolate-500">
                Sin extras. Puedes guardar solo la base, o agregar rellenos para costear una torta completa.
              </p>
            </div>
          </section>
        </div>

        <aside class="space-y-6 xl:sticky xl:top-6 self-start">
          <section class="cost-card">
            <span class="drip-top"></span>
            <h2 class="font-bold text-chocolate-900 text-lg mb-4">Costo final</h2>

            <div class="space-y-3 text-sm">
              <div class="flex justify-between gap-3">
                <span class="text-chocolate-700">Receta base</span>
                <strong x-text="money(baseCostoUsado)"></strong>
              </div>
              <div class="flex justify-between gap-3">
                <span class="text-chocolate-700">Extras</span>
                <strong x-text="money(costoExtras)"></strong>
              </div>
              <div>
                <label class="block text-sm font-bold text-chocolate-700 mb-1">Otros costos</label>
                <input type="number" step="1" min="0" name="otros_costos" x-model.number="otrosCostos">
                <p class="text-xs text-chocolate-500 mt-1">Gas, luz, caja, delivery, decoracion no inventariada.</p>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-sm font-bold text-chocolate-700 mb-1">IVA %</label>
                  <input type="number" step="0.01" min="0" name="iva_porcentaje" x-model.number="ivaPorcentaje">
                </div>
                <div>
                  <label class="block text-sm font-bold text-chocolate-700 mb-1">Margen %</label>
                  <input type="number" step="1" min="0" name="margen_porcentaje" x-model.number="margenPorcentaje">
                </div>
              </div>

              <hr class="border-rose-200">
              <div class="flex justify-between gap-3">
                <span class="text-chocolate-700">IVA</span>
                <strong x-text="money(ivaMonto)"></strong>
              </div>
              <div class="flex justify-between gap-3 text-lg">
                <span class="font-bold text-chocolate-900">Costo total</span>
                <strong class="text-rose-500" x-text="money(costoTotal)"></strong>
              </div>
              <div class="flex justify-between gap-3">
                <span class="text-chocolate-700">Costo / porcion</span>
                <strong x-text="money(costoPorcion)"></strong>
              </div>
              <div class="rounded-2xl bg-white/70 border-2 border-rose-100 p-4 text-center">
                <p class="text-xs uppercase tracking-wide text-chocolate-500">Precio sugerido</p>
                <p class="font-bold text-rose-500 text-3xl" x-text="money(precioSugerido)"></p>
                <p class="text-xs text-chocolate-500 mt-1">
                  Ganancia: <span x-text="money(gananciaMonto)"></span>
                </p>
              </div>
            </div>
          </section>

          <section class="card text-sm text-chocolate-700">
            <h3 class="font-bold text-chocolate-900 mb-2">Como leerlo</h3>
            <p>
              Si la receta base rinde 20 porciones y esta torta es de 10, se usa la mitad del costo del bizcocho.
              Los extras se calculan con los precios actuales del inventario y quedan guardados como historico.
            </p>
          </section>

          <div class="flex flex-col gap-2">
            <button type="submit" class="btn btn-primary justify-center">
              <span>💾</span> Guardar armado
            </button>
            <a href="<?= url('tortas.php') ?>" class="btn btn-ghost justify-center">Volver al listado</a>
          </div>
        </aside>
      </div>

      <input type="hidden" name="extras_json" :value="extrasJson">
    </form>
  <?php endif; ?>
<?php endif; ?>

<section>
  <div class="flex items-end justify-between gap-4 mb-4">
    <div>
      <h2 class="font-bold text-chocolate-900 text-xl">Tortas armadas guardadas</h2>
      <p class="text-sm text-chocolate-500 mt-1"><?= count($tortas) ?> armado(s) con costo historico.</p>
    </div>
  </div>

  <?php if (empty($tortas)): ?>
    <div class="card text-center py-12">
      <div class="text-6xl mb-3">🎂</div>
      <h3 class="font-sweet text-2xl text-rose-500 mb-2">Aun no hay tortas armadas</h3>
      <p class="text-chocolate-700 mb-5">Crea una torta completa a partir de una receta base y sus rellenos.</p>
      <a href="<?= url('tortas.php?accion=crear') ?>" class="btn btn-primary">Armar primera torta</a>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <?php foreach ($tortas as $t): ?>
        <article class="card card-hover">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="font-bold text-chocolate-900 text-lg"><?= e($t['nombre']) ?></h3>
              <p class="text-sm text-chocolate-500 mt-1">
                Base: <?= e($t['receta_base_nombre']) ?> · <?= (int)$t['porciones_objetivo'] ?> porciones
              </p>
            </div>
            <span class="badge"><?= date('d/m/Y', strtotime($t['updated_at'])) ?></span>
          </div>

          <?php if (!empty($t['descripcion'])): ?>
            <p class="text-sm text-chocolate-700 mt-3"><?= e($t['descripcion']) ?></p>
          <?php endif; ?>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 text-center">
            <div class="rounded-2xl bg-cream-50 border-2 border-rose-100 p-3">
              <p class="text-xs text-chocolate-500">Base</p>
              <p class="font-bold text-chocolate-900"><?= format_money((float)$t['base_costo_usado']) ?></p>
            </div>
            <div class="rounded-2xl bg-cream-50 border-2 border-rose-100 p-3">
              <p class="text-xs text-chocolate-500">Extras</p>
              <p class="font-bold text-chocolate-900"><?= format_money((float)$t['costo_extras']) ?></p>
            </div>
            <div class="rounded-2xl bg-rose-50 border-2 border-rose-100 p-3">
              <p class="text-xs text-chocolate-500">Costo</p>
              <p class="font-bold text-rose-500"><?= format_money((float)$t['costo_total']) ?></p>
            </div>
            <div class="rounded-2xl bg-mint-100 border-2 border-mint-300 p-3">
              <p class="text-xs text-chocolate-500">Sugerido</p>
              <p class="font-bold text-chocolate-900"><?= format_money((float)$t['precio_sugerido']) ?></p>
            </div>
          </div>

          <div class="mt-5 flex flex-wrap gap-2 justify-end" x-data="confirmDelete('¿Eliminar esta torta armada?')">
            <a href="<?= url('tortas.php?accion=editar&id=' . (int)$t['id']) ?>" class="btn btn-secondary !py-2 !text-xs">
              Editar / recalcular
            </a>
            <form method="post" action="<?= url('tortas.php?accion=eliminar&id=' . (int)$t['id']) ?>">
              <?= csrf_field() ?>
              <button type="submit" @click.prevent="ask(() => $event.target.form.submit())" class="btn btn-danger !py-2 !text-xs">
                Eliminar
              </button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('cakeBuilder', (initial) => ({
    nombre: initial.nombre || '',
    descripcion: initial.descripcion || '',
    recetaBaseId: Number(initial.receta_base_id || 0),
    porcionesObjetivo: Number(initial.porciones_objetivo || 10),
    otrosCostos: Number(initial.otros_costos || 0),
    ivaPorcentaje: Number(initial.iva_porcentaje || 0),
    margenPorcentaje: Number(initial.margen_porcentaje || 60),
    recetas: Array.isArray(initial.recetas) ? initial.recetas : [],
    ingredientes: Array.isArray(initial.ingredientes) ? initial.ingredientes : [],
    extras: Array.isArray(initial.extras) ? initial.extras : [],

    init() {
      if (this.extras.length === 0) this.addExtra();
      this.extras.forEach((_, i) => this.onExtraChange(i));
    },
    addExtra() {
      this.extras.push({ ingrediente_id: 0, cantidad: 0, unidad: '', costo_unitario: 0, subtotal: 0 });
    },
    removeExtra(index) {
      this.extras.splice(index, 1);
    },
    recetaBase() {
      return this.recetas.find(r => Number(r.id) === Number(this.recetaBaseId)) || null;
    },
    onBaseChange() {},
    onExtraChange(index) {
      const item = this.extras[index];
      const ing = this.ingredientes.find(x => Number(x.id) === Number(item.ingrediente_id));
      if (!ing) {
        item.unidad = '';
        item.costo_unitario = 0;
        item.subtotal = 0;
        return;
      }
      item.unidad = ing.unidad_medida;
      item.costo_unitario = Number(ing.costo_base) || 0;
      this.calcExtra(index);
    },
    calcExtra(index) {
      const item = this.extras[index];
      const ing = this.ingredientes.find(x => Number(x.id) === Number(item.ingrediente_id));
      if (!ing) {
        item.subtotal = 0;
        return;
      }
      const cantidad = Number(item.cantidad) || 0;
      const factor = (ing.unidad_medida === 'gramo' || ing.unidad_medida === 'mililitro') ? 0.001 : 1;
      item.subtotal = Math.round((Number(ing.costo_base) || 0) * cantidad * factor * 10000) / 10000;
    },
    get basePorciones() {
      const base = this.recetaBase();
      return base ? Math.max(1, Number(base.porciones) || 1) : 1;
    },
    get baseCostoTotal() {
      const base = this.recetaBase();
      return base ? Number(base.costo_total) || 0 : 0;
    },
    get baseFactor() {
      return Math.max(1, Number(this.porcionesObjetivo) || 1) / this.basePorciones;
    },
    get baseCostoUsado() {
      return this.baseCostoTotal * this.baseFactor;
    },
    get costoExtras() {
      return this.extras.reduce((acc, item) => acc + (Number(item.subtotal) || 0), 0);
    },
    get subtotal() {
      return this.baseCostoUsado + this.costoExtras + (Number(this.otrosCostos) || 0);
    },
    get ivaMonto() {
      return this.subtotal * (Number(this.ivaPorcentaje) || 0) / 100;
    },
    get costoTotal() {
      return this.subtotal + this.ivaMonto;
    },
    get costoPorcion() {
      return this.costoTotal / Math.max(1, Number(this.porcionesObjetivo) || 1);
    },
    get gananciaMonto() {
      return this.costoTotal * (Number(this.margenPorcentaje) || 0) / 100;
    },
    get precioSugerido() {
      return this.costoTotal + this.gananciaMonto;
    },
    get extrasJson() {
      return JSON.stringify(this.extras
        .filter(item => Number(item.ingrediente_id) > 0 && Number(item.cantidad) > 0)
        .map(item => ({
          ingrediente_id: Number(item.ingrediente_id),
          cantidad: Number(item.cantidad)
        })));
    },
    prepareSubmit() {
      this.extras.forEach((_, i) => this.calcExtra(i));
    },
    money(value) {
      const n = Number(value) || 0;
      return '$' + Math.round(n).toLocaleString('es-CL');
    }
  }));
});
</script>

<?php clear_old(); ?>
<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>
