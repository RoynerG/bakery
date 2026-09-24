<?php
/**
 * Admin: listado y gestion de productos del catalogo.
 */
declare(strict_types=1);

use App\Models\Producto;
use App\Auth;
use App\Database;

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

Auth::require();

$accion = $_GET['accion'] ?? 'listar';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        if ($accion === 'eliminar' && $id > 0) {
            $ok = Producto::delete($id);
            flash($ok ? 'success' : 'error', $ok
                ? '🗑️ Producto eliminado.'
                : '😢 No se pudo eliminar el producto.');
            redirect('productos.php');
        }
        if ($accion === 'toggle' && $id > 0) {
            $p = Producto::find($id);
            if ($p) {
                Database::getInstance()->execute(
                    'UPDATE productos SET visible = ? WHERE id = ?',
                    [empty($p['visible']) ? 1 : 0, $id]
                );
                flash('success', 'Visibilidad actualizada.');
                redirect('productos.php');
            }
        }
        if ($accion === 'ordenar' && isset($_POST['orden'])) {
            foreach ((array)$_POST['orden'] as $pid => $orden) {
                Database::getInstance()->execute(
                    'UPDATE productos SET orden = ? WHERE id = ?',
                    [(int)$orden, (int)$pid]
                );
            }
            flash('success', 'Orden actualizado.');
            redirect('productos.php');
        }
        if ($accion === 'guardar_catalogo') {
            try {
                Database::getInstance()->fetchOne('SELECT 1 FROM config LIMIT 1');
            } catch (Throwable $e) {
                throw new RuntimeException(
                    'La tabla `config` aun no existe. Corre la migracion SQL '
                    . '(secciones 16-17 de database/migrate_mysql.sql) en phpMyAdmin '
                    . 'antes de guardar.'
                );
            }
            foreach (['catalogo_tagline', 'catalogo_instagram', 'catalogo_whatsapp', 'catalogo_cover_deco'] as $clave) {
                set_config($clave, $_POST[$clave] ?? '');
            }
            flash('success', 'Configuracion del catalogo guardada.');
            redirect('productos.php');
        }
        // Tamanos por categoria: guardar lote
        if ($accion === 'guardar_tamanos' && isset($_POST['tipo_cat'])) {
            $tipoCat = $_POST['tipo_cat'];
            if (!in_array($tipoCat, ['clasica', 'premium', 'destacado'], true)) {
                throw new RuntimeException('Tipo no valido.');
            }
            try {
                $db = Database::getInstance();
                $db->execute('DELETE FROM categoria_variantes WHERE tipo = ?', [$tipoCat]);
                $orden = 0;
                if (isset($_POST['items']) && is_array($_POST['items'])) {
                    foreach ($_POST['items'] as $it) {
                        $label = trim($it['label'] ?? '');
                        if ($label === '') continue;
                        $precio = parse_clp($it['precio'] ?? 0);
                        $db->execute(
                            'INSERT INTO categoria_variantes (tipo, label, precio, orden) VALUES (?, ?, ?, ?)',
                            [$tipoCat, $label, $precio, $orden++]
                        );
                    }
                }
                flash('success', 'Tamaños de ' . $tipoCat . ' actualizados.');
            } catch (Throwable $e) {
                flash('error', 'No se pudieron guardar los tamaños. ¿Corriste la migración SQL? Detalle: ' . $e->getMessage());
            }
            redirect('productos.php#cat-' . $tipoCat);
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect('productos.php');
    }
}

$productos = Producto::all();
$titulo = 'Productos del catalogo';
$tagline   = get_config('catalogo_tagline', 'PASTELERÍA Y REPOSTERÍA ARTESANAL');
$instagram = get_config('catalogo_instagram', '@dulce.rinconcito');
$whatsapp  = get_config('catalogo_whatsapp', '+56 9 4968 080');
$coverDeco = get_config('catalogo_cover_deco', '🥐 🧁 🍰 🍪 🧁');
$crearTipos = [
  'destacado' => ['Destacado', 'Portada y pagina de destacados.'],
  'clasica'   => ['Torta clasica', 'Sabores y rellenos de tortas clasicas.'],
  'premium'   => ['Torta premium', 'Especialidades premium.'],
  'extra'     => ['Extra', 'Adicionales y recargos.'],
  'galeria'   => ['Foto galeria', 'Fotos finales del libro.'],
];
?>
<?php require_once __DIR__ . '/../src/layout/header.php'; ?>

<section class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8" x-data="{ configOpen: false }">
  <div>
    <h1 class="section-title">Productos del catalogo</h1>
    <p class="text-chocolate-700 mt-2">
      Edita lo que se ve en el
      <a href="<?= url('catalogo.php') ?>" target="_blank" class="text-rose-500 hover:underline">libro 3D publico →</a>
    </p>
  </div>
  <div class="flex flex-wrap gap-2">
    <button type="button" @click="configOpen = true" class="btn btn-secondary">
      <span>⚙️</span> Configuracion
    </button>
  </div>

  <div x-show="configOpen"
       x-transition
       x-cloak
       class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
       @keydown.escape.window="configOpen = false">
    <div class="card max-w-2xl w-full max-h-[90vh] overflow-y-auto" @click.outside="configOpen = false">
      <div class="flex items-start justify-between gap-4 mb-5">
        <div>
          <h2 class="font-sweet text-2xl text-rose-500">Configuracion del catalogo</h2>
          <p class="text-sm text-chocolate-500 mt-1">Portada, Instagram y telefono del libro 3D publico.</p>
        </div>
        <button type="button" @click="configOpen = false"
                class="h-10 w-10 rounded-full bg-rose-50 text-chocolate-700 hover:bg-rose-100 font-bold"
                aria-label="Cerrar configuracion">×</button>
      </div>

      <form method="post" action="<?= url('productos.php?accion=guardar_catalogo') ?>" class="space-y-5">
        <?= csrf_field() ?>

        <div>
          <label class="block text-sm font-bold text-chocolate-700 mb-1">Tagline de la portada</label>
          <input type="text" name="catalogo_tagline" maxlength="120"
                 value="<?= e($tagline) ?>"
                 placeholder="PASTELERIA Y REPOSTERIA ARTESANAL">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-bold text-chocolate-700 mb-1">Instagram / Handle</label>
            <input type="text" name="catalogo_instagram" maxlength="60"
                   value="<?= e($instagram) ?>"
                   placeholder="@dulce.rinconcito">
          </div>

          <div>
            <label class="block text-sm font-bold text-chocolate-700 mb-1">WhatsApp / Telefono</label>
            <input type="text" name="catalogo_whatsapp" maxlength="60"
                   value="<?= e($whatsapp) ?>"
                   placeholder="+56 9 4968 080">
          </div>
        </div>

        <div>
          <label class="block text-sm font-bold text-chocolate-700 mb-1">Emojis decorativos de la portada</label>
          <input type="text" name="catalogo_cover_deco" maxlength="80"
                 value="<?= e($coverDeco) ?>"
                 placeholder="🥐 🧁 🍰 🍪 🧁">
        </div>

        <div class="flex flex-wrap justify-end gap-3 pt-2">
          <button type="button" @click="configOpen = false" class="btn btn-ghost">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <span>💾</span> Guardar configuracion
          </button>
        </div>
      </form>
    </div>
  </div>
</section>

<section class="card mb-8">
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
      <h2 class="font-bold text-chocolate-900 text-lg">Crear por seccion</h2>
      <p class="text-sm text-chocolate-500 mt-1">Cada boton abre solo los campos necesarios para esa parte del libro.</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2">
      <?php foreach ($crearTipos as $tipo => [$label, $help]): ?>
        <a href="<?= url('producto.php?accion=crear&tipo=' . urlencode($tipo)) ?>"
           class="rounded-2xl border-2 border-rose-100 bg-cream-50 px-4 py-3 hover:border-rose-300 hover:bg-rose-50 transition-colors">
          <span class="block font-bold text-chocolate-900"><?= e($label) ?></span>
          <span class="block text-xs text-chocolate-500 mt-1"><?= e($help) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if (empty($productos)): ?>
  <div class="card text-center py-16">
    <div class="text-7xl mb-4 animate-wiggle inline-block">📖</div>
    <h3 class="font-sweet text-2xl text-rose-500 mb-2">Tu catalogo esta vacio</h3>
    <p class="text-chocolate-700 mb-6">Agrega tu primer producto para empezar a armar el libro.</p>
    <a href="<?= url('producto.php?accion=crear&tipo=destacado') ?>" class="btn btn-primary">
      <span>➕</span> Agregar destacado
    </a>
  </div>
<?php else: ?>

  <?php
    $porTipo = [];
    foreach ($productos as $p) {
      $porTipo[$p['tipo']][] = $p;
    }
    $tiposLabel = Producto::TIPOS;
  ?>

  <?php foreach ($porTipo as $tipo => $items): ?>
    <section class="mb-8">
      <h2 class="font-bold text-chocolate-900 text-xl mb-3 flex items-center gap-2">
        <span class="badge"><?= count($items) ?></span>
        <?= e($tiposLabel[$tipo] ?? $tipo) ?>
      </h2>
      <div class="card overflow-x-auto">
        <table class="sweet-table">
          <thead>
            <tr>
              <th class="w-16">Imagen</th>
              <th>Nombre</th>
              <th>Slug</th>
              <th>Variantes</th>
              <th>Visible</th>
              <th>Orden</th>
              <th class="text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $p):
              $vars = Producto::variantes((int)$p['id']);
            ?>
              <tr>
                <td>
                  <?php if (!empty($p['imagen'])): ?>
                    <img src="<?= upload_url($p['imagen']) ?>" alt="<?= e($p['nombre']) ?>"
                         class="h-12 w-12 rounded-xl object-cover border-2 border-rose-100">
                  <?php else: ?>
                    <div class="h-12 w-12 rounded-xl bg-rose-50 flex items-center justify-center text-xl border-2 border-rose-100">🧁</div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="font-semibold text-chocolate-900"><?= e($p['nombre']) ?></span>
                  <?php if (!empty($p['descripcion'])): ?>
                    <div class="text-xs text-chocolate-500 mt-1 max-w-md truncate"><?= e(mb_substr($p['descripcion'], 0, 80)) ?></div>
                  <?php endif; ?>
                </td>
                <td><code class="text-xs text-chocolate-500"><?= e($p['slug']) ?></code></td>
                <td class="text-sm">
                  <?php if (empty($vars)): ?>
                    <span class="text-chocolate-400">—</span>
                  <?php else: ?>
                    <span class="text-chocolate-700"><?= count($vars) ?> opcion(es)</span>
                  <?php endif; ?>
                </td>
                <td>
                  <form method="post" action="<?= url('productos.php?accion=toggle&id=' . (int)$p['id']) ?>" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit"
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold <?= !empty($p['visible']) ? 'bg-mint-100 text-chocolate-700' : 'bg-rose-100 text-chocolate-700' ?>">
                      <?= !empty($p['visible']) ? '✓ Visible' : '✗ Oculto' ?>
                    </button>
                  </form>
                </td>
                <td class="text-sm text-chocolate-700"><?= (int)$p['orden'] ?></td>
                <td class="text-right">
                  <div class="inline-flex gap-2">
                    <a href="<?= url('producto.php?accion=editar&id=' . (int)$p['id']) ?>"
                       class="btn btn-secondary !py-1 !px-3 !text-xs">✏️ Editar</a>
                    <form method="post" action="<?= url('productos.php?accion=eliminar&id=' . (int)$p['id']) ?>" class="inline"
                          x-data="confirmDelete('¿Eliminar este producto?')">
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
    </section>
  <?php endforeach; ?>

<?php endif; ?>

<!-- ============================================
     Tamanos / precios por categoria (compartidos)
     ============================================ -->
<?php
  $catVars = [
    'clasica' => categoria_variantes('clasica'),
    'premium' => categoria_variantes('premium'),
  ];
  $catLabel = [
    'clasica' => 'TORTAS CLASICAS',
    'premium' => 'TORTAS PREMIUM',
  ];
?>

<section class="mt-12">
  <h2 class="font-bold text-chocolate-900 text-xl mb-3">Tamaños y precios por categoría</h2>
  <p class="text-sm text-chocolate-500 mb-6 max-w-2xl">
    Estos tamaños se muestran en la tabla al final de las páginas de
    <b>TORTAS CLÁSICAS</b> y <b>TORTAS PREMIUM</b> del libro. Son compartidos
    entre todos los productos de la misma categoría.
  </p>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <?php foreach ($catVars as $tipoCat => $items): ?>
      <form id="cat-<?= e($tipoCat) ?>" method="post"
            action="<?= url('productos.php?accion=guardar_tamanos') ?>"
            class="card space-y-4"
            x-data='catVariantes(<?= json_encode($items) ?>)'>
        <?= csrf_field() ?>
        <input type="hidden" name="tipo_cat" value="<?= e($tipoCat) ?>">

        <h3 class="font-sweet text-2xl text-rose-500 flex items-center justify-between">
          <span><?= e($catLabel[$tipoCat]) ?></span>
          <button type="button" @click="add()" class="btn btn-secondary !py-1 !px-3 !text-xs">
            ➕ Agregar
          </button>
        </h3>

        <div class="space-y-2">
          <template x-for="(it, i) in items" :key="i">
            <div class="grid grid-cols-12 gap-2 items-center">
              <div class="col-span-7">
                <input type="text" :name="`items[${i}][label]`" x-model="it.label"
                       placeholder="Ej. 10 personas">
              </div>
              <div class="col-span-3">
                <input type="text" inputmode="numeric" :name="`items[${i}][precio]`" x-model="it.precioFmt"
                       @input="onPrecio($event, i)"
                       placeholder="Precio CLP">
              </div>
              <div class="col-span-2 text-right">
                <button type="button" @click="remove(i)" class="btn btn-danger !py-1 !px-3 !text-xs w-full">
                  🗑️
                </button>
              </div>
            </div>
          </template>

          <p x-show="items.length === 0" class="text-sm text-chocolate-400 italic text-center py-3">
            Sin tamaños. Agregá al menos uno.
          </p>
        </div>

        <button type="submit" class="btn btn-primary w-full">
          <span>💾</span> Guardar tamaños
        </button>
      </form>
    <?php endforeach; ?>
  </div>
</section>

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('catVariantes', (initial) => ({
    items: Array.isArray(initial) && initial.length > 0
      ? initial.map(v => ({
          label: v.label || '',
          precio: Number(v.precio) || 0,
          precioFmt: (Number(v.precio) || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')
        }))
      : [{ label: '', precio: 0, precioFmt: '' }],
    add() { this.items.push({ label: '', precio: 0, precioFmt: '' }); },
    remove(i) {
      this.items.splice(i, 1);
      if (this.items.length === 0) this.add();
    },
    onPrecio(event, i) {
      var raw = (event.target.value || '').toString().replace(/\D/g, '');
      this.items[i].precio = raw ? parseFloat(raw) : 0;
      this.items[i].precioFmt = this.items[i].precio
        ? this.items[i].precio.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')
        : '';
      event.target.value = this.items[i].precioFmt;
    }
  }));
});
</script>

<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>
