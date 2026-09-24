-- ==========================================================
-- Esquema de Base de Datos: Dulce Rinconcito
-- Compatible con: SQLite 3
-- ==========================================================

-- ----------------------------------------------------------
-- Tabla: usuarios (autenticación)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  usuario        TEXT NOT NULL UNIQUE,
  password_hash  TEXT NOT NULL,
  nombre         TEXT,
  created_at     TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at  TEXT
);

-- ----------------------------------------------------------
-- Tabla: ingredientes
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS ingredientes (
  id               INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre           TEXT NOT NULL,
  unidad_medida    TEXT NOT NULL DEFAULT 'pieza'
                   CHECK (unidad_medida IN ('kilo','litro','pieza','gramo','mililitro')),
  costo_base       REAL NOT NULL DEFAULT 0,
  cantidad_compra  REAL NOT NULL DEFAULT 1,
  unidad_compra    TEXT NOT NULL DEFAULT 'pieza'
                   CHECK (unidad_compra IN ('kilo','litro','pieza','gramo','mililitro')),
  precio_compra    REAL NOT NULL DEFAULT 0,
  notas            TEXT,
  imagen           TEXT,
  created_at       TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_ingredientes_nombre ON ingredientes(nombre);

-- ----------------------------------------------------------
-- Tabla: recetas
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS recetas (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre        TEXT NOT NULL,
  descripcion   TEXT,
  instrucciones TEXT NOT NULL,
  imagen        TEXT,
  porciones     INTEGER NOT NULL DEFAULT 1,
  costo_total   REAL NOT NULL DEFAULT 0,
  created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_recetas_nombre ON recetas(nombre);

-- ----------------------------------------------------------
-- Tabla: receta_ingredientes
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS receta_ingredientes (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  receta_id      INTEGER NOT NULL,
  ingrediente_id INTEGER NOT NULL,
  cantidad       REAL NOT NULL DEFAULT 0,
  subtotal       REAL NOT NULL DEFAULT 0,
  FOREIGN KEY (receta_id) REFERENCES recetas(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (ingrediente_id) REFERENCES ingredientes(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_ri_receta      ON receta_ingredientes(receta_id);
CREATE INDEX IF NOT EXISTS idx_ri_ingrediente ON receta_ingredientes(ingrediente_id);

-- ----------------------------------------------------------
-- Tabla: categorias (etiquetas con emoji para notas)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  emoji       TEXT NOT NULL,
  nombre      TEXT NOT NULL UNIQUE,
  created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------
-- Tabla: notas (bloc de notas del admin)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS notas (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo        TEXT NOT NULL,
  contenido     TEXT NOT NULL DEFAULT '',
  categoria_id  INTEGER,
  created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_notas_updated    ON notas(updated_at DESC);
CREATE INDEX IF NOT EXISTS idx_notas_categoria  ON notas(categoria_id);

-- ----------------------------------------------------------
-- Tabla: config (clave-valor para ajustes editables)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS config (
  clave       TEXT PRIMARY KEY,
  valor       TEXT,
  updated_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Valores por defecto del catalogo publico
INSERT OR IGNORE INTO config (clave, valor) VALUES
  ('catalogo_tagline',   'PASTELERÍA Y REPOSTERÍA ARTESANAL'),
  ('catalogo_instagram', '@dulce.rinconcito'),
  ('catalogo_whatsapp',  '+56 9 4968 080'),
  ('catalogo_cover_deco','🥐 🧁 🍰 🍪 🧁');

-- ----------------------------------------------------------
-- Tabla: categoria_variantes (tamanos/precios compartidos)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS categoria_variantes (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  tipo         TEXT NOT NULL CHECK (tipo IN ('clasica','premium','destacado')),
  label        TEXT NOT NULL,
  precio       REAL NOT NULL DEFAULT 0,
  orden        INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_cv_tipo ON categoria_variantes(tipo, orden);

-- Tamaños por defecto para tortas clasicas y premium
INSERT OR IGNORE INTO categoria_variantes (tipo, label, precio, orden) VALUES
  ('clasica', '10 personas', 23990, 0),
  ('clasica', '15 personas', 27990, 1),
  ('clasica', '20 personas', 31990, 2),
  ('clasica', '30 personas', 44990, 3),
  ('premium', '10 personas', 25990, 0),
  ('premium', '15 personas', 29990, 1),
  ('premium', '20 personas', 33990, 2),
  ('premium', '30 personas', 46990, 3);

-- ----------------------------------------------------------
-- Tabla: productos (catalogo publico)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS productos (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre        TEXT NOT NULL,
  slug          TEXT NOT NULL UNIQUE,
  descripcion   TEXT,
  tipo          TEXT NOT NULL DEFAULT 'clasica'
                CHECK (tipo IN ('destacado','clasica','premium','extra','galeria')),
  imagen        TEXT,
  precio_desde  REAL,
  visible       INTEGER NOT NULL DEFAULT 1,
  orden         INTEGER NOT NULL DEFAULT 0,
  created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_productos_tipo ON productos(tipo, orden);

-- ----------------------------------------------------------
-- Tabla: producto_variantes (tamanos / precios)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS producto_variantes (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  producto_id  INTEGER NOT NULL,
  label        TEXT NOT NULL,
  precio       REAL NOT NULL DEFAULT 0,
  orden        INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_variantes_producto ON producto_variantes(producto_id, orden);

-- ----------------------------------------------------------
-- Tabla: agenda (eventos / citas)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS agenda (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo       TEXT NOT NULL,
  descripcion  TEXT,
  fecha        TEXT NOT NULL,                 -- YYYY-MM-DD
  fecha_fin    TEXT,                          -- YYYY-MM-DD opcional
  hora         TEXT,                          -- HH:MM (opcional)
  todo_el_dia  INTEGER NOT NULL DEFAULT 0,
  color        TEXT NOT NULL DEFAULT 'rosa'
               CHECK (color IN ('rosa','crema','menta','chocolate')),
  categoria_id INTEGER,
  created_at   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_agenda_fecha     ON agenda(fecha);
CREATE INDEX IF NOT EXISTS idx_agenda_fecha_fin ON agenda(fecha_fin);
CREATE INDEX IF NOT EXISTS idx_agenda_categoria ON agenda(categoria_id);

-- ==========================================================
-- Datos iniciales de ejemplo (opcional)
-- ==========================================================
INSERT INTO ingredientes (nombre, unidad_medida, costo_base, notas) VALUES
  ('Harina de trigo',       'kilo',   28.50, 'Para pasteles y galletas'),
  ('Azúcar blanca',         'kilo',   32.00, 'Refinada'),
  ('Mantequilla sin sal',   'kilo',  180.00, 'Sin sal'),
  ('Huevo',                 'pieza',   3.50, 'Tamaño grande'),
  ('Leche entera',          'litro',  28.00, 'Entera'),
  ('Chocolate semi-amargo', 'kilo',  220.00, 'Para cobertura'),
  ('Crema para batir',      'litro',  95.00, '36% grasa'),
  ('Vainilla',              'mililitro', 1.20, 'Extracto natural'),
  ('Polvo para hornear',    'gramo',   0.45, 'Doble acción'),
  ('Sal',                   'gramo',   0.05, 'Fina');

-- Categorías predeterminadas
INSERT INTO categorias (emoji, nombre) VALUES
  ('🍰', 'Pedidos'),
  ('🎂', 'Cumpleaños'),
  ('💡', 'Ideas'),
  ('📞', 'Llamadas'),
  ('📦', 'Inventario'),
  ('💰', 'Ventas'),
  ('📝', 'General');