<?php
/**
 * Admin: configuracion del catalogo publico.
 * Edita tagline, instagram, whatsapp y emojis de la portada.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/helpers.php';

use App\Auth;
use App\Database;

Auth::require();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        foreach (['catalogo_tagline', 'catalogo_instagram', 'catalogo_whatsapp', 'catalogo_cover_deco'] as $clave) {
            set_config($clave, $_POST[$clave] ?? '');
        }
        flash('success', '✅ Configuracion del catalogo guardada.');
        redirect('configuracion.php');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect('configuracion.php');
    }
}

$titulo = 'Configuracion del catalogo';
$tagline   = get_config('catalogo_tagline', '');
$instagram = get_config('catalogo_instagram', '');
$whatsapp  = get_config('catalogo_whatsapp', '');
$coverDeco = get_config('catalogo_cover_deco', '');
?>
<?php require_once __DIR__ . '/../../src/layout/header.php'; ?>

<section class="mb-8">
  <a href="<?= url('productos.php') ?>" class="text-chocolate-500 hover:text-rose-500 text-sm">← Volver a productos</a>
  <h1 class="section-title mt-2">Configuracion del catalogo</h1>
  <p class="text-chocolate-700 mt-2">
    Estos valores se muestran en la portada y galeria del
    <a href="<?= url('catalogo.php') ?>" target="_blank" class="text-rose-500 hover:underline">libro 3D</a>.
  </p>
</section>

<form method="post" class="card max-w-2xl space-y-5">
  <?= csrf_field() ?>

  <div>
    <label class="block text-sm font-bold text-chocolate-700 mb-1">Tagline de la portada</label>
    <input type="text" name="catalogo_tagline" maxlength="120"
           value="<?= e($tagline) ?>"
           placeholder="PASTELERÍA Y REPOSTERÍA ARTESANAL">
    <p class="text-xs text-chocolate-500 mt-1">Texto que aparece debajo del titulo en la portada.</p>
  </div>

  <div>
    <label class="block text-sm font-bold text-chocolate-700 mb-1">Instagram / Handle</label>
    <input type="text" name="catalogo_instagram" maxlength="60"
           value="<?= e($instagram) ?>"
           placeholder="@dulce.rinconcito">
    <p class="text-xs text-chocolate-500 mt-1">Se muestra en portada y galeria.</p>
  </div>

  <div>
    <label class="block text-sm font-bold text-chocolate-700 mb-1">WhatsApp / Telefono</label>
    <input type="text" name="catalogo_whatsapp" maxlength="60"
           value="<?= e($whatsapp) ?>"
           placeholder="+56 9 4968 080">
    <p class="text-xs text-chocolate-500 mt-1">Se muestra en la galeria de fotos final.</p>
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
    <a href="<?= url('catalogo.php') ?>" target="_blank" class="btn btn-ghost">
      Ver catalogo →
    </a>
  </div>
</form>

<?php require_once __DIR__ . '/../../src/layout/footer.php'; ?>
