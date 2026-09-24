-- ==========================================================
-- Importar / corregir ingredientes desde la planilla Excel
-- ==========================================================
-- Antes de ejecutar: SELECCIONA tu base de datos en phpMyAdmin.
-- Idempotente: corre varias veces sin problema.
--
-- Que hace:
--   1. Inserta cada ingrediente del Excel si NO existe (por nombre).
--   2. Si ya existe, corrige cantidad_compra / unidad_compra /
--      precio_compra / costo_base / unidad_medida con los valores
--      del Excel, pero PRESERVA imagen, notas y created_at.
--
-- Despues de correrlo, todos los 23 ingredientes del Excel
-- quedan en la base de datos con los valores correctos. No
-- necesitas entrar uno por uno al formulario.
-- ==========================================================

INSERT INTO `ingredientes`
  (`nombre`, `unidad_medida`, `costo_base`, `cantidad_compra`, `unidad_compra`, `precio_compra`)
VALUES
  ('Almendras',                'gramo',      18.00,    1000, 'gramo',     18000),
  ('Azucar (sin isidro)',      'gramo',       1.12,    1000, 'gramo',      1120),
  ('Canela',                   'gramo',      40.67,      15, 'gramo',       610),
  ('Clavo de olor',            'gramo',      90.00,       5, 'gramo',       450),
  ('Crema bravo',              'mililitro',   3.89,    1000, 'mililitro',  3890),
  ('Crema soprole',            'mililitro',   4.89,    1000, 'mililitro',  4890),
  ('Crema bettercream richs',  'mililitro',   4.79,    1000, 'mililitro',  4790),
  ('Durazno en conserva',      'gramo',       4.05,     590, 'gramo',      2390),
  ('Harina',                   'gramo',       1.19,    1000, 'gramo',      1190),
  ('Huevos',                   'pieza',     200.00,       1, 'pieza',       200),
  ('Jengibre en polvo',       'gramo',      28.67,      15, 'gramo',       430),
  ('Leche condensada',         'gramo',       3.88,     397, 'gramo',      1540),
  ('Leche consesada',          'gramo',       4.16,    1200, 'gramo',      4990),
  ('Leche consesada (nestle)', 'gramo',       4.91,     397, 'gramo',      1950),
  ('Leche entera (soprole)',   'mililitro',   1.29,    1000, 'mililitro',  1290),
  ('Limon',                    'gramo',       2.50,    1000, 'gramo',      2500),
  ('Manjar',                   'gramo',       5.39,    1000, 'gramo',      5390),
  ('Mantequilla (colun)',      'gramo',      10.76,     250, 'gramo',      2690),
  ('Manzana',                  'gramo',       2.49,    1000, 'gramo',      2490),
  ('Margarina',                'gramo',       5.96,     250, 'gramo',      1490),
  ('Margarina hornito',        'gramo',       3.99,    1000, 'gramo',      3990),
  ('Nueces',                   'gramo',      12.00,    1000, 'gramo',     12000)
ON DUPLICATE KEY UPDATE
  `unidad_medida`   = VALUES(`unidad_medida`),
  `costo_base`      = VALUES(`costo_base`),
  `cantidad_compra` = VALUES(`cantidad_compra`),
  `unidad_compra`   = VALUES(`unidad_compra`),
  `precio_compra`   = VALUES(`precio_compra`),
  `updated_at`      = CURRENT_TIMESTAMP;

-- 'Anis' se queda como esta: en el Excel no figura precio.

SELECT 'Listo. Los 22 ingredientes del Excel quedaron importados/actualizados.' AS aviso;
