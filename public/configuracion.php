<?php
/**
 * Redirect: la configuracion del catalogo se edita ahora desde
 * /usuarios.php (seccion al final). Este archivo queda como
 * compat para no romper enlaces viejos.
 */
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/helpers.php';

use App\Auth;
Auth::require();

flash('info', 'La configuracion del catalogo ahora se edita desde la pagina de usuarios.');
redirect('usuarios.php#catalogo');
