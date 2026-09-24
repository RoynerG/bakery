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

-- 7. Agregar columna categoria_id a agenda (si no existe)
SET @col_existe = (
  SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE()
     AND table_name   = 'agenda'
     AND column_name  = 'categoria_id'
);

SET @sql = IF(@col_existe = 0,
  'ALTER TABLE `agenda` ADD COLUMN `categoria_id` INT UNSIGNED DEFAULT NULL AFTER `color`',
  'SELECT "categoria_id ya existe" AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 8. FK categoria -> agenda (si no existe)
SET @fk_existe = (
  SELECT COUNT(*) FROM information_schema.table_constraints
   WHERE table_schema      = DATABASE()
     AND table_name        = 'agenda'
     AND constraint_name   = 'fk_agenda_categoria'
);

SET @sql = IF(@fk_existe = 0,
  'ALTER TABLE `agenda` ADD CONSTRAINT `fk_agenda_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT "fk_agenda_categoria ya existe" AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 9. Indice en categoria_id de agenda
SET @idx_existe = (
  SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = DATABASE()
     AND table_name   = 'agenda'
     AND index_name   = 'idx_categoria'
);

SET @sql = IF(@idx_existe = 0,
  'ALTER TABLE `agenda` ADD KEY `idx_categoria` (`categoria_id`)',
  'SELECT "idx_categoria ya existe" AS msg'
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

-- 10. Agregar cantidad_compra a ingredientes (si no existe)
SET @col_existe = (
  SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE()
     AND table_name   = 'ingredientes'
     AND column_name  = 'cantidad_compra'
);
SET @sql = IF(@col_existe = 0,
  'ALTER TABLE `ingredientes` ADD COLUMN `cantidad_compra` DECIMAL(10,4) NOT NULL DEFAULT 1.0000 AFTER `costo_base`',
  'SELECT "cantidad_compra ya existe" AS msg'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 11. Agregar unidad_compra a ingredientes (si no existe)
SET @col_existe = (
  SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE()
     AND table_name   = 'ingredientes'
     AND column_name  = 'unidad_compra'
);
SET @sql = IF(@col_existe = 0,
  'ALTER TABLE `ingredientes` ADD COLUMN `unidad_compra` ENUM(''kilo'',''litro'',''pieza'',''gramo'',''mililitro'') NOT NULL DEFAULT ''pieza'' AFTER `cantidad_compra`',
  'SELECT "unidad_compra ya existe" AS msg'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 12. Agregar precio_compra a ingredientes (si no existe)
SET @col_existe = (
  SELECT COUNT(*) FROM information_schema.columns
   WHERE table_schema = DATABASE()
     AND table_name   = 'ingredientes'
     AND column_name  = 'precio_compra'
);
SET @sql = IF(@col_existe = 0,
  'ALTER TABLE `ingredientes` ADD COLUMN `precio_compra` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `unidad_compra`',
  'SELECT "precio_compra ya existe" AS msg'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 13. Backfill: para ingredientes existentes, asumimos que el
-- costo_base guardado equivale a "compré 1 unidad de unidad_medida
-- por ese precio". Asi la UI muestra algo coherente y editable.
UPDATE `ingredientes`
   SET `cantidad_compra` = 1,
       `unidad_compra`   = `unidad_medida`,
       `precio_compra`   = `costo_base`
 WHERE `cantidad_compra` = 1
   AND `precio_compra`   = 0
   AND `costo_base`     <> 0;

SELECT 'Migracion completada con exito.' AS resultado;

-- ==========================================================
-- Catalogo publico (productos) - v1.3
-- ==========================================================

-- 14. Tabla productos (si no existe)
CREATE TABLE IF NOT EXISTS `productos` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`       VARCHAR(160) NOT NULL,
  `slug`         VARCHAR(180) NOT NULL,
  `descripcion`  TEXT,
  `tipo`         ENUM('destacado','clasica','premium','extra','galeria') NOT NULL DEFAULT 'clasica',
  `imagen`       VARCHAR(255) DEFAULT NULL,
  `precio_desde` DECIMAL(12,2) DEFAULT NULL,
  `visible`      TINYINT(1) NOT NULL DEFAULT 1,
  `orden`        INT NOT NULL DEFAULT 0,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_tipo` (`tipo`, `orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Tabla producto_variantes (si no existe)
CREATE TABLE IF NOT EXISTS `producto_variantes` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `producto_id` INT UNSIGNED NOT NULL,
  `label`       VARCHAR(120) NOT NULL,
  `precio`      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `orden`       INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_producto` (`producto_id`, `orden`),
  CONSTRAINT `fk_variante_producto`
    FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Tablas de catalogo creadas (productos, producto_variantes).' AS aviso;

-- ==========================================================
-- Config y tamanos por categoria (v1.4)
-- ==========================================================

-- 16. Tabla config (clave-valor)
CREATE TABLE IF NOT EXISTS `config` (
  `clave`      VARCHAR(60) NOT NULL,
  `valor`      TEXT,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `config` (`clave`, `valor`) VALUES
  ('catalogo_tagline',   'PASTELERÍA Y REPOSTERÍA ARTESANAL'),
  ('catalogo_instagram', '@dulce.rinconcito'),
  ('catalogo_whatsapp',  '+56 9 4968 080'),
  ('catalogo_cover_deco','🥐 🧁 🍰 🍪 🧁');

-- 17. Tabla categoria_variantes
CREATE TABLE IF NOT EXISTS `categoria_variantes` (
  `id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo`    ENUM('clasica','premium','destacado') NOT NULL,
  `label`   VARCHAR(120) NOT NULL,
  `precio`  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `orden`   INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`, `orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `categoria_variantes` (`tipo`, `label`, `precio`, `orden`) VALUES
  ('clasica', '10 personas', 23990, 0),
  ('clasica', '15 personas', 27990, 1),
  ('clasica', '20 personas', 31990, 2),
  ('clasica', '30 personas', 44990, 3),
  ('premium', '10 personas', 25990, 0),
  ('premium', '15 personas', 29990, 1),
  ('premium', '20 personas', 33990, 2),
  ('premium', '30 personas', 46990, 3);

SELECT 'Tablas config y categoria_variantes listas.' AS aviso;