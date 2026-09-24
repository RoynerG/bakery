-- ==========================================================
-- Vaciar ingredientes y recetas para empezar de cero
-- ==========================================================
-- Antes de ejecutar: SELECCIONA tu base de datos en phpMyAdmin.
-- ==========================================================
-- PELIGRO: este script BORRA todas las recetas y todos los
-- ingredientes. Usar solo si querés empezar de cero.
-- Las notas, categorias, agenda y usuarios NO se tocan.
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `receta_ingredientes`;
TRUNCATE TABLE `recetas`;
TRUNCATE TABLE `ingredientes`;
SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Tablas vaciadas. Ya podes empezar a cargar tus ingredientes desde la app.' AS aviso;
