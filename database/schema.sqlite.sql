-- ==========================================================
-- Esquema de Base de Datos: Repostería
-- Compatible con: SQLite 3
-- ==========================================================

-- ----------------------------------------------------------
-- Tabla: ingredientes
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS ingredientes (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre        TEXT NOT NULL,
  unidad_medida TEXT NOT NULL DEFAULT 'pieza'
                CHECK (unidad_medida IN ('kilo','litro','pieza','gramo','mililitro')),
  costo_base    REAL NOT NULL DEFAULT 0,
  notas         TEXT,
  created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
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

-- ==========================================================
-- Datos iniciales (opcional)
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
