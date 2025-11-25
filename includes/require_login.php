<?php

/**
 * require_login.php
 * Middleware de Control de Acceso.
 *
 * Este script debe incluirse al principio de cualquier archivo que requiera
 * autenticacion. Verifica si existe una sesion de usuario activa.
 * Si no hay sesion, redirige al usuario al formulario de login y detiene la ejecucion.
 */

// Verificacion defensiva: Asegurar que la sesion este iniciada.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validacion de credencial de sesion (ID de usuario).
if (!isset($_SESSION['user_id'])) {
    // Redireccion al login utilizando la ruta absoluta definida en config.php.
    header('Location: ' . BASE_URL . '/auth/login.php');

    // Detener ejecucion inmediatamente para evitar que se procese codigo posterior.
    exit;
}
