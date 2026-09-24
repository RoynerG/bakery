<?php
/**
 * Pagina: Gestion de Usuarios + Configuracion del catalogo (tabs).
 */
declare(strict_types=1);

use App\Models\Usuario;
use App\Auth;

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

Auth::require();

$accion = $_GET['accion'] ?? 'listar';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$yo     = Auth::user();
$tab    = $_GET['tab'] ?? 'users';
if (!in_array($tab, ['users', 'catalogo'], true)) $tab = 'users';

// ============ POST ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        if ($accion === 'crear') {
            Usuario::create(
                $_POST['usuario'] ?? '',
                $_POST['password'] ?? '',
                $_POST['nombre'] ?? null
            );
            flash('success', '👤 Usuario creado.');
            redirect('usuarios.php?tab=users');
        }
        if ($accion === 'editar' && $id > 0) {
            Usuario::update(
                $id,
                $_POST['nombre'] ?? null,
                !empty($_POST['password']) ? $_POST['password'] : null
            );
            if ($yo && (int)$yo['id'] === $id) {
                $_SESSION['auth']['nombre'] = trim($_POST['nombre'] ?? '') ?: null;
            }
            flash('success', '✅ Usuario actualizado.');
            redirect('usuarios.php?tab=users');
        }
        if ($accion === 'eliminar' && $id > 0) {
            if ($yo && (int)$yo['id'] === $id) {
                throw new RuntimeException('No puedes eliminar tu propio usuario.');
            }
            if (Usuario::count() <= 1) {
                throw new RuntimeException('Debe existir al menos un usuario administrador.');
            }
            $ok = Usuario::delete($id);
            flash($ok ? 'success' : 'error', $ok ? '🗑️ Usuario eliminado.' : '😢 No se pudo eliminar.');
            redirect('usuarios.php?tab=users');
        }
        if ($accion === 'guardar_catalogo') {
            try {
                \App\Database::getInstance()->fetchOne('SELECT 1 FROM config LIMIT 1');
            } catch (Throwable $e) {
                throw new RuntimeException(
                    'La tabla `config` aun no existe. Corré la migracion SQL '
                    . '(secciones 16-17 de database/migrate_mysql.sql) en phpMyAdmin '
                    . 'antes de guardar.'
                );
            }
            foreach (['catalogo_tagline', 'catalogo_instagram', 'catalogo_whatsapp', 'catalogo_cover_deco'] as $clave) {
                set_config($clave, $_POST[$clave] ?? '');
            }
            flash('success', '✅ Configuracion del catalogo guardada.');
            redirect('usuarios.php?tab=catalogo');
        }
    } catch (Throwable $e) {
        keep_old($_POST);
        flash('error', $e->getMessage());
        redirect('usuarios.php?tab=' . ($accion === 'guardar_catalogo' ? 'catalogo' : 'users')
            . ($accion === 'editar' ? '&accion=editar&id=' . $id : ''));
    }
}

$usuarioEditar = null;
if ($accion === 'editar' && $id > 0 && $tab === 'users') {
    $usuarioEditar = Usuario::find($id);
    if (!$usuarioEditar) {
        flash('error', 'El usuario no existe.');
        redirect('usuarios.php?tab=users');
    }
}

$usuarios = Usuario::all();
$titulo   = 'Usuarios y configuracion';

// Datos de la pestana de configuracion
$tagline   = get_config('catalogo_tagline', 'PASTELERÍA Y REPOSTERÍA ARTESANAL');
$instagram = get_config('catalogo_instagram', '@dulce.rinconcito');
$whatsapp  = get_config('catalogo_whatsapp', '+56 9 4968 080');
$coverDeco = get_config('catalogo_cover_deco', '🥐 🧁 🍰 🍪 🧁');
?>
<?php require_once __DIR__ . '/../src/layout/header.php'; ?>

<!-- Toda la pagina en un unico scope Alpine para que las tabs funcionen -->
<section x-data="{ tab: '<?= e($tab) ?>' }">

  <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
    <div>
      <h1 class="section-title">Usuarios y configuracion</h1>
      <p class="text-chocolate-700 mt-2">Administra las cuentas del sistema y la portada del catalogo 3D. 👤📖</p>
    </div>
    <a href="<?= url('catalogo.php') ?>" target="_blank"
       class="btn btn-secondary !py-2 !text-xs self-start sm:self-auto">
      📖 Ver libro 3D publico →
    </a>
  </div>

  <!-- Pestanas -->
  <div class="flex gap-1 border-b-2 border-rose-100 mb-6 overflow-x-auto">
    <button type="button" @click="tab = 'users'"
            :class="tab === 'users'
              ? 'border-rose-400 text-rose-500 bg-rose-50/50'
              : 'border-transparent text-chocolate-500 hover:text-rose-400 hover:bg-rose-50/30'"
            class="px-5 py-3 font-bold text-sm border-b-4 -mb-0.5 transition-colors whitespace-nowrap rounded-t-xl">
      👤 Usuarios
      <span class="ml-2 text-xs font-normal opacity-70">(<?= count($usuarios) ?>)</span>
    </button>
    <button type="button" @click="tab = 'catalogo'"
            :class="tab === 'catalogo'
              ? 'border-rose-400 text-rose-500 bg-rose-50/50'
              : 'border-transparent text-chocolate-500 hover:text-rose-400 hover:bg-rose-50/30'"
            class="px-5 py-3 font-bold text-sm border-b-4 -mb-0.5 transition-colors whitespace-nowrap rounded-t-xl">
      📖 Configuracion del catalogo
    </button>
  </div>

  <!-- TAB: USUARIOS -->
  <div x-show="tab === 'users'" x-cloak>
    <?php if ($accion === 'crear' || $accion === 'editar'): ?>
      <section class="card max-w-2xl mx-auto mb-10">
        <h2 class="font-sweet text-2xl text-rose-500 mb-1">
          <?= $accion === 'crear' ? 'Nuevo usuario' : 'Editar usuario' ?>
        </h2>
        <p class="text-sm text-chocolate-500 mb-6">
          <?= $accion === 'crear'
              ? 'Esta persona podra iniciar sesion y administrar todo el recetario.'
              : 'Cambia el nombre visible o resetea la contrasena. No puedes eliminarte a ti mismo.' ?>
        </p>

        <form method="post"
              action="<?= url('usuarios.php?tab=users&accion=' . $accion . ($id ? '&id=' . $id : '')) ?>"
              class="space-y-5" autocomplete="off">
          <?= csrf_field() ?>

          <?php if ($accion === 'crear'): ?>
            <div>
              <label class="block text-sm font-bold text-chocolate-700 mb-1">Usuario *</label>
              <input type="text" name="usuario" required minlength="3" maxlength="60"
                     value="<?= e(old('usuario')) ?>"
                     pattern="[A-Za-z0-9_.\-]+"
                     placeholder="ej. royna, admin2, maria.p"
                     class="font-mono">
              <p class="text-xs text-chocolate-500 mt-1">
                Solo letras, numeros, guion bajo, punto o guion. Min. 3 caracteres.
              </p>
            </div>
          <?php else: ?>
            <div>
              <label class="block text-sm font-bold text-chocolate-700 mb-1">Usuario</label>
              <input type="text" value="<?= e($usuarioEditar['usuario']) ?>" disabled
                     class="font-mono bg-rose-50/40">
            </div>
          <?php endif; ?>

          <div>
            <label class="block text-sm font-bold text-chocolate-700 mb-1">Nombre <span class="text-chocolate-500 font-normal">(opcional)</span></label>
            <input type="text" name="nombre" maxlength="120"
                   value="<?= e(old('nombre', $usuarioEditar['nombre'] ?? '')) ?>"
                   placeholder="Ej. Maria Gonzalez">
          </div>

          <div>
            <label class="block text-sm font-bold text-chocolate-700 mb-1">
              <?= $accion === 'crear' ? 'Contrasena *' : 'Nueva contrasena' ?>
              <?php if ($accion === 'editar'): ?>
                <span class="text-chocolate-500 font-normal">(dejala vacia para no cambiar)</span>
              <?php endif; ?>
            </label>
            <input type="password" name="password"
                   <?= $accion === 'crear' ? 'required' : '' ?>
                   minlength="6"
                   class="font-mono"
                   placeholder="Minimo 6 caracteres"
                   autocomplete="new-password">
          </div>

          <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" class="btn btn-primary">
              <span>💾</span> <?= $accion === 'crear' ? 'Crear usuario' : 'Actualizar' ?>
            </button>
            <a href="<?= url('usuarios.php?tab=users') ?>" class="btn btn-ghost">Cancelar</a>
          </div>
        </form>
      </section>
    <?php else: ?>
      <div class="flex justify-end mb-4">
        <a href="<?= url('usuarios.php?tab=users&accion=crear') ?>" class="btn btn-primary">
          <span>➕</span> Nuevo usuario
        </a>
      </div>

      <?php if (empty($usuarios)): ?>
        <div class="card text-center py-16">
          <div class="text-7xl mb-4 animate-wiggle inline-block">👤</div>
          <h3 class="font-sweet text-2xl text-rose-500 mb-2">No hay usuarios</h3>
          <a href="<?= url('usuarios.php?tab=users&accion=crear') ?>" class="btn btn-primary">
            <span>➕</span> Crear el primero
          </a>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <?php foreach ($usuarios as $u):
            $soyYo = $yo && (int)$yo['id'] === (int)$u['id'];
            $inicial = mb_substr($u['nombre'] ?: $u['usuario'], 0, 1);
          ?>
            <article class="user-card">
              <div class="flex items-start gap-4">
                <div class="user-avatar"><?= e(mb_strtoupper($inicial)) ?></div>
                <div class="flex-1 min-w-0">
                  <h3 class="font-bold text-lg text-chocolate-900 truncate">
                    <?= e($u['nombre'] ?: $u['usuario']) ?>
                  </h3>
                  <p class="text-sm text-chocolate-700 font-mono truncate">@<?= e($u['usuario']) ?></p>
                  <?php if ($soyYo): ?>
                    <span class="user-badge-tú">✨ Tu</span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="mt-4 pt-3 border-t border-rose-100 text-xs text-chocolate-700 space-y-1">
                <p>📅 Creado: <?= date('d/m/Y', strtotime($u['created_at'])) ?></p>
                <?php if (!empty($u['last_login_at'])): ?>
                  <p>🟢 Ultimo login: <?= date('d/m/Y H:i', strtotime($u['last_login_at'])) ?></p>
                <?php else: ?>
                  <p>⚪ Nunca ha iniciado sesion</p>
                <?php endif; ?>
              </div>
              <div class="mt-4 flex gap-2">
                <a href="<?= url('usuarios.php?tab=users&accion=editar&id=' . (int)$u['id']) ?>"
                   class="btn btn-secondary flex-1 justify-center !py-2 !text-xs">
                  <span>✏️</span> Editar
                </a>
                <?php if (!$soyYo && count($usuarios) > 1): ?>
                  <form method="post" action="<?= url('usuarios.php?tab=users&accion=eliminar&id=' . (int)$u['id']) ?>"
                        x-data="confirmDelete('¿Eliminar al usuario @<?= e($u['usuario']) ?>?\n\nSe perdera el acceso de esta persona.')" class="flex-1">
                    <?= csrf_field() ?>
                    <button type="submit" @click.prevent="ask(() => $event.target.form.submit())"
                            class="btn btn-danger w-full justify-center !py-2 !text-xs">
                      <span>🗑️</span> Eliminar
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- TAB: CONFIGURACION DEL CATALOGO -->
  <div x-show="tab === 'catalogo'" x-cloak>
    <p class="text-sm text-chocolate-500 mb-4 max-w-2xl">
      Estos valores se muestran en la portada y galeria del libro 3D
      publico. Cambialos aca sin tocar codigo.
    </p>

    <form method="post" action="<?= url('usuarios.php?tab=catalogo&accion=guardar_catalogo') ?>"
          class="card max-w-2xl space-y-5">
      <?= csrf_field() ?>

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">Tagline de la portada</label>
        <input type="text" name="catalogo_tagline" maxlength="120"
               value="<?= e($tagline) ?>"
               placeholder="PASTELERIA Y REPOSTERIA ARTESANAL">
        <p class="text-xs text-chocolate-500 mt-1">Texto debajo del titulo en la portada.</p>
      </div>

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">Instagram / Handle</label>
        <input type="text" name="catalogo_instagram" maxlength="60"
               value="<?= e($instagram) ?>"
               placeholder="@dulce.rinconcito">
        <p class="text-xs text-chocolate-500 mt-1">Aparece en portada y galeria.</p>
      </div>

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">WhatsApp / Telefono</label>
        <input type="text" name="catalogo_whatsapp" maxlength="60"
               value="<?= e($whatsapp) ?>"
               placeholder="+56 9 4968 080">
        <p class="text-xs text-chocolate-500 mt-1">Aparece en la galeria de fotos final.</p>
      </div>

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">Emojis decorativos de la portada</label>
        <input type="text" name="catalogo_cover_deco" maxlength="80"
               value="<?= e($coverDeco) ?>"
               placeholder="🥐 🧁 🍰 🍪 🧁">
        <p class="text-xs text-chocolate-500 mt-1">Emojis que aparecen en la portada del libro.</p>
      </div>

      <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="btn btn-primary">
          <span>💾</span> Guardar configuracion
        </button>
      </div>
    </form>
  </div>

</section>

<?php clear_old(); ?>
<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>
