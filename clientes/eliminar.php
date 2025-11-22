<?php
// 1. Configuración y Seguridad
require_once '../includes/config.php';

// A) Verificamos Login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// B) CONTROL DE ROLES (CRÍTICO)
// Esto es el "muro de fuego". Aunque ocultamos el botón en el index,
// si un operador intenta llamar a este archivo, lo frenamos acá.
if ($_SESSION['rol'] !== 'admin') {
    die("ACCESO DENEGADO: No tenés permisos de Administrador para eliminar registros.");
}

// --------------------------------------------------------------------------
// [LÓGICA] PROCESAMIENTO
// --------------------------------------------------------------------------
// Solo aceptamos POST (para evitar ataques por links maliciosos)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // C) Validación CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Error de seguridad: Token CSRF inválido.");
    }

    // Obtenemos ID
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);

    if ($id) {
        try {
            // PASO 1: Buscar la foto antes de borrar el registro
            // Necesitamos saber el nombre del archivo para borrarlo del disco.
            $sql_foto = "SELECT fotoPerfil FROM clientes WHERE id = :id";
            $stmt_foto = $pdo->prepare($sql_foto);
            $stmt_foto->execute([':id' => $id]);
            $cliente = $stmt_foto->fetch();

            // Si el cliente existe, procedemos
            if ($cliente) {
                
                // Borrado de archivo físico (Limpieza)
                if (!empty($cliente['fotoPerfil'])) {
                    $ruta_archivo = '../uploads/' . $cliente['fotoPerfil'];
                    if (file_exists($ruta_archivo)) {
                        unlink($ruta_archivo); // ¡Chau foto!
                    }
                }

                // PASO 2: Borrar de la Base de Datos
                // Gracias a las Foreign Keys con ON DELETE CASCADE que definimos al principio,
                // al borrar el cliente, MySQL borra SOLITO las relaciones en 'cliente_especialidad'.
                $sql_delete = "DELETE FROM clientes WHERE id = :id";
                $stmt_delete = $pdo->prepare($sql_delete);
                $stmt_delete->execute([':id' => $id]);

                // Éxito
                // Podríamos usar una sesión flash para mostrar mensaje, pero por ahora redirigimos.
                header('Location: index.php?msg=eliminado');
                exit;
            } else {
                die("El cliente no existe.");
            }

        } catch (PDOException $e) {
            die("Error de base de datos al eliminar: " . $e->getMessage());
        }
    } else {
        die("ID inválido.");
    }

} else {
    // Si intentan entrar por GET (escribiendo la url en el navegador) lo sacamos volando.
    header('Location: index.php');
    exit;
}
?>