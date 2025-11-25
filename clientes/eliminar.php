<?php

/**
 * clientes/eliminar.php
 * Modulo de Baja Logica (Soft Delete).
 *
 * Este script procesa la solicitud de desactivacion de un cliente.
 * Implementa un borrado logico (actualizando el campo 'activo' a 0) para preservar
 * la integridad referencial y el historial de datos.
 *
 * Medidas de Seguridad:
 * - Control de Acceso Basado en Roles: Solo 'admin'.
 * - Proteccion CSRF: Verificacion de token en solicitudes POST.
 * - Sanitizacion de Entrada: Filtrado de ID entero.
 */

require_once '../includes/config.php';
require_once '../includes/require_login.php';

// Control de Autorizacion
// Se verifica estrictamente que el usuario tenga el rol de administrador.
// Los operadores no tienen permisos destructivos en el sistema.
if ($_SESSION['rol'] !== 'admin') {
    $_SESSION['flash_msg'] = "Acceso denegado: Privilegios insuficientes para realizar esta accion.";
    $_SESSION['flash_type'] = "danger";
    header('Location: index.php');
    exit;
}

// Procesamiento de la Solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validacion de Token Anti-CSRF
    // Previene que sitios externos fuercen la ejecucion de esta accion sin consentimiento.
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['flash_msg'] = "Error de seguridad (Token invalido o expirado).";
        $_SESSION['flash_type'] = "danger";
        header('Location: index.php');
        exit;
    }

    // Sanitizacion del Identificador
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);

    if ($id) {
        try {
            // Ejecucion del Delete
            // No se utiliza DELETE FROM. Se actualiza el estado para ocultarlo de las vistas principales.
            $stmt = $pdo->prepare("UPDATE clientes SET activo = 0 WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Verificacion de afectacion de filas (Row Count)
            // Confirma si el registro existia y fue modificado.
            if ($stmt->rowCount() > 0) {
                $_SESSION['flash_msg'] = "Cliente desactivado correctamente (enviado a Papelera).";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_msg'] = "El cliente no existe o ya se encuentra desactivado.";
                $_SESSION['flash_type'] = "warning";
            }
        } catch (PDOException $e) {
            // Manejo de excepciones de base de datos 
            $_SESSION['flash_msg'] = "Error de Base de Datos: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_msg'] = "Identificador de cliente invalido.";
        $_SESSION['flash_type'] = "danger";
    }
} else {
    // Proteccion contra acceso directo via GET u otros metodos
    header('Location: index.php');
    exit;
}

// Redireccion final al listado de clientes
header('Location: index.php');
exit;
