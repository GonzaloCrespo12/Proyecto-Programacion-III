<?php
// clientes/eliminar.php
require_once '../includes/config.php';
require_once '../includes/require_login.php';

// 2. Control de Roles (Solo Admin)
if ($_SESSION['rol'] !== 'admin') {
    $_SESSION['flash_msg'] = "Acceso denegado: Solo administradores pueden borrar.";
    $_SESSION['flash_type'] = "danger";
    header('Location: index.php');
    exit;
}

// 3. Procesamiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validación CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['flash_msg'] = "Error de seguridad (Token inválido).";
        $_SESSION['flash_type'] = "danger";
        header('Location: index.php');
        exit;
    }

    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);

    if ($id) {
        try {
            // SOFT DELETE: No borramos físicamente, solo desactivamos.
            // Actualizamos la columna 'activo' a 0
            $stmt = $pdo->prepare("UPDATE clientes SET activo = 0 WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Verificamos si se tocó alguna fila
            if ($stmt->rowCount() > 0) {
                $_SESSION['flash_msg'] = "Cliente desactivado correctamente (Papelera).";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_msg'] = "El cliente no existe o ya estaba desactivado.";
                $_SESSION['flash_type'] = "warning";
            }
        } catch (PDOException $e) {
            $_SESSION['flash_msg'] = "Error de BD: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_msg'] = "ID inválido.";
        $_SESSION['flash_type'] = "danger";
    }
} else {
    // Si entran por GET
    header('Location: index.php');
    exit;
}

// Redirección final
header('Location: index.php');
exit;
