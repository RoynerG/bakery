-- ==========================================================
-- Migracion para instalaciones existentes (no borra datos).
-- ==========================================================
-- Antes de ejecutar: SELECCIONA tu base de datos en phpMyAdmin.
-- Este script es idempotente: se puede correr varias veces.
-- ==========================================================

-- 1. Crear tabla categorias si no existe
CREATE TABLE IF NOT EXISTS `categorias` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `emoji`      VARCHAR(8) NOT NULL,
  `nombre`     VARCHAR(60) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categoria_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed por defecto (solo si la tabla esta vacia)
INSERT IGNORE INTO `categorias` (`emoji`, `nombre`) VALUES
  ('🍰', 'Pedidos'),
  ('🎂', 'Cumpleaños'),
  ('💡', 'Ideas'),
  ('📞', 'Llamadas'),
  ('📦', 'Inventario'),
  ('💰', 'Ventas'),
  ('📝', 'General');

-- 2. Agregar columna categoria_id a notas (si no existe)
SET @col_existe = (
  SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE()
     AND table_name   = 'notas'
     AND column_name  = 'categoria_id'
);

SET @sql = IF(@col_existe = 0,
  'ALTER TABLE `notas` ADD COLUMN `categoria_id` INT UNSIGNED DEFAULT NULL AFTER `contenido`',
  'SELECT "categoria_id ya existe" AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Agregar FK categoria -> notas (si no existe)
SET @fk_existe = (
  SELECT COUNT(*) FROM information_schema.table_constraints
   WHERE table_schema      = DATABASE()
     AND table_name        = 'notas'
     AND constraint_name   = 'fk_notas_categoria'
);

SET @sql = IF(@fk_existe = 0,
  'ALTER TABLE `notas` ADD CONSTRAINT `fk_notas_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT "fk_notas_categoria ya existe" AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Agregar indice categoria_id a notas (si no existe)
SET @idx_existe = (
  SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = DATABASE()
     AND table_name   = 'notas'
     AND index_name   = 'idx_categoria'
);

SET @sql = IF(@idx_existe = 0,
  'ALTER TABLE `notas` ADD KEY `idx_categoria` (`categoria_id`)',
  'SELECT "idx_categoria ya existe" AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Agregar columna fecha_fin a agenda (si no existe)
SET @col_existe = (
  SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE()
     AND table_name   = 'agenda'
     AND column_name  = 'fecha_fin'
);

SET @sql = IF(@col_existe = 0,
  'ALTER TABLE `agenda` ADD COLUMN `fecha_fin` DATE DEFAULT NULL AFTER `fecha`',
  'SELECT "fecha_fin ya existe" AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Indice en fecha_fin
SET @idx_existe = (
  SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = DATABASE()
     AND table_name   = 'agenda'
     AND index_name   = 'idx_fecha_fin'
);

SET @sql = IF(@idx_existe = 0,
  'ALTER TABLE `agenda` ADD KEY `idx_fecha_fin` (`fecha_fin`)',
  'SELECT "idx_fecha_fin ya existe" AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ==========================================================
-- La columna 'color' de notas queda en la tabla pero ya no
-- se usa. Puedes ignorarla o borrarla con:
--   ALTER TABLE notas DROP COLUMN color;
-- (No la borramos automaticamente por seguridad)
-- ==========================================================
SELECT 'Migracion completada con exito.' AS resultado;