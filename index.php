<?php
/**
 * Punto de entrada raíz.
 * Si Apache + mod_rewrite no están disponibles, redirige
 * al directorio /public/ donde vive la aplicación.
 */
header('Location: public/');
exit;
