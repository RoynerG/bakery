<?php
/**
 * Página: Gestión de Usuarios (solo admin)
 * CRUD de usuarios administradores.
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Usuario;
use App\Auth;

Auth::require();

$accion = $_GET['accion'] ?? 'listar';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$yo     = Auth::user();  // usuario actualmente logueado

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
            redirect('usuarios.php');
        }
        if ($accion === 'editar' && $id > 0) {
            Usuario::update(
                $id,
                $_POST['nombre'] ?? null,
                !empty($_POST['password']) ? $_POST['password'] : null
            );
            // Si el usuario editó su propio nombre, actualizamos la sesión
            if ($yo && (int)$yo['id'] === $id) {
                $_SESSION['auth']['nombre'] = trim($_POST['nombre'] ?? '') ?: null;
            }
            flash('success', '✅ Usuario actualizado.');
            redirect('usuarios.php');
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
            redirect('usuarios.php');
        }
    } catch (Throwable $e) {
        keep_old($_POST);
        flash('error', $e->getMessage());
        redirect('usuarios.php' . ($accion === 'editar' ? '?accion=editar&id=' . $id : '?accion=crear'));
    }
}

// ============ Cargar usuario para edición ============
$usuarioEditar = null;
if ($accion === 'editar' && $id > 0) {
    $usuarioEditar = Usuario::find($id);
    if (!$usuarioEditar) {
        flash('error', 'El usuario no existe.');
        redirect('usuarios.php');
    }
}

$usuarios = Usuario::all();
$titulo   = 'Usuarios';
?>
<?php require_once __DIR__ . '/../src/layout/header.php'; ?>

<!-- HEADER -->
<section class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
  <div>
    <h1 class="section-title">Usuarios</h1>
    <p class="text-chocolate-700 mt-2">Crea y administra las cuentas que pueden entrar al panel. 👤</p>
  </div>
  <a href="<?= url('usuarios.php?accion=crear') ?>" class="btn btn-primary">
    <span>➕</span> Nuevo usuario
  </a>
</section>

<?php if ($accion === 'crear' || $accion === 'editar'): ?>
  <!-- ============ FORMULARIO ============ -->
  <section class="card max-w-2xl mx-auto mb-10">
    <h2 class="font-sweet text-2xl text-rose-500 mb-1">
      <?= $accion === 'crear' ? 'Nuevo usuario' : 'Editar usuario' ?>
    </h2>
    <p class="text-sm text-chocolate-500 mb-6">
      <?= $accion === 'crear'
          ? 'Esta persona podrá iniciar sesión y administrar todo el recetario.'
          : 'Cambia el nombre visible o resetea la contraseña. El usuario no puede eliminarse a sí mismo.' ?>
    </p>

    <form method="post"
          action="<?= url('usuarios.php?accion=' . $accion . ($id ? '&id=' . $id : '')) ?>"
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
            Solo letras, números, guion bajo, punto o guion. Mín. 3 caracteres.
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
               placeholder="Ej. María González">
      </div>

      <div>
        <label class="block text-sm font-bold text-chocolate-700 mb-1">
          <?= $accion === 'crear' ? 'Contraseña *' : 'Nueva contraseña' ?>
          <?php if ($accion === 'editar'): ?>
            <span class="text-chocolate-500 font-normal">(déjala vacía para no cambiar)</span>
          <?php endif; ?>
        </label>
        <input type="password" name="password"
               <?= $accion === 'crear' ? 'required' : '' ?>
               minlength="6"
               class="font-mono"
               placeholder="Mínimo 6 caracteres"
               autocomplete="new-password">
      </div>

      <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="btn btn-primary">
          <span>💾</span> <?= $accion === 'crear' ? 'Crear usuario' : 'Actualizar' ?>
        </button>
        <a href="<?= url('usuarios.php') ?>" class="btn btn-ghost">Cancelar</a>
      </div>
    </form>
  </section>

<?php else: ?>
  <!-- ============ LISTADO ============ -->
  <?php if (empty($usuarios)): ?>
    <div class="card text-center py-16">
      <div class="text-7xl mb-4 animate-wiggle inline-block">👤</div>
      <h3 class="font-sweet text-2xl text-rose-500 mb-2">No hay usuarios</h3>
      <a href="<?= url('usuarios.php?accion=crear') ?>" class="btn btn-primary">
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
                <span class="user-badge-tú">✨ Tú</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="mt-4 pt-3 border-t border-rose-100 text-xs text-chocolate-700 space-y-1">
            <p>📅 Creado: <?= date('d/m/Y', strtotime($u['created_at'])) ?></p>
            <?php if (!empty($u['last_login_at'])): ?>
              <p>🟢 Último login: <?= date('d/m/Y H:i', strtotime($u['last_login_at'])) ?></p>
            <?php else: ?>
              <p>⚪ Nunca ha iniciado sesión</p>
            <?php endif; ?>
          </div>
          <div class="mt-4 flex gap-2">
            <a href="<?= url('usuarios.php?accion=editar&id=' . (int)$u['id']) ?>"
               class="btn btn-secondary flex-1 justify-center !py-2 !text-xs">
              <span>✏️</span> Editar
            </a>
            <?php if (!$soyYo && count($usuarios) > 1): ?>
              <form method="post" action="<?= url('usuarios.php?accion=eliminar&id=' . (int)$u['id']) ?>"
                    x-data="confirmDelete('¿Eliminar al usuario @<?= e($u['usuario']) ?>?\n\nSe perderá el acceso de esta persona.')" class="flex-1">
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

<?php clear_old(); ?>
<?php require_once __DIR__ . '/../src/layout/footer.php'; ?>