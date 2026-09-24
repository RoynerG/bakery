/* ============================================
   Catalogo publico - Libro 3D con Three.js CSS3DRenderer
   ============================================ */
import * as THREE from 'three';
import { CSS3DRenderer, CSS3DObject } from 'three/addons/renderers/CSS3DRenderer.js';

class CatalogoBook {
  constructor() {
    this.container = document.getElementById('book-scene');
    if (!this.container) {
      console.error('No se encontró #book-scene');
      return;
    }

    this.PAGE_W = 600;
    this.PAGE_H = 800;
    this.flipped = 0;
    this.animating = false;

    this.scene = new THREE.Scene();
    this.camera = new THREE.PerspectiveCamera(36, window.innerWidth / window.innerHeight, 1, 10000);
    this.camera.position.set(0, 0, 1500);

    this.renderer = new CSS3DRenderer();
    this.renderer.setSize(window.innerWidth, window.innerHeight);
    this.renderer.domElement.style.position = 'absolute';
    this.renderer.domElement.style.top = '0';
    this.container.appendChild(this.renderer.domElement);

    this.book = new THREE.Group();
    this.scene.add(this.book);

    this.leaves = [];
    this.init();
  }

  init() {
    const pageNames = ['cover', 'destacados', 'clasicas', 'premium', 'extras', 'galeria'];
    pageNames.forEach((name, i) => {
      const tpl = document.getElementById('tpl-page-' + name);
      if (!tpl) return;
      const el = document.createElement('div');
      el.className = 'book-leaf page page-' + name;
      el.style.width = this.PAGE_W + 'px';
      el.style.height = this.PAGE_H + 'px';
      el.appendChild(tpl.content.cloneNode(true));
      const obj = new CSS3DObject(el);
      obj.userData = { name, index: i };
      this.book.add(obj);
      this.leaves.push(obj);
    });

    this.layout();
    this.bindEvents();
    this.updateCounter();
    this.tick();
  }

  layout() {
    // Cada hoja: borde izquierdo en x=0 (lomo), rotacion en Y alrededor del lomo.
    // El book group se centra para que el lomo caiga al medio de la pantalla.
    const w = this.PAGE_W;
    this.leaves.forEach((leaf, i) => {
      leaf.element.style.width = this.PAGE_W + 'px';
      leaf.element.style.height = this.PAGE_H + 'px';
      leaf.position.set(w / 2, 0, -i * 0.5); // z-offset para evitar z-fighting
      leaf.rotation.set(0, 0, 0);
    });
    // Centrar el libro en pantalla: el lomo queda en x=0,
    // movemos el libro para que el centro de la pagina visible quede al medio.
    this.book.position.set(-w / 2, 0, 0);
  }

  flipNext() {
    if (this.animating || this.flipped >= this.leaves.length - 1) return;
    const leaf = this.leaves[this.flipped];
    this.animating = true;
    this.animate(leaf, 0, -Math.PI, () => {
      this.flipped++;
      this.animating = false;
      this.updateCounter();
    });
  }

  flipPrev() {
    if (this.animating || this.flipped <= 0) return;
    this.flipped--;
    const leaf = this.leaves[this.flipped];
    this.animating = true;
    this.animate(leaf, -Math.PI, 0, () => {
      this.animating = false;
      this.updateCounter();
    });
  }

  animate(obj, from, to, done) {
    const duration = 950;
    const start = performance.now();
    const tick = (now) => {
      const t = Math.min(1, (now - start) / duration);
      const eased = t < 0.5
        ? 2 * t * t
        : 1 - Math.pow(-2 * t + 2, 2) / 2;
      obj.rotation.y = from + (to - from) * eased;
      if (t < 1) {
        requestAnimationFrame(tick);
      } else if (done) {
        done();
      }
    };
    requestAnimationFrame(tick);
  }

  updateCounter() {
    const counter = document.getElementById('book-counter');
    const prev = document.getElementById('book-prev');
    const next = document.getElementById('book-next');
    const total = this.leaves.length;
    const current = this.flipped + 1;
    if (counter) counter.textContent = current + ' / ' + total;
    if (prev) prev.disabled = this.flipped <= 0;
    if (next) next.disabled = this.flipped >= total - 1;
  }

  bindEvents() {
    document.getElementById('book-next')?.addEventListener('click', () => this.flipNext());
    document.getElementById('book-prev')?.addEventListener('click', () => this.flipPrev());

    window.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') this.flipNext();
      if (e.key === 'ArrowLeft') this.flipPrev();
    });

    window.addEventListener('resize', () => {
      this.camera.aspect = window.innerWidth / window.innerHeight;
      this.camera.updateProjectionMatrix();
      this.renderer.setSize(window.innerWidth, window.innerHeight);
      this.adjustForViewport();
      this.updateShadow();
    });

    let startX = 0;
    this.container.addEventListener('touchstart', (e) => {
      startX = e.touches[0].clientX;
    }, { passive: true });
    this.container.addEventListener('touchend', (e) => {
      const dx = e.changedTouches[0].clientX - startX;
      if (Math.abs(dx) > 60) {
        if (dx < 0) this.flipNext();
        else this.flipPrev();
      }
    }, { passive: true });

    this.adjustForViewport();
    this.installCoverClick();
    this.installShadow();
  }

  installCoverClick() {
    // Click en la portada abre el libro (solo si estamos en la primera hoja)
    const cover = this.leaves[0]?.element;
    if (!cover) return;
    cover.addEventListener('click', () => {
      if (this.flipped === 0) this.flipNext();
    });
  }

  installShadow() {
    // Sombra realista bajo el libro (HTML plano, gradiente radial)
    const shadow = document.createElement('div');
    shadow.className = 'book-shadow';
    this.container.appendChild(shadow);
    this.shadowEl = shadow;
    this.updateShadow();
  }

  updateShadow() {
    if (!this.shadowEl) return;
    // Posicionar la sombra bajo el libro visible
    const w = this.PAGE_W;
    const h = this.PAGE_H;
    this.shadowEl.style.width = w * 0.95 + 'px';
    this.shadowEl.style.height = (h * 0.18) + 'px';
  }

  adjustForViewport() {
    // En pantallas chicas, reducir tamano de las paginas via CSS.
    if (window.innerWidth < 720) {
      this.PAGE_W = Math.min(window.innerWidth * 0.9, 400);
      this.PAGE_H = this.PAGE_W * 1.33;
      // Re-layout con nuevo tamano
      this.layout();
    } else {
      this.PAGE_W = 600;
      this.PAGE_H = 800;
      this.layout();
    }
  }

  tick() {
    requestAnimationFrame(() => this.tick());
    this.renderer.render(this.scene, this.camera);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  try {
    new CatalogoBook();
  } catch (e) {
    console.error('Error iniciando el catalogo:', e);
  }
});
