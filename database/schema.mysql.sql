-- ==========================================================
-- Esquema MySQL/MariaDB para Hostinger (sin CREATE DATABASE)
-- ==========================================================
-- En Hostinger la base de datos YA EXISTE (la creas desde el panel).
-- Antes de ejecutar este script, SELECCIONA tu base de datos en
-- phpMyAdmin (panel izquierdo). NO hace falta CREATE DATABASE.
--
-- Compatible con: MySQL 5.7+ / MariaDB 10.3+
-- ==========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- Limpieza previa (orden importa por las FK)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `receta_ingredientes`;
DROP TABLE IF EXISTS `recetas`;
DROP TABLE IF EXISTS `ingredientes`;
DROP TABLE IF EXISTS `notas`;
DROP TABLE IF EXISTS `agenda`;
DROP TABLE IF EXISTS `categorias`;
DROP TABLE IF EXISTS `usuarios`;

-- ----------------------------------------------------------
-- Tabla: usuarios (autenticación)
-- ----------------------------------------------------------
CREATE TABLE `usuarios` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario`       VARCHAR(60) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `nombre`        VARCHAR(120) DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: ingredientes
-- ----------------------------------------------------------
CREATE TABLE `ingredientes` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`          VARCHAR(120) NOT NULL,
  `unidad_medida`   ENUM('kilo','litro','pieza','gramo','mililitro') NOT NULL DEFAULT 'pieza',
  `costo_base`      DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `cantidad_compra` DECIMAL(10,4) NOT NULL DEFAULT 1.0000,
  `unidad_compra`   ENUM('kilo','litro','pieza','gramo','mililitro') NOT NULL DEFAULT 'pieza',
  `precio_compra`   DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `notas`           VARCHAR(255) DEFAULT NULL,
  `imagen`          VARCHAR(255) DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: recetas
-- ----------------------------------------------------------
CREATE TABLE `recetas` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`        VARCHAR(180) NOT NULL,
  `descripcion`   VARCHAR(500) DEFAULT NULL,
  `instrucciones` TEXT NOT NULL,
  `imagen`        VARCHAR(255) DEFAULT NULL,
  `porciones`     INT UNSIGNED NOT NULL DEFAULT 1,
  `costo_total`   DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: receta_ingredientes (muchos a muchos)
-- ----------------------------------------------------------
CREATE TABLE `receta_ingredientes` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `receta_id`      INT UNSIGNED NOT NULL,
  `ingrediente_id` INT UNSIGNED NOT NULL,
  `cantidad`       DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `subtotal`       DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  PRIMARY KEY (`id`),
  KEY `idx_receta` (`receta_id`),
  KEY `idx_ingrediente` (`ingrediente_id`),
  CONSTRAINT `fk_ri_receta`
    FOREIGN KEY (`receta_id`) REFERENCES `recetas` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ri_ingrediente`
    FOREIGN KEY (`ingrediente_id`) REFERENCES `ingredientes` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: categorias (etiquetas con emoji para notas)
-- ----------------------------------------------------------
CREATE TABLE `categorias` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `emoji`      VARCHAR(8) NOT NULL,
  `nombre`     VARCHAR(60) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categoria_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: productos (catalogo publico)
-- ----------------------------------------------------------
CREATE TABLE `productos` (
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

-- ----------------------------------------------------------
-- Tabla: producto_variantes (tamanos / precios)
-- ----------------------------------------------------------
CREATE TABLE `producto_variantes` (
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

-- ----------------------------------------------------------
-- Tabla: config (clave-valor para ajustes editables)
-- ----------------------------------------------------------
CREATE TABLE `config` (
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

-- ----------------------------------------------------------
-- Tabla: categoria_variantes (tamanos/precios compartidos)
-- ----------------------------------------------------------
CREATE TABLE `categoria_variantes` (
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

-- ----------------------------------------------------------
-- Tabla: notas (bloc de notas del admin)
-- ----------------------------------------------------------
CREATE TABLE `notas` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo`       VARCHAR(180) NOT NULL,
  `contenido`    TEXT NOT NULL,
  `categoria_id` INT UNSIGNED DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_updated`    (`updated_at`),
  KEY `idx_categoria`  (`categoria_id`),
  CONSTRAINT `fk_notas_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Tabla: agenda (eventos / citas)
-- ----------------------------------------------------------
CREATE TABLE `agenda` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo`       VARCHAR(180) NOT NULL,
  `descripcion`  TEXT,
  `fecha`        DATE NOT NULL,
  `fecha_fin`    DATE DEFAULT NULL,
  `hora`         TIME DEFAULT NULL,
  `todo_el_dia`  TINYINT(1) NOT NULL DEFAULT 0,
  `color`        ENUM('rosa','crema','menta','chocolate') NOT NULL DEFAULT 'rosa',
  `categoria_id` INT UNSIGNED DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fecha`        (`fecha`),
  KEY `idx_fecha_fin`    (`fecha_fin`),
  KEY `idx_categoria`    (`categoria_id`),
  CONSTRAINT `fk_agenda_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- Datos iniciales de ejemplo
-- ==========================================================
INSERT INTO `ingredientes` (`nombre`, `unidad_medida`, `costo_base`, `notas`) VALUES
  ('Harina de trigo',          'kilo',   28.50, 'Para pasteles y galletas'),
  ('Azúcar blanca',            'kilo',   32.00, 'Refinada'),
  ('Mantequilla sin sal',      'kilo',  180.00, 'Sin sal'),
  ('Huevo',                    'pieza',   3.50, 'Tamaño grande'),
  ('Leche entera',             'litro',  28.00, 'Entera'),
  ('Chocolate semi-amargo',    'kilo',  220.00, 'Para cobertura'),
  ('Crema para batir',         'litro',  95.00, '36% grasa'),
  ('Vainilla',                 'mililitro', 1.20, 'Extracto natural'),
  ('Polvo para hornear',       'gramo',   0.45, 'Doble acción'),
  ('Sal',                      'gramo',   0.05, 'Fina');

-- Categorías predeterminadas para las notas
INSERT INTO `categorias` (`emoji`, `nombre`) VALUES
  ('🍰', 'Pedidos'),
  ('🎂', 'Cumpleaños'),
  ('💡', 'Ideas'),
  ('📞', 'Llamadas'),
  ('📦', 'Inventario'),
  ('💰', 'Ventas'),
  ('📝', 'General');

-- ==========================================================
-- Fin del esquema
-- ==========================================================