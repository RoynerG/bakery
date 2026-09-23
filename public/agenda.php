<?php
/**
 * Página: Agenda con calendario (FullCalendar)
 * Permite crear, editar y eliminar eventos.
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Agenda;
use App\Models\Categoria;
use App\Auth;

Auth::require();

$accion = $_GET['accion'] ?? 'calendario';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ============ POST: Crear / Actualizar / Eliminar ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        if ($accion === 'crear') {
            Agenda::create($_POST);
            flash('success', '📅 Evento creado.');
            redirect('agenda.php');
        }
        if ($accion === 'editar' && $id > 0) {
            Agenda::update($id, $_POST);
            flash('success', '✅ Evento actualizado.');
            redirect('agenda.php');
        }
        if ($accion === 'eliminar' && $id > 0) {
            $ok = Agenda::delete($id);
            flash($ok ? 'success' : 'error', $ok ? '🗑️ Evento eliminado.' : '😢 No se pudo eliminar.');
            redirect('agenda.php');
        }
    } catch (Throwable $e) {
        keep_old($_POST);
        flash('error', $e->getMessage());
        redirect('agenda.php' . ($accion === 'editar' ? '?accion=editar&id=' . $id : '?accion=crear'));
    }
}

// ============ Cargar evento para edición ============
$eventoEditar = null;
if ($accion === 'editar' && $id > 0) {
    $eventoEditar = Agenda::find($id);
    if (!$eventoEditar) {
        flash('error', 'El evento no existe.');
        redirect('agenda.php');
    }
}

$titulo = 'Agenda';

$categorias = Categoria::all();
?>
<?php require_once __DIR__ . '/../src/layout/header.php'; ?>

<!-- HEADER -->
<section class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
  <div>
    <h1 class="section-title">Agenda Dulce</h1>
    <p class="text-chocolate-700 mt-2">Tus pedidos, entregas y eventos importantes. 📅</p>
  </div>
  <div class="flex gap-2">
    <button type="button" onclick="window.abrirCategorias(); return false;"
            class="btn btn-secondary">
      <span>🏷️</span> Categorías
    </button>
    <a href="<?= url('agenda.php?accion=calendario') ?>"
       class="btn <?= $accion === 'calendario' ? 'btn-primary' : 'btn-secondary' ?>">
      <span>📅</span> Calendario
    </a>
    <a href="<?= url('agenda.php?accion=crear') ?>" class="btn btn-primary">
      <span>➕</span> Nuevo evento
    </a>
  </div>
</section>

<?php if ($accion === 'crear' || $accion === 'editar'): ?>
  <!-- ============ FORMULARIO ============ -->
  <section class="card max-w-2xl mx-auto mb-10">
    <h2 class="font-sweet text-2xl text-rose-500 mb-1">
      <?= $accion === 'crear' ? 'Nuevo evento' : 'Editar evento' ?>
    </h2>
    <p class="text-sm text-chocolate-500 mb-6">Anota un pedido, una entrega o un recordatorio. ✨</p>

    <form method="post"
          action="<?= url('agenda.php?accion=' . $accion . ($id ? '&id=' . $id : '')) ?>"
          class="space-y-5">
      <?= csrf_field() ?>

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">Título del evento *</label>
        <input type="text" name="titulo" required maxlength="180"
               value="<?= e(old('titulo', $eventoEditar['titulo'] ?? '')) ?>"
               placeholder="Ej. Entrega pastel de cumpleaños 🎂">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-bold text-chocolate-700 mb-1">Fecha inicio *</label>
          <input type="date" name="fecha" required
                 value="<?= e(old('fecha', $eventoEditar['fecha'] ?? ($_GET['fecha'] ?? date('Y-m-d')))) ?>">
        </div>
        <div>
          <label class="block text-sm font-bold text-chocolate-700 mb-1">Fecha fin <span class="text-chocolate-500 font-normal">(opcional)</span></label>
          <input type="date" name="fecha_fin"
                 min="<?= e(old('fecha', $eventoEditar['fecha'] ?? date('Y-m-d'))) ?>"
                 value="<?= e(old('fecha_fin', $eventoEditar['fecha_fin'] ?? '')) ?>">
          <p class="text-xs text-chocolate-500 mt-1">Para eventos de varios días.</p>
        </div>
        <div>
          <label class="block text-sm font-bold text-chocolate-700 mb-1">Hora</label>
          <input type="time" name="hora"
                 value="<?= e(old('hora', $eventoEditar['hora'] ?? '')) ?>">
        </div>
        <div class="flex items-end">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="todo_el_dia" value="1"
                   <?= old('todo_el_dia', $eventoEditar['todo_el_dia'] ?? 0) ? 'checked' : '' ?>
                   x-data
                   @change="$event.target.checked ? document.querySelector('[name=hora]').value='' : null"
                   class="w-5 h-5 rounded border-rose-300 text-rose-400 focus:ring-rose-400">
            <span class="text-sm font-semibold text-chocolate-700">Todo el día</span>
          </label>
        </div>
      </div>

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">Descripción <span class="text-chocolate-500 font-normal">(opcional)</span></label>
        <textarea name="descripcion" rows="3"
                  placeholder="Detalles del pedido, nombre del cliente, dirección, etc."
                  class="font-display"><?= e(old('descripcion', $eventoEditar['descripcion'] ?? '')) ?></textarea>
      </div>

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-2">Color</label>
        <div class="flex flex-wrap gap-3">
          <?php
          $colorActual = old('color', $eventoEditar['color'] ?? 'rosa');
          $colores = [
            'rosa'      => ['#ff6b9d', '🌸 Rosa'],
            'crema'     => ['#ffb37a', '🍯 Crema'],
            'menta'     => ['#3eb97a', '🌿 Menta'],
            'chocolate' => ['#8b4513', '🍫 Chocolate'],
          ];
          foreach ($colores as $key => [$hex, $label]):
            $sel = $colorActual === $key ? 'checked' : '';
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

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-2">Categoría</label>
        <div class="flex flex-wrap gap-2">
          <label class="cat-pick cursor-pointer">
            <input type="radio" name="categoria_id" value="" class="sr-only peer"
                   <?= old('categoria_id', $eventoEditar['categoria_id'] ?? '') === null || old('categoria_id', $eventoEditar['categoria_id'] ?? '') === '' ? 'checked' : '' ?>>
            <span class="cat-pill peer-checked:ring-2 peer-checked:ring-rose-400">Sin categoría</span>
          </label>
          <?php foreach ($categorias as $c):
            $selCat = (int)old('categoria_id', $eventoEditar['categoria_id'] ?? 0) === (int)$c['id'];
          ?>
            <label class="cat-pick cursor-pointer">
              <input type="radio" name="categoria_id" value="<?= (int)$c['id'] ?>" class="sr-only peer" <?= $selCat ? 'checked' : '' ?>>
              <span class="cat-pill peer-checked:ring-2 peer-checked:ring-rose-400">
                <span class="text-base"><?= e($c['emoji']) ?></span> <?= e($c['nombre']) ?>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
        <p class="text-xs text-chocolate-500 mt-2">
          ¿No encuentras una? <button type="button" onclick="window.abrirCategorias(); return false;"
                                       class="text-rose-500 font-bold underline">Gestionar categorías</button>
        </p>
      </div>

      <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="btn btn-primary">
          <span>💾</span> <?= $accion === 'crear' ? 'Guardar evento' : 'Actualizar' ?>
        </button>
        <a href="<?= url('agenda.php') ?>" class="btn btn-ghost">Cancelar</a>
      </div>
    </form>
  </section>

<?php else: ?>
  <!-- ============ CALENDARIO ============ -->
  <section class="card mb-8">
    <div id="agenda-calendar" x-data="agendaCalendar()" x-init="init()"></div>
  </section>

  <!-- Modal de detalle de evento -->
  <div x-data="eventoModal()" x-show="open" x-cloak
       @keydown.escape.window="cerrar()"
       class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
       style="display:none;">
    <div @click.outside="cerrar()"
         class="card max-w-md w-full p-6 animate-pop"
         :style="evento ? `background: linear-gradient(135deg, ${bgColor(evento.extendedProps.color)} 0%, #fff 100%)` : ''">
      <template x-if="evento">
        <div>
          <div class="flex items-start justify-between gap-3 mb-4">
            <h3 class="font-sweet text-2xl text-rose-500 flex-1" x-text="evento.title"></h3>
            <button @click="cerrar()" class="w-9 h-9 rounded-full bg-white/70 hover:bg-white flex items-center justify-center text-chocolate-700">✕</button>
          </div>

          <div class="space-y-2 text-sm text-chocolate-800">
            <p><b>📅 Inicio:</b> <span x-text="fechaFmt(evento.start)"></span></p>
            <template x-if="evento.extendedProps.fecha_fin">
              <p><b>🏁 Fin:</b> <span x-text="fechaFmt(evento.extendedProps.fecha_fin)"></span></p>
            </template>
            <template x-if="!evento.allDay && evento.start && !evento.extendedProps.todo_el_dia">
              <p><b>🕐 Hora:</b> <span x-text="horaFmt(evento.start)"></span></p>
            </template>
            <template x-if="evento.allDay || evento.extendedProps.todo_el_dia">
              <p><b>🕐 Todo el día</b></p>
            </template>
            <template x-if="evento.extendedProps.descripcion">
              <p class="mt-3 p-3 bg-white/60 rounded-xl whitespace-pre-line"
                 x-text="evento.extendedProps.descripcion"></p>
            </template>
          </div>

          <div class="flex gap-2 mt-5">
            <a :href="'agenda.php?accion=editar&id=' + evento.extendedProps.db_id"
               class="btn btn-primary flex-1 justify-center">
              <span>✏️</span> Editar
            </a>
            <form method="post" :action="'agenda.php?accion=eliminar&id=' + evento.extendedProps.db_id"
                  x-data="confirmDelete('¿Eliminar este evento?')" class="flex-1">
              <?= csrf_field() ?>
              <button type="submit" @click.prevent="if (confirm('¿Eliminar este evento?')) $event.target.form.submit()"
                      class="btn btn-danger w-full justify-center">
                <span>🗑️</span> Eliminar
              </button>
            </form>
          </div>
        </div>
      </template>
    </div>
  </div>
<?php endif; ?>

<?php clear_old(); ?>

<!-- FullCalendar desde CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('agendaCalendar', () => ({
    cal: null,
    init() {
      const el = document.getElementById('agenda-calendar');
      if (!el || typeof FullCalendar === 'undefined') return;

      this.cal = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        locale: 'es',
        firstDay: 1,
        height: 'auto',
        headerToolbar: {
          left:   'prev,next today',
          center: 'title',
          right:  'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        buttonText: {
          today:  'Hoy',
          month:  'Mes',
          week:   'Semana',
          day:    'Día',
          list:   'Lista'
        },
        events: '<?= url("api/agenda.php") ?>',
        eventClick: (info) => {
          window.dispatchEvent(new CustomEvent('abrir-evento', { detail: info.event }));
        },
        dateClick: (info) => {
          window.location.href = '<?= url("agenda.php?accion=crear") ?>&fecha=' + info.dateStr;
        },
        eventDidMount: (info) => {
          info.el.style.borderRadius = '12px';
          info.el.style.padding = '2px 6px';
          info.el.style.fontWeight = '600';
          info.el.style.boxShadow = '0 4px 10px -2px rgba(255,107,157,.25)';
        },
      });
      this.cal.render();
    }
  }));

  Alpine.data('eventoModal', () => ({
    open: false,
    evento: null,
    init() {
      window.addEventListener('abrir-evento', (e) => {
        this.evento = e.detail;
        this.open = true;
      });
    },
    cerrar() { this.open = false; this.evento = null; },
    fechaFmt(fecha) {
      if (!fecha) return '';
      const d = new Date(fecha);
      return d.toLocaleDateString('es-CL', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
    },
    horaFmt(fecha) {
      if (!fecha) return '';
      const d = new Date(fecha);
      return d.toLocaleTimeString('es-CL', { hour:'2-digit', minute:'2-digit' });
    },
    bgColor(color) {
      const m = {
        rosa: '#ffd6e7',
        crema: '#ffe5b4',
        menta: '#d4f4e2',
        chocolate: '#e8d4bd',
      };
      return m[color] || '#ffd6e7';
    }
  }));
});
</script>

<!-- ============ MODAL DE CATEGORÍAS (compartido con notas.php) ============ -->
<div id="categorias-modal" class="modal-backdrop">
  <div x-data="categoriasModal(<?= htmlspecialchars(json_encode(array_map(fn($c) => [
    'id' => (int)$c['id'], 'emoji' => $c['emoji'], 'nombre' => $c['nombre']
], $categorias)), ENT_QUOTES, 'UTF-8') ?>)"
       @click.outside="window.cerrarCategorias()"
       class="card max-w-lg w-full p-6 animate-pop max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-sweet text-2xl text-rose-500">🏷️ Categorías</h3>
      <button type="button" onclick="window.cerrarCategorias(); return false;" class="w-9 h-9 rounded-full bg-rose-50 hover:bg-rose-100 flex items-center justify-center">✕</button>
    </div>
    <p class="text-sm text-chocolate-500 mb-4">
      Crea etiquetas con emoji para clasificar tus notas.
    </p>

    <form method="post" action="<?= url('notas.php') ?>" class="flex gap-2 mb-5 p-3 bg-rose-50/60 rounded-2xl">
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
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') cerrar();
  });
  if (new URLSearchParams(location.search).get('modal') === 'categorias') {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', abrir);
    } else {
      abrir();
    }
  }
})();
</script>

<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>