-- ==========================================================
-- Correccion masiva de datos de compra (Excel de la usuaria)
-- ==========================================================
-- Antes de ejecutar: SELECCIONA tu base de datos en phpMyAdmin.
-- Idempotente: se puede correr varias veces sin problema.
--
-- Este script corrige cantidad_compra / unidad_compra / precio_compra
-- de los ingredientes que ya existen, usando los mismos valores
-- que estaban en la planilla Excel.
--
-- Si un ingrediente tiene un nombre distinto en la base de datos,
-- el UPDATE no le hace nada y tendras que editarlo manualmente.
-- ==========================================================

UPDATE `ingredientes`
   SET `cantidad_compra` = 1000, `unidad_compra` = 'gramo',     `precio_compra` = 18000
 WHERE `nombre` = 'Almendras';

-- 'Anis' no tiene precio en el Excel, lo dejamos como esta.

UPDATE `ingredientes`
   SET `cantidad_compra` = 1000, `unidad_compra` = 'gramo',     `precio_compra` = 1120
 WHERE `nombre` = 'Azucar (sin isidro)';

UPDATE `ingredientes`
   SET `cantidad_compra` = 15,   `unidad_compra` = 'gramo',     `precio_compra` = 610
 WHERE `nombre` = 'Canela';

UPDATE `ingredientes`
   SET `cantidad_compra` = 5,    `unidad_compra` = 'gramo',     `precio_compra` = 450
 WHERE `nombre` = 'Clavo de olor';

UPDATE `ingredientes`
   SET `cantidad_compra` = 1000, `unidad_compra` = 'mililitro', `precio_compra` = 3890
 WHERE `nombre` = 'Crema bravo';

UPDATE `ingredientes`
   SET `cantidad_compra` = 1000, `unidad_compra` = 'mililitro', `precio_compra` = 4890
 WHERE `nombre` = 'Crema soprole';

UPDATE `ingredientes`
   SET `cantidad_compra` = 1000, `unidad_compra` = 'mililitro', `precio_compra` = 4790
 WHERE `nombre` = 'Crema bettercream richs';

UPDATE `ingredientes`
   SET `cantidad_compra` = 590,  `unidad_compra` = 'gramo',     `precio_compra` = 2390
 WHERE `nombre` = 'Durazno en conserva';

UPDATE `ingredientes`
   SET `cantidad_compra` = 1000, `unidad_compra` = 'gramo',     `precio_compra` = 1190
 WHERE `nombre` = 'Harina';

UPDATE `ingredientes`
   SET `cantidad_compra` = 1,    `unidad_compra` = 'pieza',     `precio_compra` = 200
 WHERE `nombre` = 'Huevos';

UPDATE `ingredientes`
   SET `cantidad_compra` = 15,   `unidad_compra` = 'gramo',     `precio_compra` = 430
 WHERE `nombre` = 'Jengibre en polvo';

UPDATE `ingredientes`
   SET `cantidad_compra` = 397,  `unidad_compra` = 'gramo',     `precio_compra` = 1540
 WHERE `nombre` = 'Leche condensada';

UPDATE `ingredientes`
   SET `cantidad_compra` = 1200, `unidad_compra` = 'gramo',     `precio_compra` = 4990
 WHERE `nombre` = 'Leche consesada';

UPDATE `ingredientes`
   SET `cantidad_compra` = 397,  `unidad_compra` = 'gramo',     `precio_compra` = 1950
 WHERE `nombre` = 'Leche consesada (nestle)';

UPDATE `ingredientes`
   SET `cantidad_compra` = 1000, `unidad_compra` = 'mililitro', `precio_compra` = 1290
 WHERE `nombre` = 'Leche entera (soprole)';

UPDATE `ingredientes`
   SET `cantidad_compra` = 1000, `unidad_compra` = 'gramo',     `precio_compra` = 2500
 WHERE `nombre` = 'Limon';

UPDATE `ingredientes`
   SET `cantidad_compra` = 1000, `unidad_compra` = 'gramo',     `precio_compra` = 5390
 WHERE `nombre` = 'Manjar';

UPDATE `ingredientes`
   SET `cantidad_compra` = 250,  `unidad_compra` = 'gramo',     `precio_compra` = 2690
 WHERE `nombre` = 'Mantequilla (colun)';

UPDATE `ingredientes`
   SET `cantidad_compra` = 1000, `unidad_compra` = 'gramo',     `precio_compra` = 2490
 WHERE `nombre` = 'Manzana';

UPDATE `ingredientes`
   SET `cantidad_compra` = 250,  `unidad_compra` = 'gramo',     `precio_compra` = 1490
 WHERE `nombre` = 'Margarina';

UPDATE `ingredientes`
   SET `cantidad_compra` = 1000, `unidad_compra` = 'gramo',     `precio_compra` = 3990
 WHERE `nombre` = 'Margarina hornito';

UPDATE `ingredientes`
   SET `cantidad_compra` = 1000, `unidad_compra` = 'gramo',     `precio_compra` = 12000
 WHERE `nombre` = 'Nueces';

SELECT 'Migracion de datos de compra aplicada. Verifica los nombres
de ingredientes que no se actualizaron (puede haber diferencias de
mayusculas/acentos entre la app y esta planilla).' AS aviso;
