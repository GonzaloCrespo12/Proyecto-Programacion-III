<?php
/**
 * 1. Inicia la sesión del usuario de forma segura.
 * 2. Configura las credenciales de la Base de Datos.
 * 3. Establece la conexión usando PDO (Lo más robusto).
 */

// 1. Iniciamos la sesión (Si no está iniciada ya)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Credenciales de la Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'proyectoprogramacioniii');
define('DB_USER', 'root');
define('DB_PASS', '');            

// 3. Conexión con PDO (PHP Data Objects)
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    // Opciones de configuración de PDO
    $options = [
        // Si hay error en SQL, que tire una Excepción (así no fallamos en silencio)
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Que los resultados vengan como Array Asociativo por defecto ($fila['nombre'])
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Desactivamos la emulación de prepares para máxima seguridad contra inyección SQL
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    //instancia de conexión
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    ($e->getMessage());
}

// 4. Helpers de Seguridad (CSRF)
// Dejamos esto listo para cuando hagamos los formularios.
if (empty($_SESSION['csrf_token'])) {
    // Generamos un token criptográficamente seguro si no existe
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Función rápida para debuggear sin romper el HTML (Solo para desarrollo)
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}
// Definimos la constante global. Ojo: Sin signo $ y en MAYÚSCULAS.
define('BASE_URL', 'http://localhost/Proyecto-Pogramacion-III');
?>