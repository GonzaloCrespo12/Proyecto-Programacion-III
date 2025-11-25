<?php

/**
 * auth/logout.php
 * Módulo de Cierre de Sesion.
 * Se encarga de limpiar variables, invalidar cookies y destruir la sesión en el servidor.
 */

// Inicializar sesión existente para poder manipularla
require_once '../includes/config.php';


// PROCESO DE DESTRUCCION DE SESION
// Limpieza de variables de sesion
// Se vacía el array superglobal $_SESSION para eliminar datos en tiempo de ejecucion.
$_SESSION = [];

// Invalidacion de la Cookie de Sesion (Lado Cliente)
// Es necesario caducar la cookie en el navegador para evitar reutilizacion.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruccion de la sesion (Lado Servidor)
// Elimina el archivo de sesion o registro en el almacenamiento del servidor.
session_destroy();

// REDIRECCIONAMIENTO
header("Location: login.php");
exit;
