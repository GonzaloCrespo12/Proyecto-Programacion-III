<?php

/**
 * config.php
 * Modulo de Configuracion Principal.
 *
 * Este archivo se encarga de inicializar el entorno de la aplicacion:
 * 1. Configuracion segura de sesiones (Cookies HTTPOnly).
 * 2. Definicion de constantes globales (Rutas y Credenciales).
 * 3. Conexion a la base de datos mediante PDO.
 * 4. Generacion de tokens CSRF para seguridad en formularios.
 * 5. Definicion de funciones auxiliares (Helpers).
 */

// CONFIGURACION DE SESION SEGURA
// Se establecen parametros estrictos para las cookies de sesion antes de iniciarla.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,            // La sesion expira al cerrar el navegador.
        'path' => '/',              // Valida en todo el dominio.
        'domain' => '',             // Dominio actual (vacio para localhost).
        'secure' => false,          // TRUE solo si se utiliza HTTPS.
        'httponly' => true,         // Previene acceso a la cookie desde JavaScript (Anti-XSS).
        'samesite' => 'Strict'      // Proteccion adicional contra ataques CSRF.
    ]);

    session_start();
}

// CONSTANTES GLOBALES Y CREDENCIALES
// URL base para generar rutas absolutas y evitar problemas de navegacion.
// Ajustar segun entorno de desarrollo o produccion.
define('BASE_URL', 'http://localhost/Proyecto-Programacion-III');

// Credenciales de conexion a la Base de Datos.
// Ajustar segun entorno de desarrollo o produccion.
define('DB_HOST', 'localhost');
define('DB_NAME', 'base_datos_clientes');
define('DB_USER', 'root');
define('DB_PASS', '');

// CONEXION A BASE DE DATOS (PDO)
try {
    // Data Source Name (DSN) con codificacion UTF-8 para caracteres especiales.
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    // Configuracion de opciones PDO para maximizar seguridad y manejo de errores.
    $options = [
        // Lanzar excepciones en caso de error SQL (permite uso de try-catch).
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Obtener resultados como arrays asociativos por defecto.
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Desactivar emulacion para usar sentencias preparadas nativas (Prevencion SQL Injection).
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Instancia del objeto PDO.
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // En produccion, no se deben mostrar detalles del error al usuario final.
    // Se detiene la ejecucion con un mensaje generico.
    die("Error critico: No se pudo establecer conexion con la base de datos.");
}

// SEGURIDAD CSRF (Cross-Site Request Forgery)
// Generacion de token unico por sesion para validar el origen de los formularios POST.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// FUNCION Auxiliar

/**
 * Sanitiza una cadena para su salida segura en HTML.
 * Previene ataques XSS (Cross-Site Scripting) convirtiendo caracteres especiales.
 *
 *  $string Cadena de texto a limpiar.
 *  string Cadena sanitizada segura para imprimir.
 */
function h($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
