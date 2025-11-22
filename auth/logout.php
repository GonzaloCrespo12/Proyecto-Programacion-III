<?php
// 1. Incluimos config para iniciar la sesión actual (necesitamos "abrirla" para poder "cerrarla")
require_once '../includes/config.php';

// --------------------------------------------------------------------------
// [LOGICA] DESTRUCCIÓN TOTAL DE SESIÓN
// --------------------------------------------------------------------------

// 2. Vaciamos el array de la sesión
// Esto borra $_SESSION['user_id'], $_SESSION['rol'], etc. de la memoria inmediata.
$_SESSION = [];

// 3. Borramos la Cookie de Sesión del navegador
// Esto es clave. Si no hacés esto, la cookie sigue viva en el navegador del usuario.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruimos la sesión en el servidor
session_destroy();

// --------------------------------------------------------------------------
// [REDIRECCIÓN]
// --------------------------------------------------------------------------
// Lo mandamos de vuelta al login.
header("Location: login.php");
exit;
?>