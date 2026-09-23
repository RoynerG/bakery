<?php
/**
 * Logout: cierra la sesión y redirige al login.
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/helpers.php';

use App\Auth;

Auth::logout();
flash('success', 'Sesión cerrada correctamente.');
redirect('login.php');