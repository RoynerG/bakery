/* ============================================
   Reposteria - JavaScript de la aplicacion
   Componentes globales de Alpine.js
   ============================================ */

document.addEventListener('alpine:init', () => {

  // Las categorias ahora se gestionan en /categorias.php (no modal).
  // Solo necesitamos Alpine.data para componentes que usan x-data.

  // ----- confirmDelete -----
  Alpine.data('confirmDelete', (message = 'Estas seguro?') => ({
    message,
    ask(callback) {
      if (window.confirm(this.message)) callback();
    }
  }));

  // ----- toggleMenu -----
  Alpine.data('toggleMenu', () => ({ open: false }));

  // ----- calcCostoBase (preview en vivo del formulario de inventario) -----
  Alpine.data('calcCostoBase', (initial) => ({
    cantidad:   (initial && initial.cantidad != null) ? initial.cantidad : 1,
    unidad:     (initial && initial.unidad)   || 'gramo',
    precio:     (initial && initial.precio != null) ? initial.precio : 0,
    precioFmt:  '',

    init() {
      this.precioFmt = this.fmtCLP(this.precio);
    },

    factor(unidad) {
      return (unidad === 'gramo' || unidad === 'mililitro') ? 0.001 : 1;
    },
    unidadBase() {
      return ['kilo', 'litro', 'pieza'].indexOf(this.unidad) >= 0
        ? this.unidad
        : (this.unidad === 'gramo' ? 'kilo' : 'litro');
    },
    unidadBaseLabel() {
      const map = { kilo: 'kg', litro: 'L', pieza: 'pieza' };
      return map[this.unidadBase()] || this.unidadBase();
    },

    /** Formatea un numero entero con separador de miles chileno (punto). */
    fmtCLP(v) {
      var n = parseFloat(v) || 0;
      if (n <= 0) return '';
      return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },

    /** Lee lo tipeado en el input, limpia todo lo no numerico,
        actualiza precio y reescribe el input formateado. */
    onPrecioInput(event) {
      var raw = (event.target.value || '').toString().replace(/\D/g, '');
      this.precio = raw ? parseFloat(raw) : 0;
      this.precioFmt = this.fmtCLP(this.precio);
      event.target.value = this.precioFmt;
    },

    get costoBase() {
      var c = parseFloat(this.cantidad) || 0;
      var p = parseFloat(this.precio) || 0;
      var enUnidadBase = c * this.factor(this.unidad);
      if (enUnidadBase <= 0) return 0;
      return Math.round((p / enUnidadBase) * 10000) / 10000;
    },
    costoBasePorUnidadCompra() {
      var c = parseFloat(this.cantidad) || 0;
      var p = parseFloat(this.precio) || 0;
      if (c <= 0) return 0;
      return Math.round((p / c) * 100) / 100;
    },
    money(v) {
      var n = Math.round((parseFloat(v) || 0));
      return '$' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
  }));

  // ----- agendaCalendar (FullCalendar) -----
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
          today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Dia', list: 'Lista'
        },
        events: window.APP_URLS ? window.APP_URLS.agendaApi : 'api/agenda.php',
        eventClick: (info) => {
          window.dispatchEvent(new CustomEvent('abrir-evento', { detail: info.event }));
        },
        dateClick: (info) => {
          const url = window.APP_URLS ? window.APP_URLS.agendaCrear : 'agenda.php?accion=crear';
          window.location.href = url + '&fecha=' + info.dateStr;
        },
        eventDidMount: (info) => {
          info.el.style.borderRadius  = '12px';
          info.el.style.padding       = '2px 6px';
          info.el.style.fontWeight    = '600';
          info.el.style.boxShadow     = '0 4px 10px -2px rgba(255,107,157,.25)';
        }
      });
      this.cal.render();
    }
  }));

  // ----- eventoModal (agenda) -----
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
        rosa: '#ffd6e7', crema: '#ffe5b4', menta: '#d4f4e2', chocolate: '#e8d4bd'
      };
      return m[color] || '#ffd6e7';
    }
  }));

  // ----- recipeWizard (wizard de receta) -----
  // Usamos IIFE que devuelve un objeto literal (mas robusto
  // que arrow function con block body en algunos navegadores).
  Alpine.data('recipeWizard', (function () {
    return function (initial) {
      initial = initial || {};
      var raw = (initial.instrucciones || '').toString();
      var lines = raw.split(/\r?\n/)
                     .map(function (l) { return l.replace(/^\s*\d+[.)]\s*/, '').trim(); })
                     .filter(function (l) { return l.length > 0; });
      var pasosIniciales = lines.length > 0
        ? lines.map(function (t) { return { texto: t }; })
        : [{ texto: '' }];

      return {
        step: 1,
        totalSteps: 5,
        submitting: false,
        progress: 0,
        loadingMsg: '',

        nombre:      initial.nombre      || '',
        descripcion: initial.descripcion || '',
        porciones:   parseInt(initial.porciones) || 8,
        pasos: pasosIniciales,

        ingredientes: Array.isArray(initial.ingredientes) ? initial.ingredientes : [],
        items:         Array.isArray(initial.items)         ? initial.items         : [],

        imagenFile:    null,
        imagenPreview: initial.imagenActual || null,

        iva: 0,
        margen: 60,
        otrosCostos: 0,

        loadMsgs: [
          'Precalentando el horno...',
          'Mezclando los ingredientes...',
          'Horneando a 180 grados...',
          'Decorando con frosting...',
          'Dando el toque final...',
          'Lista tu delicia!'
        ],

        get steps() {
          return [
            { n: 1, icon: '1', label: 'Basicos' },
            { n: 2, icon: '2', label: 'Ingredientes' },
            { n: 3, icon: '3', label: 'Preparacion' },
            { n: 4, icon: '4', label: 'Foto' },
            { n: 5, icon: '5', label: 'Reporte' }
          ];
        },
        get progressPct() {
          return Math.round(((this.step - 1) / (this.totalSteps - 1)) * 100);
        },
        get step1Valid() {
          return (this.nombre || '').trim().length >= 2 && parseInt(this.porciones) > 0;
        },
        get step2Valid() {
          return this.items.length > 0 && this.items.every(function (i) {
            return i.ingrediente_id && parseFloat(i.cantidad) > 0;
          });
        },
        get step3Valid() {
          return this.pasos.length > 0 && this.pasos.some(function (p) {
            return (p.texto || '').trim().length >= 3;
          });
        },
        get step4Valid() { return true; },
        get step5Valid() { return true; },
        get listaPasos() {
          return this.pasos.map(function (p) { return (p.texto || '').trim(); }).filter(function (t) { return t.length > 0; });
        },
        get itemsJson() {
          return JSON.stringify(this.items.map(function (i) {
            return {
              ingrediente_id: parseInt(i.ingrediente_id) || 0,
              cantidad:       parseFloat(i.cantidad) || 0
            };
          }));
        },

        canProceed() {
          var checks = [this.step1Valid, this.step2Valid, this.step3Valid, this.step4Valid, this.step5Valid];
          return checks[this.step - 1];
        },
        next() {
          if (!this.canProceed()) { this.warnStep(); return; }
          if (this.step < this.totalSteps) {
            this.step++;
            window.scrollTo({ top: 0, behavior: 'smooth' });
          }
        },
        prev() {
          if (this.step > 1) {
            this.step--;
            window.scrollTo({ top: 0, behavior: 'smooth' });
          }
        },
        goTo(n) {
          if (n <= this.step) { this.step = n; return; }
          var checks = [this.step1Valid, this.step2Valid, this.step3Valid, this.step4Valid, this.step5Valid];
          for (var i = this.step; i < n; i++) {
            if (!checks[i - 1]) { this.step = i; this.warnStep(); return; }
          }
          this.step = n;
        },
        warnStep() {
          var msgs = {
            1: 'Necesitas un nombre (min. 2 letras) y al menos 1 porcion.',
            2: 'Agrega al menos un ingrediente con cantidad mayor a 0.',
            3: 'Agrega al menos un paso de preparacion con texto.'
          };
          alert(msgs[this.step] || 'Revisa los datos del paso actual.');
        },

        addPaso() {
          this.pasos.push({ texto: '' });
        },
        removePaso(i) {
          if (this.pasos.length <= 1) {
            this.pasos[0] = { texto: '' };
            return;
          }
          this.pasos.splice(i, 1);
        },
        movePasoUp(i) {
          if (i <= 0) return;
          var tmp = this.pasos[i - 1];
          this.pasos[i - 1] = this.pasos[i];
          this.pasos[i] = tmp;
        },
        movePasoDown(i) {
          if (i >= this.pasos.length - 1) return;
          var tmp = this.pasos[i + 1];
          this.pasos[i + 1] = this.pasos[i];
          this.pasos[i] = tmp;
        },

        addItem() {
          this.items.push({
            ingrediente_id: '', cantidad: 0, unidad: '',
            costo_unitario: 0, subtotal: 0
          });
        },
        removeItem(i) { this.items.splice(i, 1); },
        onIngChange(i) {
          var it = this.items[i];
          var ing = this.ingredientes.find(function (x) { return x.id == it.ingrediente_id; });
          if (ing) {
            it.unidad = ing.unidad_medida;
            it.costo_unitario = parseFloat(ing.costo_base) || 0;
          } else {
            it.unidad = '';
            it.costo_unitario = 0;
          }
          this.calcRow(i);
        },
        calcRow(i) {
          var it = this.items[i];
          var ing = this.ingredientes.find(function (x) { return x.id == it.ingrediente_id; });
          if (!ing) { it.subtotal = 0; return; }
          var cant = parseFloat(it.cantidad) || 0;
          var factor = (ing.unidad_medida === 'gramo' || ing.unidad_medida === 'mililitro') ? 0.001 : 1;
          it.subtotal = Math.round(parseFloat(ing.costo_base) * cant * factor * 10000) / 10000;
        },

        get costoIngredientes() {
          return this.items.reduce(function (a, i) { return a + (parseFloat(i.subtotal) || 0); }, 0);
        },
        get costoOtros()  { return parseFloat(this.otrosCostos) || 0; },
        get subtotal()    { return this.costoIngredientes + this.costoOtros; },
        get ivaMonto()    { return this.subtotal * (parseFloat(this.iva) || 0) / 100; },
        get costoTotal()  { return this.subtotal + this.ivaMonto; },
        get costoPorcion(){ return this.costoTotal / Math.max(1, parseInt(this.porciones) || 1); },
        get ganancia()    { return this.costoTotal * (parseFloat(this.margen) || 0) / 100; },
        get precioVenta() { return this.costoTotal + this.ganancia; },
        get precioPorcion(){ return this.precioVenta / Math.max(1, parseInt(this.porciones) || 1); },

        handleImage(event) {
          var f = event.target.files[0];
          if (!f) return;
          this.imagenFile = f;
          var reader = new FileReader();
          reader.onload = function (e) { this.imagenPreview = e.target.result; }.bind(this);
          reader.readAsDataURL(f);
        },
        clearImage() {
          this.imagenFile = null;
          this.imagenPreview = null;
          var inp = document.getElementById('imagen-input');
          if (inp) inp.value = '';
        },

        money(v) {
          var n = parseFloat(v) || 0;
          return '$' + n.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        },
        num(v, d) {
          var n = parseFloat(v) || 0;
          var dec = (d === undefined) ? 0 : d;
          return n.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: dec });
        },
        cantUnidad(unidad, cant) {
          var c = parseFloat(cant) || 0;
          var str = (Math.round(c * 1000) / 1000).toString().replace(/\.?0+$/, '');
          var plur = Math.abs(c) !== 1 ? 's' : '';
          return str + ' ' + unidad + plur;
        },

        submit: function (form) {
          if (!this.step1Valid || !this.step2Valid || !this.step3Valid) {
            this.step = !this.step1Valid ? 1 : (!this.step2Valid ? 2 : 3);
            this.warnStep();
            return;
          }
          // Submit directo, sin loading artificial. Asi si algo
          // falla, el navegador maneja el redirect normalmente.
          form.submit();
        }
      };
    };
  })());

});