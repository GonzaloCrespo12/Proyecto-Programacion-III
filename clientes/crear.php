<?php

/**
 * clientes/crear.php
 * Modulo de Alta de Nuevos Clientes.
 *
 * Controlador encargado de renderizar el formulario de registro y procesar la entrada de datos.
 * Gestiona la persistencia en base de datos, incluyendo relaciones complejas (Especialidades)
 * y almacenamiento de archivos multimedia (Fotos de perfil).
 */

require_once '../includes/config.php';
require_once '../includes/require_login.php';

// Inicializacion de variables para mantener el estado del formulario (Sticky Form)
$nombre = '';
$apellido = '';
$fecha_nacimiento = '';
$mensaje = "";
$tipo_mensaje = "";

// Recuperacion del catalogo de especialidades
try {
    $stmt = $pdo->query("SELECT * FROM especialidades ORDER BY nombre ASC");
    $lista_especialidades = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error critico del sistema al recuperar dependencias.");
}

// Logica de Procesamiento POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validacion de Seguridad CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Error CSRF: Solicitud rechazada por seguridad.");
    }

    // Saneamiento de entradas de texto
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    // Arreglo de especialidades seleccionadas (en caso de que existan)
    $especialidades_seleccionadas = $_POST['especialidades'] ?? [];

    // Validacion de Reglas de Negocio (Campos obligatorios)
    if (empty($nombre) || empty($apellido)) {
        $mensaje = "Los campos Nombre y Apellido son obligatorios.";
        $tipo_mensaje = "danger";
    } elseif (count($especialidades_seleccionadas) === 0) {
        $mensaje = "Debe asignar al menos una especialidad al cliente.";
        $tipo_mensaje = "warning";
    } else {

        // Logica de Gestion de Archivos (Upload)
        $nombre_foto = null;
        $error_subida = false;

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

            // Validacion de Restricciones de Archivo
            if ($_FILES['foto']['size'] > 2097152) { // Limite de 2MB
                $mensaje = "El archivo excede el tamaño maximo permitido (2MB).";
                $tipo_mensaje = "warning";
                $error_subida = true;
            } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) { // Whitelist de extensiones
                // Generacion de nombre hash unico para evitar colisiones y problemas de encoding
                $nombre_foto = md5(time() . $_FILES['foto']['name']) . '.' . $ext;

                if (!move_uploaded_file($_FILES['foto']['tmp_name'], '../uploads/' . $nombre_foto)) {
                    $mensaje = "Fallo al mover el archivo al directorio de destino.";
                    $tipo_mensaje = "danger";
                    $error_subida = true;
                }
            } else {
                $mensaje = "Formato de archivo no soportado.";
                $tipo_mensaje = "warning";
                $error_subida = true;
            }
        } elseif ($_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Captura de errores de transferencia HTTP
            $mensaje = "Error en la carga del archivo. Codigo: " . $_FILES['foto']['error'];
            $tipo_mensaje = "danger";
            $error_subida = true;
        }

        // Persistencia Transaccional
        if (!$error_subida) {
            try {
                // Inicio de transaccion PDO para operaciones atomicas
                $pdo->beginTransaction();

                // Insercion en tabla principal
                $stmt = $pdo->prepare("INSERT INTO clientes (nombre, apellido, fecha_nacimiento, fotoPerfil) VALUES (:n, :a, :f, :p)");
                $stmt->execute([':n' => $nombre, ':a' => $apellido, ':f' => $fecha_nacimiento ?: null, ':p' => $nombre_foto]);
                $cid = $pdo->lastInsertId();

                // Insercion en tabla pivot (Relacion Muchos a Muchos)
                if (!empty($especialidades_seleccionadas)) {
                    $stmt_pivot = $pdo->prepare("INSERT INTO cliente_especialidad (cliente_id, especialidad_id) VALUES (:cid, :eid)");
                    foreach ($especialidades_seleccionadas as $esp_id) {
                        $stmt_pivot->execute([':cid' => $cid, ':eid' => $esp_id]);
                    }
                }

                // Confirmacion de cambios
                $pdo->commit();

                // Feedback exitoso via Sesion (Flash Message)
                $_SESSION['flash_msg'] = "Cliente registrado exitosamente en el sistema.";
                $_SESSION['flash_type'] = "success";
                header('Location: crear.php');
                exit;
            } catch (PDOException $e) {
                // Reversion de cambios en caso de error
                $pdo->rollBack();
                $mensaje = "Error de Base de Datos: " . $e->getMessage();
                $tipo_mensaje = "danger";
            }
        }
    }
}
require_once '../includes/header.php';
?>

<div class="container-fluid px-4 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Nuevo Cliente</h2>
        <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Volver
        </a>
    </div>
    <!-- Mensajes de error o informacion del proceso de creacion -->
    <?php if (isset($_SESSION['flash_msg'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show shadow-sm border-0 rounded-3">
            <?= h($_SESSION['flash_msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> shadow-sm border-0 rounded-3 d-flex align-items-center">
            <i class="fa-solid fa-circle-exclamation fa-lg me-2"></i>
            <div><strong>Atencion:</strong> <?= h($mensaje) ?></div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">

            <div class="text-end text-muted small fst-italic mb-3">
                Los campos marcados con <span class="text-danger">*</span> son obligatorios
            </div>

            <form action="crear.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">

                <div class="row g-4">

                    <div class="col-md-8">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" class="form-control bg-light border-0" value="<?= h($nombre) ?>" placeholder="Ej: Juan" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">Apellido <span class="text-danger">*</span></label>
                                <input type="text" name="apellido" class="form-control bg-light border-0" value="<?= h($apellido) ?>" placeholder="Ej: Perez" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Fecha de Nacimiento (Opcional)</label>
                            <input type="date" name="fecha_nacimiento" class="form-control bg-light border-0" value="<?= h($fecha_nacimiento) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Foto de Perfil (Opcional)</label>
                            <div class="p-3 border-2 border-dashed rounded-4 text-center bg-light">
                                <i class="fa-regular fa-image fa-2x text-muted mb-2"></i>
                                <input type="file" name="foto" class="form-control form-control-sm" accept="image/*">
                                <small class="text-muted d-block mt-2" style="font-size: 0.75rem;">Formatos: JPG, PNG, WEBP (Max 2MB)</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border border-light shadow-none h-100 rounded-4 bg-white">
                            <div class="card-header bg-white border-0 pt-3 pb-0">
                                <h6 class="fw-bold mb-0 text-dark">Especialidades <span class="text-danger">*</span></h6>
                            </div>
                            <div class="card-body">
                                <div class="vstack gap-1" style="max-height: 300px; overflow-y: auto;">
                                    <!-- Verificacion de existencia de especialidades y muestra de checkboxes activos -->
                                    <?php if (empty($lista_especialidades)): ?>
                                        <p class="text-muted small">No existen especialidades cargadas en el sistema.</p>
                                    <?php else: ?>
                                        <?php foreach ($lista_especialidades as $esp): ?>
                                            <?php
                                            // Logica Sticky: Mantener seleccion si hubo error en el envio
                                            $checked = (isset($_POST['especialidades']) && in_array($esp['id'], $_POST['especialidades'])) ? 'checked' : '';
                                            ?>

                                            <div class="p-2 rounded hover-bg-light pointer-wrapper">
                                                <div class="form-check">
                                                    <input class="form-check-input cursor-pointer" type="checkbox"
                                                        name="especialidades[]"
                                                        value="<?= $esp['id'] ?>"
                                                        id="esp_<?= $esp['id'] ?>"
                                                        <?= $checked ?>>
                                                    <label class="form-check-label w-100 cursor-pointer" for="esp_<?= $esp['id'] ?>">
                                                        <?= h($esp['nombre']) ?>
                                                    </label>
                                                </div>
                                            </div>

                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4 border-light">

                <div class="d-flex gap-2 justify-content-end">
                    <a href="index.php" class="btn btn-light text-secondary rounded-pill px-4 fw-bold">Cancelar</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Estilos personalizados para mejorar la experiencia de usuario */
    .cursor-pointer {
        cursor: pointer;
    }

    .border-dashed {
        border-style: dashed !important;
    }

    .hover-bg-light:hover {
        background-color: #f8f9fa;
    }
</style>

<?php require_once '../includes/footer.php'; ?>