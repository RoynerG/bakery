<?php
/**
 * Página: Crear / Editar Receta — WIZARD de 5 pasos
 *
 * Paso 1: Datos básicos
 * Paso 2: Ingredientes con calculadora en tiempo real
 * Paso 3: Instrucciones paso a paso (con vista previa)
 * Paso 4: Fotografía del platillo
 * Paso 5: Reporte de costos tipo Excel (margen, IVA, precio)
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Receta;
use App\Models\Ingrediente;
use App\Auth;

// Crear/editar recetas requiere autenticación
Auth::require();

$recetaId  = isset($_GET['editar']) ? (int)$_GET['editar'] : 0;
$esEdicion = $recetaId > 0;
$receta    = null;
$items     = [];

if ($esEdicion) {
    $receta = Receta::find($recetaId);
    if (!$receta) {
        flash('error', 'La receta no existe.');
        redirect('index.php');
    }
    foreach ($receta['ingredientes'] as $ri) {
        $items[] = [
            'ingrediente_id' => (int)$ri['ingrediente_id'],
            'cantidad'       => (float)$ri['cantidad'],
            'unidad'         => $ri['unidad_medida'],
            'costo_unitario' => (float)$ri['costo_base'],
            'subtotal'       => (float)$ri['subtotal'],
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        $nombre        = trim($_POST['nombre'] ?? '');
        $descripcion   = trim($_POST['descripcion'] ?? '');
        $instrucciones = trim($_POST['instrucciones'] ?? '');
        $porciones     = (int)($_POST['porciones'] ?? 1);

        $itemsPost = [];
        if (!empty($_POST['ingredientes_json'])) {
            $decoded = json_decode($_POST['ingredientes_json'], true);
            if (is_array($decoded)) {
                $itemsPost = array_values(array_filter($decoded, fn($i) =>
                    !empty($i['ingrediente_id']) && (float)($i['cantidad'] ?? 0) > 0
                ));
            }
        }
        if (empty($itemsPost)) throw new InvalidArgumentException('Agrega al menos un ingrediente.');

        $upload     = handle_upload('imagen');
        if (!$upload['ok']) throw new RuntimeException($upload['error']);
        $imagenFile = $upload['filename'];

        $data = compact('nombre', 'descripcion', 'instrucciones', 'porciones');

        if ($esEdicion) {
            Receta::update($recetaId, $data, $itemsPost, $imagenFile, (bool)$imagenFile);
            flash('success', '🎂 Receta actualizada.');
            redirect('ver-receta.php?id=' . $recetaId);
        } else {
            $newId = Receta::create($data, $itemsPost, $imagenFile);
            flash('success', '🎉 ¡Receta creada! Se ve deliciosa.');
            redirect('ver-receta.php?id=' . $newId);
        }
    } catch (Throwable $e) {
        keep_old($_POST);
        flash('error', $e->getMessage());
        redirect($esEdicion ? 'receta.php?editar=' . $recetaId : 'receta.php');
    }
}

$initialData = [
    'nombre'        => old('nombre', $receta['nombre'] ?? ''),
    'descripcion'   => old('descripcion', $receta['descripcion'] ?? ''),
    'porciones'     => (int)old('porciones', $receta['porciones'] ?? 8),
    'instrucciones' => old('instrucciones', $receta['instrucciones'] ?? ''),
    'items'         => $items,
    'ingredientes'  => Ingrediente::all(),
    'imagenActual'  => $receta['imagen'] ?? null,
];

$titulo = $esEdicion ? 'Editar receta' : 'Nueva receta';
?>
<?php require_once __DIR__ . '/../src/layout/header.php'; ?>

<section class="mb-6 flex items-center gap-3">
  <a href="<?= url($esEdicion ? 'ver-receta.php?id=' . $recetaId : 'index.php') ?>" class="btn btn-ghost !py-1 !px-3">← Volver</a>
  <div>
    <h1 class="font-sweet text-3xl text-rose-500"><?= e($titulo) ?></h1>
  </div>
</section>

<form method="post" enctype="multipart/form-data"
      x-data='recipeWizard(<?= json_encode($initialData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)'
      x-init="<?= empty($items) ? 'addItem()' : '' ?>"
      @submit.prevent="submit($event.target)"
      class="space-y-6">

  <?= csrf_field() ?>

  <!-- ============ STEPPER ============ -->
  <div class="card !p-5">
    <div class="flex items-center justify-between mb-3 text-sm">
      <span class="font-bold text-chocolate-700">
        Paso <span x-text="step"></span> de <span x-text="totalSteps"></span>
      </span>
      <span class="font-bold text-rose-500" x-text="progressPct + '%'"></span>
    </div>
    <div class="wizard-progress">
      <div class="wizard-progress-fill" :style="`width: ${progressPct}%`"></div>
    </div>
    <div class="stepper mt-5">
      <template x-for="s in steps" :key="s.n">
        <div class="stepper-item"
             :class="{ active: step === s.n, done: step > s.n }"
             @click="goTo(s.n)">
          <div class="stepper-circle" x-text="step > s.n ? '✓' : s.icon"></div>
          <div class="stepper-label" x-text="s.label"></div>
        </div>
      </template>
    </div>
  </div>

  <!-- ============ PASO 1 ============ -->
  <div x-show="step === 1"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0 translate-y-4"
       x-transition:enter-end="opacity-100 translate-y-0"
       x-cloak>
    <div class="step-panel" data-icon="📝">
      <h2 class="step-title">Cuéntanos de tu creación</h2>
      <p class="step-subtitle">Empecemos con lo básico. ¿Cómo se llama tu receta?</p>

      <div class="text-center mb-6">
        <span class="text-6xl animate-wiggle inline-block" x-text="nombre ? '🍰' : '🧁'"></span>
      </div>

      <div class="space-y-4 max-w-2xl mx-auto">
        <div>
          <label class="block text-sm font-bold text-chocolate-700 mb-1">
            Nombre de la receta <span class="text-rose-500">*</span>
          </label>
          <input type="text" x-model="nombre" maxlength="180"
                 placeholder="Ej. Pastel de chocolate esponjoso" class="text-lg">
        </div>

        <div>
          <label class="block text-sm font-bold text-chocolate-700 mb-1">
            Descripción corta <span class="text-chocolate-500 font-normal">(opcional)</span>
          </label>
          <input type="text" x-model="descripcion" maxlength="500"
                 placeholder="Una breve descripción de tu delicia...">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-bold text-chocolate-700 mb-1">
              🍽️ Porciones <span class="text-rose-500">*</span>
            </label>
            <input type="number" x-model.number="porciones" min="1" max="999">
          </div>
          <div class="flex items-center justify-center">
            <div class="cost-card w-full text-center">
              <div class="text-xs uppercase text-chocolate-500">Rinde para</div>
              <div class="text-2xl font-bold text-rose-500" x-text="porciones + ' personas'"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ============ PASO 2 ============ -->
  <div x-show="step === 2"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0 translate-y-4"
       x-transition:enter-end="opacity-100 translate-y-0"
       x-cloak>
    <div class="step-panel" data-icon="🥣">
      <h2 class="step-title">Ingredientes y costos</h2>
      <p class="step-subtitle">Agrega los ingredientes. El costo se calcula al instante. ✨</p>

      <?php if (empty($initialData['ingredientes'])): ?>
        <div class="bg-cream-100 border-2 border-cream-200 rounded-2xl p-6 text-center">
          <div class="text-5xl mb-2 animate-wiggle inline-block">😢</div>
          <p class="text-chocolate-700 mb-3">Primero necesitas ingredientes en tu inventario.</p>
          <a href="<?= url('inventario.php?accion=crear') ?>" class="btn btn-primary">
            <span>📦</span> Ir al inventario
          </a>
        </div>
      <?php else: ?>
        <div class="space-y-3">
          <template x-for="(item, index) in items" :key="index">
            <div class="ing-row grid grid-cols-12 gap-3 items-end">
              <span class="ing-num" x-text="'#' + (index + 1)"></span>
              <div class="col-span-12 sm:col-span-5">
                <label class="block text-xs font-bold text-chocolate-700 mb-1" x-show="index === 0">Ingrediente</label>
                <select x-model="item.ingrediente_id" @change="onIngChange(index)">
                  <option value="">— Selecciona —</option>
                  <template x-for="ing in ingredientes" :key="ing.id">
                    <option :value="ing.id" x-text="ing.nombre + ' · ' + ing.unidad_medida + ' · $' + parseFloat(ing.costo_base).toFixed(2)"></option>
                  </template>
                </select>
              </div>
              <div class="col-span-6 sm:col-span-3">
                <label class="block text-xs font-bold text-chocolate-700 mb-1" x-show="index === 0">Cantidad</label>
                <div class="relative">
                  <input type="number" step="0.0001" min="0" x-model.number="item.cantidad" @input="calcRow(index)">
                  <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-chocolate-500 pointer-events-none" x-text="item.unidad || '—'"></span>
                </div>
              </div>
              <div class="col-span-4 sm:col-span-3">
                <label class="block text-xs font-bold text-chocolate-700 mb-1" x-show="index === 0">Subtotal</label>
                <div class="px-3 py-3 bg-white rounded-xl border-2 border-rose-100 font-bold text-rose-500 text-center" x-text="money(item.subtotal)"></div>
              </div>
              <div class="col-span-2 sm:col-span-1 flex justify-end">
                <button type="button" @click="removeItem(index)" class="w-10 h-10 rounded-full bg-rose-50 hover:bg-rose-100 text-rose-500 hover:text-rose-600 flex items-center justify-center transition" title="Quitar">✕</button>
              </div>
            </div>
          </template>

          <button type="button" @click="addItem()" class="btn btn-secondary w-full justify-center !border-dashed">
            <span class="text-xl">＋</span> Agregar otro ingrediente
          </button>
        </div>

        <div class="mt-6 cost-card">
          <span class="drip-top"></span>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-center">
            <div>
              <div class="text-xs uppercase text-chocolate-500">Ingredientes</div>
              <div class="font-bold text-chocolate-700 text-xl" x-text="items.length"></div>
            </div>
            <div>
              <div class="text-xs uppercase text-chocolate-500">Costo base</div>
              <div class="font-bold text-rose-500 text-xl" x-text="money(costoIngredientes)"></div>
            </div>
            <div class="col-span-2 sm:col-span-1">
              <div class="text-xs uppercase text-chocolate-500">Costo / porción</div>
              <div class="font-bold text-rose-500 text-xl" x-text="money(costoPorcion)"></div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ============ PASO 3 ============ -->
  <div x-show="step === 3"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0 translate-y-4"
       x-transition:enter-end="opacity-100 translate-y-0"
       x-cloak>
    <div class="step-panel" data-icon="👩‍🍳">
      <h2 class="step-title">Preparación paso a paso</h2>
      <p class="step-subtitle">Escribe cada paso en una línea nueva. ✨</p>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-bold text-chocolate-700 mb-1">Tus instrucciones</label>
          <textarea x-model="instrucciones"
                    placeholder="1. Precalienta el horno a 180°C&#10;2. Bate la mantequilla con el azúcar&#10;3. Agrega los huevos uno a uno&#10;4. ..."></textarea>
          <p class="text-xs text-chocolate-500 mt-2">💡 Cada línea será un paso numerado automáticamente.</p>
        </div>
        <div>
          <label class="block text-sm font-bold text-chocolate-700 mb-1">Vista previa</label>
          <div class="bg-cream-50 rounded-2xl p-4 border-2 border-rose-100 min-h-[200px]">
            <template x-if="listaPasos.length === 0">
              <div class="text-center text-chocolate-400 py-8">
                <div class="text-4xl mb-2">📝</div>
                <p>Tus pasos aparecerán aquí...</p>
              </div>
            </template>
            <template x-for="(paso, i) in listaPasos" :key="i">
              <div class="paso-item">
                <div class="paso-num" x-text="i + 1"></div>
                <div class="flex-1 text-chocolate-800 pt-1" x-text="paso"></div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ============ PASO 4 ============ -->
  <div x-show="step === 4"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0 translate-y-4"
       x-transition:enter-end="opacity-100 translate-y-0"
       x-cloak>
    <div class="step-panel" data-icon="📸">
      <h2 class="step-title">Una foto deliciosa</h2>
      <p class="step-subtitle">Sube una imagen de tu platillo. (Opcional). 📸</p>

      <label class="block">
        <div class="drop-zone" :class="{ 'has-file': imagenPreview }">
          <input id="imagen-input" type="file" name="imagen" accept="image/*" @change="handleImage($event)" class="hidden">
          <template x-if="!imagenPreview">
            <div>
              <div class="text-6xl mb-2 animate-floaty inline-block">📷</div>
              <p class="font-bold text-chocolate-700">Arrastra una imagen aquí</p>
              <p class="text-sm text-chocolate-500">o haz clic para seleccionar</p>
              <p class="text-xs text-chocolate-400 mt-2">JPG, PNG, WebP o GIF · Máx 5 MB</p>
            </div>
          </template>
          <template x-if="imagenPreview">
            <div>
              <img :src="imagenPreview" alt="preview" class="w-full max-h-80 object-cover rounded-2xl shadow-lg">
              <button type="button" @click.stop="clearImage()" class="btn btn-danger mt-3">
                <span>✕</span> Quitar imagen
              </button>
            </div>
          </template>
        </div>
      </label>
    </div>
  </div>

  <!-- ============ PASO 5 ============ -->
  <div x-show="step === 5"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0 translate-y-4"
       x-transition:enter-end="opacity-100 translate-y-0"
       x-cloak>
    <div class="step-panel" data-icon="💰">
      <h2 class="step-title">Reporte de costos</h2>
      <p class="step-subtitle">Tu Excel pastelero. Ajusta IVA, margen y costos extra. 📊</p>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
          <div class="flex items-center gap-2 mb-3">
            <span class="text-2xl">📊</span>
            <h3 class="font-bold text-chocolate-900">Desglose de producción</h3>
          </div>
          <table class="excel-table">
            <thead>
              <tr>
                <th>Ingrediente</th>
                <th class="num">Cantidad</th>
                <th class="num">Costo / unidad</th>
                <th class="num">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="(it, i) in items" :key="i">
                <tr>
                  <td x-text="(ingredientes.find(x => x.id == it.ingrediente_id) || {}).nombre || '—'"></td>
                  <td class="num" x-text="cantUnidad(it.unidad, it.cantidad)"></td>
                  <td class="num" x-text="money(it.costo_unitario)"></td>
                  <td class="num-money" x-text="money(it.subtotal)"></td>
                </tr>
              </template>
              <template x-if="items.length === 0">
                <tr><td colspan="4" class="text-center text-chocolate-400 py-6">Sin ingredientes</td></tr>
              </template>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="3" class="text-right">Subtotal ingredientes</td>
                <td class="num-money" x-text="money(costoIngredientes)"></td>
              </tr>
              <tr>
                <td colspan="3" class="text-right">Otros costos (gas, luz, empaque)</td>
                <td class="num-money" x-text="money(costoOtros)"></td>
              </tr>
              <tr>
                <td colspan="3" class="text-right">IVA (<span x-text="iva"></span>%)</td>
                <td class="num-money" x-text="money(ivaMonto)"></td>
              </tr>
              <tr style="background: linear-gradient(135deg, #ffd6e7, #ffb3d1);">
                <td colspan="3" class="text-right text-chocolate-900" style="font-size: 1.05rem;">💰 COSTO TOTAL</td>
                <td class="num-money" style="font-size: 1.15rem;" x-text="money(costoTotal)"></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="space-y-4">
          <div class="card">
            <h3 class="font-bold text-chocolate-900 mb-3 flex items-center gap-2">
              <span>⚙️</span> Configuración
            </h3>
            <div class="space-y-4">
              <div>
                <label class="flex items-center justify-between mb-1">
                  <span class="text-sm font-bold text-chocolate-700">¿Aplicar IVA?</span>
                  <label class="toggle">
                    <input type="checkbox" x-model.number="iva" :true-value="16" :false-value="0">
                    <span class="toggle-slider"></span>
                  </label>
                </label>
                <p class="text-xs text-chocolate-500" x-text="iva > 0 ? 'IVA del 16%' : 'Sin IVA'"></p>
              </div>
              <div>
                <label class="block text-sm font-bold text-chocolate-700 mb-1">Otros costos extra ($)</label>
                <input type="number" step="0.01" min="0" x-model.number="otrosCostos" placeholder="0.00">
                <p class="text-xs text-chocolate-500 mt-1">Gas, luz, empaque, decoración...</p>
              </div>
              <div>
                <label class="block text-sm font-bold text-chocolate-700 mb-1">
                  Margen de ganancia: <span class="text-rose-500" x-text="margen + '%'"></span>
                </label>
                <input type="range" min="0" max="200" step="5" x-model.number="margen">
                <div class="flex justify-between text-xs text-chocolate-500 mt-1">
                  <span>0%</span><span>100%</span><span>200%</span>
                </div>
              </div>
            </div>
          </div>

          <div class="cost-card">
            <span class="drip-top"></span>
            <h3 class="font-bold text-chocolate-900 mb-3 flex items-center gap-2">
              <span>📈</span> Resumen
            </h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between"><span class="text-chocolate-700">Costo total:</span><span class="font-bold" x-text="money(costoTotal)"></span></div>
              <div class="flex justify-between"><span class="text-chocolate-700">Costo / porción:</span><span class="font-bold" x-text="money(costoPorcion)"></span></div>
              <hr class="border-rose-200">
              <div class="flex justify-between text-mint-500 font-bold"><span>Ganancia (<span x-text="margen"></span>%):</span><span x-text="money(ganancia)"></span></div>
              <div class="flex justify-between text-rose-500 font-bold text-lg"><span>Precio sugerido:</span><span x-text="money(precioVenta)"></span></div>
              <div class="text-center mt-3 pt-3 border-t border-rose-200">
                <div class="text-xs uppercase text-chocolate-500">Vender cada porción a</div>
                <div class="text-2xl font-bold text-rose-500" x-text="money(precioPorcion)"></div>
              </div>
            </div>
          </div>

          <div class="card bg-mint-100/40 border-mint-300 text-sm">
            <p class="font-bold mb-1">💡 Tip</p>
            <p class="text-chocolate-700">El margen típico en repostería casera es del <b>60-100%</b>. Considera también el tiempo invertido.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ============ NAVEGACIÓN ============ -->
  <div class="card !p-4 sticky bottom-4 backdrop-blur-md bg-white/90 z-10 shadow-2xl">
    <div class="flex items-center justify-between gap-3">
      <button type="button" @click="prev()" x-show="step > 1" x-transition class="btn btn-ghost">← Atrás</button>
      <div class="flex-1"></div>
      <div class="text-xs text-chocolate-500 hidden sm:block">
        <span x-show="!canProceed() && step < 5">⚠️ Completa este paso para continuar</span>
        <span x-show="canProceed() && step < 5" class="text-mint-500 font-bold">✓ Listo para continuar</span>
      </div>
      <div class="flex-1"></div>
      <button type="button" @click="next()" x-show="step < totalSteps" :disabled="!canProceed()" class="btn btn-primary">
        Siguiente →
      </button>
      <button type="submit" x-show="step === totalSteps" class="btn btn-primary btn-lg animate-pop">
        🎂 Guardar receta
      </button>
    </div>
  </div>

  <!-- Campos ocultos -->
  <input type="hidden" name="nombre" :value="nombre">
  <input type="hidden" name="descripcion" :value="descripcion">
  <input type="hidden" name="porciones" :value="porciones">
  <input type="hidden" name="instrucciones" :value="instrucciones">
  <input type="hidden" name="ingredientes_json" :value="itemsJson">
</form>

<!-- ============ OVERLAY DE CARGA ============ -->
<div x-show="submitting"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-cloak
     class="loading-overlay">
  <span class="loading-orbit o1">🍪</span>
  <span class="loading-orbit o2">🍩</span>
  <span class="loading-orbit o3">🧁</span>
  <span class="loading-orbit o4">🍰</span>
  <div class="loading-cupcake">🧁</div>
  <div class="loading-msg" x-text="loadingMsg">Precalentando...</div>
  <div class="loading-bar">
    <div class="loading-bar-fill" :style="`width: ${progress}%`"></div>
  </div>
  <div class="loading-percent" x-text="progress + '%'">0%</div>
</div>

<?php clear_old(); ?>
<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>
