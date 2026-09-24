-- ==========================================================
-- Vaciar para empezar de cero (preserva categorias y usuarios)
-- ==========================================================
-- Antes de ejecutar: SELECCIONA tu base de datos en phpMyAdmin.
-- ==========================================================
-- BORRA:   ingredientes, recetas, receta_ingredientes, notas, agenda
-- PRESERVA: usuarios, categorias (y todas sus relaciones)
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `receta_ingredientes`;
TRUNCATE TABLE `recetas`;
TRUNCATE TABLE `ingredientes`;
TRUNCATE TABLE `notas`;
TRUNCATE TABLE `agenda`;
SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Listo. Se borraron ingredientes, recetas, notas y agenda.
Quedan intactos los usuarios y las categorias.' AS aviso;
