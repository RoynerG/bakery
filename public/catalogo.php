<?php
/**
 * Catalogo publico de productos.
 * Pagina SIN autenticacion: accesible a clientes.
 * Renderiza un libro 3D con Three.js (CSS3DRenderer).
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Models\Producto;

$destacados = Producto::byTipo('destacado');
$clasicas   = Producto::byTipo('clasica');
$premiums   = Producto::byTipo('premium');
$extras     = Producto::byTipo('extra');
$galeria    = Producto::byTipo('galeria');

$titulo = 'Catalogo';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Catalogo · <?= e(APP_NAME) ?></title>
  <meta name="description" content="Catalogo de productos de <?= e(APP_NAME) ?>: tortas clasicas, premium, kuchen, pie de limon y mas.">

  <link rel="stylesheet" href="<?= asset('css/styles.css') ?>?v=5">
  <link rel="stylesheet" href="<?= asset('css/catalogo.css') ?>?v=4">
  <link rel="icon" type="image/jpeg" href="<?= asset('img/logo.jpg') ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&family=Pacifico&display=swap" rel="stylesheet">

  <script type="importmap">
  {
    "imports": {
      "three": "<?= asset('js/three.module.js') ?>",
      "three/addons/": "<?= asset('js/three/addons/') ?>"
    }
  }
  </script>

  <style>
    body {
      background: linear-gradient(135deg, #fff5f0 0%, #ffe9d6 50%, #f7e0c5 100%);
      min-height: 100vh;
      overflow: hidden;
    }
  </style>
</head>
<body>

  <!-- Lienzo donde Three.js dibuja el libro -->
  <div id="book-scene"></div>

  <!-- HTML real de cada pagina (lo posiciona Three.js via CSS3DRenderer) -->
  <template id="tpl-page-cover">
    <div class="page page-cover" data-clickable="cover">
      <div class="page-inner">
        <div class="cover-bg"></div>
        <div class="cover-deco"><?= e(get_config('catalogo_cover_deco', '🥐 🧁 🍰 🍪 🧁')) ?></div>
        <h1 class="cover-title"><span class="cover-sweet"><?= e(APP_NAME) ?></span></h1>
        <p class="cover-cat">CATÁLOGO</p>
        <p class="cover-tag"><?= e(get_config('catalogo_tagline', 'PASTELERÍA Y REPOSTERÍA ARTESANAL')) ?></p>
        <p class="cover-ig"><?= e(get_config('catalogo_instagram', '@dulce.rinconcito')) ?></p>
        <p class="cover-hint">Tocá la portada para abrir →</p>
      </div>
    </div>
  </template>

  <template id="tpl-page-blank">
    <div class="page page-blank"></div>
  </template>

  <template id="tpl-page-destacados">
    <div class="page page-content">
      <div class="page-inner">
        <header class="page-header">
          <span class="page-emoji">🍰</span>
          <h2 class="page-title">Destacados</h2>
        </header>
        <div class="destacados-list">
          <?php foreach ($destacados as $p): ?>
            <article class="destacado">
              <?php if (!empty($p['imagen'])): ?>
                <div class="destacado-img">
                  <img src="<?= upload_url($p['imagen']) ?>" alt="<?= e($p['nombre']) ?>" loading="lazy">
                </div>
              <?php endif; ?>
              <div class="destacado-body">
                <h3 class="destacado-name"><?= e(mb_strtoupper($p['nombre'])) ?></h3>
                <?php if (!empty($p['descripcion'])): ?>
                  <p class="destacado-desc"><?= nl2br(e($p['descripcion'])) ?></p>
                <?php endif; ?>
                <?php $vars = Producto::variantes((int)$p['id']); if (!empty($vars)): ?>
                  <table class="variantes">
                    <tbody>
                      <?php foreach ($vars as $v): ?>
                        <tr>
                          <td class="var-label"><?= e($v['label']) ?></td>
                          <td class="var-price"><?= format_money((float)$v['precio']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
          <?php if (empty($destacados)): ?>
            <p class="empty-msg">Pronto publicaremos nuestros destacados ✨</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </template>

  <template id="tpl-page-clasicas">
    <div class="page page-content">
      <div class="page-inner">
        <h2 class="page-title big">TORTAS CLÁSICAS</h2>
        <p class="page-lead">
          Capas de esponjoso bizcocho blanco intercaladas con tres o cuatro
          capas de relleno a elección, logrando una torta abundante,
          equilibrada y llena de sabor.
        </p>
        <?php
          // Agrupar clasicas por sabor (agrupamos por el nombre antes del primer espacio o palabra)
          // Por simplicidad, las mostramos todas juntas con un separador
        ?>
        <?php foreach ($clasicas as $p): ?>
          <section class="clasica catalog-item">
            <?php if (!empty($p['imagen'])): ?>
              <img class="catalog-item-img" src="<?= upload_url($p['imagen']) ?>" alt="<?= e($p['nombre']) ?>" loading="lazy">
            <?php endif; ?>
            <div class="catalog-item-body">
              <h3 class="clasica-name"><?= e($p['nombre']) ?></h3>
              <p class="clasica-rel"><b>Relleno:</b></p>
              <?php if (!empty($p['descripcion'])): ?>
                <p class="clasica-desc"><?= nl2br(e($p['descripcion'])) ?></p>
              <?php endif; ?>
            </div>
          </section>
        <?php endforeach; ?>
        <?php if (!empty($clasicas)): ?>
          <p class="clasica-nota">Cobertura crema o merengue</p>
          <?php $tamanosCat = categoria_variantes('clasica'); if (!empty($tamanosCat)): ?>
            <table class="size-table">
              <thead><tr><th>Tamaño</th><th>Valor</th></tr></thead>
              <tbody>
                <?php foreach ($tamanosCat as $t): ?>
                  <tr><td class="size-label"><?= e($t['label']) ?></td><td class="size-price"><?= format_money((float)$t['precio']) ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </template>

  <template id="tpl-page-premium">
    <div class="page page-content">
      <div class="page-inner">
        <h2 class="page-title big">TORTAS PREMIUM</h2>
        <p class="page-lead">
          Preparadas con bizcochos artesanales y tres o cuatro capas de
          relleno, nuestras especialidades combinan recetas tradicionales
          y sabores que nunca pasan de moda.
        </p>
        <p class="clasica-nota">Cobertura de crema o merengue.</p>
        <?php foreach ($premiums as $p): ?>
          <section class="clasica catalog-item">
            <?php if (!empty($p['imagen'])): ?>
              <img class="catalog-item-img" src="<?= upload_url($p['imagen']) ?>" alt="<?= e($p['nombre']) ?>" loading="lazy">
            <?php endif; ?>
            <div class="catalog-item-body">
              <h3 class="clasica-name"><?= e($p['nombre']) ?></h3>
              <?php if (!empty($p['descripcion'])): ?>
                <p class="clasica-desc"><?= nl2br(e($p['descripcion'])) ?></p>
              <?php endif; ?>
            </div>
          </section>
        <?php endforeach; ?>
        <?php if (!empty($premiums)): ?>
          <?php $tamanosCat = categoria_variantes('premium'); if (!empty($tamanosCat)): ?>
            <table class="size-table">
              <thead><tr><th>Tamaño</th><th>Valor</th></tr></thead>
              <tbody>
                <?php foreach ($tamanosCat as $t): ?>
                  <tr><td class="size-label"><?= e($t['label']) ?></td><td class="size-price"><?= format_money((float)$t['precio']) ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </template>

  <template id="tpl-page-extras">
    <div class="page page-content">
      <div class="page-inner">
        <h2 class="page-title big">EXTRAS</h2>
        <?php if (!empty($extras)): ?>
          <table class="extras-table">
            <tbody>
              <?php foreach ($extras as $p):
                $vars = Producto::variantes((int)$p['id']);
                $nombre = mb_strtoupper($p['nombre']);
              ?>
                <?php if (!empty($vars)): ?>
                  <tr>
                    <td class="extra-name" rowspan="<?= count($vars) ?>"><?= e($nombre) ?></td>
                    <?php $first = array_shift($vars); ?>
                    <td class="extra-label"><?= e($first['label']) ?></td>
                    <td class="extra-plus">+</td>
                    <td class="extra-price"><?= format_money((float)$first['precio']) ?></td>
                  </tr>
                  <?php foreach ($vars as $v): ?>
                    <tr>
                      <td class="extra-label"><?= e($v['label']) ?></td>
                      <td class="extra-plus">+</td>
                      <td class="extra-price"><?= format_money((float)$v['precio']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td class="extra-name"><?= e($nombre) ?></td>
                    <td class="extra-label">—</td>
                    <td class="extra-plus">+</td>
                    <td class="extra-price"><?= !empty($p['precio_desde']) ? format_money((float)$p['precio_desde']) : 'Consultar' ?></td>
                  </tr>
                <?php endif; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="empty-msg">Pronto publicaremos nuestros extras ✨</p>
        <?php endif; ?>
      </div>
    </div>
  </template>

  <template id="tpl-page-galeria">
    <div class="page page-content">
      <div class="page-inner">
        <div class="galeria-grid">
          <?php if (!empty($galeria)): ?>
            <?php foreach ($galeria as $g): ?>
              <?php if (!empty($g['imagen'])): ?>
                <figure class="galeria-fig">
                  <img src="<?= upload_url($g['imagen']) ?>" alt="<?= e($g['nombre']) ?>" loading="lazy">
                </figure>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="empty-msg">Pronto subiremos más fotos 📸</p>
          <?php endif; ?>
        </div>
        <footer class="galeria-foot">
          <span>📷 <?= e(get_config('catalogo_instagram', '@dulce.rinconcito')) ?></span>
          <span>📱 <?= e(get_config('catalogo_whatsapp', '+56 9 4968 080')) ?></span>
        </footer>
      </div>
    </div>
  </template>

  <!-- Controles del libro -->
  <div class="book-controls">
    <button id="book-prev" class="book-btn" aria-label="Página anterior">‹</button>
    <span id="book-counter" class="book-counter">1 / 1</span>
    <button id="book-next" class="book-btn" aria-label="Página siguiente">›</button>
  </div>

  <a href="<?= url('index.php') ?>" class="book-exit" title="Volver al sistema">← Sistema</a>

  <script type="module" src="<?= asset('js/catalogo.js') ?>?v=4"></script>
</body>
</html>
