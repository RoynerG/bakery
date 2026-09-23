/* ============================================
   Repostería - JavaScript de la aplicación
   Define componentes globales de Alpine.js
   ============================================ */

document.addEventListener('alpine:init', () => {

  /* ============================================================
     Componente: recipeWizard (formulario de receta paso a paso)
     ============================================================ */
  Alpine.data('recipeWizard', (initial = {}) => ({
    // ---------- Estado general ----------
    step: 1,
    totalSteps: 5,
    submitting: false,
    progress: 0,
    loadingMsg: '',

    // ---------- Datos de la receta ----------
    nombre:        initial.nombre        ?? '',
    descripcion:   initial.descripcion   ?? '',
    porciones:     initial.porciones     ?? 8,
    pasos: [],

    init() {
      // Convierte el string inicial de instrucciones (DB) en un array de pasos
      const raw = (initial.instrucciones ?? '').toString();
      const lines = raw.split(/\r?\n/)
                       .map(l => l.replace(/^\s*\d+[.)]\s*/, '').trim())
                       .filter(l => l.length > 0);
      this.pasos = lines.map(t => ({ texto: t }));
    },

    // ---------- Ingredientes ----------
    ingredientes: initial.ingredientes ?? [],
    items: initial.items ?? [],

    // ---------- Imagen ----------
    imagenFile: null,
    imagenPreview: initial.imagenActual ?? null,

    // ---------- Reporte de costos ----------
    iva: 0,
    margen: 60,
    otrosCostos: 0,

    // ---------- Mensajes de carga pasteleros ----------
    loadMsgs: [
      '🧁 Precalentando el horno...',
      '🥣 Mezclando los ingredientes...',
      '🔥 Horneando a 180°C...',
      '🎨 Decorando con frosting...',
      '✨ Dando el toque final...',
      '🍰 ¡Lista tu delicia!',
    ],

    get steps() {
      return [
        { n: 1, icon: '📝', label: 'Básicos' },
        { n: 2, icon: '🥣', label: 'Ingredientes' },
        { n: 3, icon: '👩‍🍳', label: 'Preparación' },
        { n: 4, icon: '📸', label: 'Foto' },
        { n: 5, icon: '💰', label: 'Reporte' },
      ];
    },
    get progressPct() {
      return Math.round(((this.step - 1) / (this.totalSteps - 1)) * 100);
    },

    get step1Valid() { return this.nombre.trim().length >= 2 && parseInt(this.porciones) > 0; },
    get step2Valid() { return this.items.length > 0 && this.items.every(i => i.ingrediente_id && parseFloat(i.cantidad) > 0); },
    get step3Valid() { return this.pasos.length > 0 && this.pasos.some(p => p.texto.trim().length >= 3); },

    addPaso() {
      this.pasos.push({ texto: '' });
      // Enfoca el nuevo input en el siguiente tick
      this.$nextTick(() => {
        const inputs = document.querySelectorAll('.paso-input');
        const last = inputs[inputs.length - 1];
        if (last) last.focus();
      });
    },
    removePaso(i) { this.pasos.splice(i, 1); },
    movePasoUp(i) {
      if (i <= 0) return;
      [this.pasos[i - 1], this.pasos[i]] = [this.pasos[i], this.pasos[i - 1]];
    },
    movePasoDown(i) {
      if (i >= this.pasos.length - 1) return;
      [this.pasos[i + 1], this.pasos[i]] = [this.pasos[i], this.pasos[i + 1]];
    },
    get step4Valid() { return true; },
    get step5Valid() { return true; },

    canProceed() {
      return [this.step1Valid, this.step2Valid, this.step3Valid, this.step4Valid, this.step5Valid][this.step - 1];
    },

    next() {
      if (!this.canProceed()) { this.warnStep(); return; }
      if (this.step < this.totalSteps) { this.step++; window.scrollTo({ top: 0, behavior: 'smooth' }); }
    },
    prev() {
      if (this.step > 1) { this.step--; window.scrollTo({ top: 0, behavior: 'smooth' }); }
    },
    goTo(n) {
      if (n <= this.step) { this.step = n; return; }
      for (let i = this.step; i < n; i++) {
        if (![this.step1Valid, this.step2Valid, this.step3Valid, this.step4Valid, this.step5Valid][i - 1]) {
          this.step = i; this.warnStep(); return;
        }
      }
      this.step = n;
    },
    warnStep() {
      const msgs = {
        1: 'Necesitas un nombre (mín. 2 letras) y al menos 1 porción. 🧁',
        2: 'Agrega al menos un ingrediente con cantidad mayor a 0. 🥣',
        3: 'Agrega al menos un paso de preparación con texto. 👩‍🍳',
      };
      alert(msgs[this.step] || 'Revisa los datos del paso actual.');
    },

    addItem() { this.items.push({ ingrediente_id: '', cantidad: 0, unidad: '', costo_unitario: 0, subtotal: 0 }); },
    removeItem(i) { this.items.splice(i, 1); },
    onIngChange(i) {
      const it = this.items[i];
      const ing = this.ingredientes.find(x => x.id == it.ingrediente_id);
      if (ing) { it.unidad = ing.unidad_medida; it.costo_unitario = parseFloat(ing.costo_base) || 0; }
      else     { it.unidad = ''; it.costo_unitario = 0; }
      this.calcRow(i);
    },
    calcRow(i) {
      const it = this.items[i];
      const ing = this.ingredientes.find(x => x.id == it.ingrediente_id);
      if (!ing) { it.subtotal = 0; return; }
      const cant = parseFloat(it.cantidad) || 0;
      const factor = (ing.unidad_medida === 'gramo' || ing.unidad_medida === 'mililitro') ? 0.001 : 1;
      it.subtotal = Math.round(parseFloat(ing.costo_base) * cant * factor * 10000) / 10000;
    },
    get costoIngredientes() { return this.items.reduce((a, i) => a + (parseFloat(i.subtotal) || 0), 0); },
    get costoOtros()       { return parseFloat(this.otrosCostos) || 0; },
    get subtotal()         { return this.costoIngredientes + this.costoOtros; },
    get ivaMonto()         { return this.subtotal * (parseFloat(this.iva) || 0) / 100; },
    get costoTotal()       { return this.subtotal + this.ivaMonto; },
    get costoPorcion()     { return this.costoTotal / Math.max(1, parseInt(this.porciones) || 1); },
    get ganancia()         { return this.costoTotal * (parseFloat(this.margen) || 0) / 100; },
    get precioVenta()      { return this.costoTotal + this.ganancia; },
    get precioPorcion()    { return this.precioVenta / Math.max(1, parseInt(this.porciones) || 1); },

    handleImage(event) {
      const f = event.target.files[0];
      if (!f) return;
      this.imagenFile = f;
      const reader = new FileReader();
      reader.onload = e => { this.imagenPreview = e.target.result; };
      reader.readAsDataURL(f);
    },
    clearImage() {
      this.imagenFile = null;
      this.imagenPreview = null;
      const inp = document.getElementById('imagen-input');
      if (inp) inp.value = '';
    },

    money(v) { const n = parseFloat(v) || 0; return '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    num(v, d = 3) { const n = parseFloat(v) || 0; return n.toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: d }); },
    cantUnidad(unidad, cant) {
      const c = parseFloat(cant) || 0;
      const str = (Math.round(c * 1000) / 1000).toString().replace(/\.?0+$/, '');
      const plur = Math.abs(c) !== 1 ? 's' : '';
      return str + ' ' + unidad + plur;
    },

    get listaPasos() {
      return this.pasos
        .map(p => p.texto.trim())
        .filter(t => t.length > 0);
    },

    get itemsJson() {
      return JSON.stringify(this.items.map(i => ({
        ingrediente_id: parseInt(i.ingrediente_id) || 0,
        cantidad:       parseFloat(i.cantidad) || 0,
      })));
    },

    async submit(form) {
      if (!this.step1Valid || !this.step2Valid || !this.step3Valid) {
        this.step = !this.step1Valid ? 1 : (!this.step2Valid ? 2 : 3);
        this.warnStep();
        return;
      }
      this.submitting = true;
      for (let i = 0; i < this.loadMsgs.length; i++) {
        this.loadingMsg = this.loadMsgs[i];
        this.progress   = Math.round(((i + 1) / this.loadMsgs.length) * 100);
        await new Promise(r => setTimeout(r, 550));
      }
      form.submit();
    },
  }));

  Alpine.data('confirmDelete', (message = '¿Estás seguro?') => ({
    message,
    ask(callback) { if (window.confirm(this.message)) callback(); },
  }));

  Alpine.data('toggleMenu', () => ({ open: false }));
});
