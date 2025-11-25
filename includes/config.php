<?php

/**
 * config.php
 * Encargado de iniciar sesión, conectar a la BD y definir constantes globales.
 * Este archivo se incluye al principio de todos los scripts.
 */

//Iniciamos la sesión (Manejo de estado entre páginas)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//Constantes Globales y Credenciales
// Definimos la URL base para usar rutas absolutas (ajustar si cambia el dominio)
define('BASE_URL', 'http://localhost/proyectoProgramacionIII');

// Credenciales de la Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'pruebagestion');
define('DB_USER', 'root');
define('DB_PASS', '');

// 3. Conexión con PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    // Opciones de configuración de PDO para máxima seguridad
    $options = [
        // Lanzar excepciones si hay error en SQL (para manejarlo en el catch)
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Devolver resultados como Array Asociativo ($fila['email'])
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Desactivar emulación para usar sentencias preparadas nativas (Anti-Inyección SQL real)
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Instancia de conexión
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Cortamos la ejecución y mostramos mensaje:
    die("Error crítico: No se pudo conectar a la base de datos. Contacte al administrador.");
}

// Seguridad: Generacion de Token CSRF
// Esto previene ataques donde envian formularios desde otros sitios.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 5. Funciones Helpers (Ayudas globales)

/**
 * Función de seguridad para evitar XSS (Cross-Site Scripting).
 * Úsala SIEMPRE que imprimas datos en pantalla: <?= h($variable) ?>
 */
function h($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
