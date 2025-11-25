<?php

/**
 * clientes/editar.php
 * Modulo de Edicion y Actualizacion de Clientes.
 *
 * Este controlador maneja el ciclo de vida completo de la modificacion de un registro:
 * - Recuperacion del estado actual del cliente desde la base de datos.
 * - Renderizado del formulario con datos precargados.
 * - Procesamiento de la solicitud POST para actualizar informacion.
 * - Gestion de la carga/reemplazo/eliminacion de archivos (Foto de Perfil).
 * - Sincronizacion de relaciones(Especialidades).
 */

// Inclusiones Necesarias
require_once '../includes/config.php';
require_once '../includes/require_login.php';

// Validacion de integridad del identificador de recurso
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$mensaje = "";
$tipo_mensaje = "";

try {
    // Recuperacion de la entidad Cliente mediante sentencia preparada
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        die("Error: El recurso solicitado no existe.");
    }

    // Recuperacion de dependencias para el formulario (Catalogo de Especialidades)
    $todas_especialidades = $pdo->query("SELECT * FROM especialidades ORDER BY nombre ASC")->fetchAll();

    // Recuperacion de relaciones existentes para pre-llenado de checkboxes
    $stmt_esp = $pdo->prepare("SELECT especialidad_id FROM cliente_especialidad WHERE cliente_id = :id");
    $stmt_esp->execute([':id' => $id]);
    // Fetch en modo columna para obtener un array plano de IDs
    $mis_especialidades_ids = $stmt_esp->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Error critico de datos: " . $e->getMessage());
}

// Logica de Procesamiento de la Actualizacion (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verificacion de Token Anti-CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Error CSRF: Solicitud rechazada por seguridad.");
    }

    // Sanitizacion de inputs
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    // Especialidades seleccionadas en array
    $especialidades_seleccionadas = $_POST['especialidades'] ?? [];

    // Validacion de Reglas de Negocio
    if (empty($nombre) || empty($apellido)) {
        $mensaje = "Nombre y apellido son obligatorios.";
        $tipo_mensaje = "danger";
    } elseif (count($especialidades_seleccionadas) === 0) { // Al menos una especialidad
        $mensaje = "Debes seleccionar al menos una especialidad.";
        $tipo_mensaje = "warning";
        // Reset visual de especialidades en caso de error para forzar correccion
        $mis_especialidades_ids = [];
    } else {

        // Gestion del ciclo de vida de la Foto de Perfil
        $nombre_foto_final = $cliente['fotoPerfil'];
        $error_subida = false;

        // Caso A: Solicitud explicita de eliminacion de foto
        if (isset($_POST['borrar_foto']) && $_POST['borrar_foto'] == '1') {
            // Eliminacion fisica del archivo si existe
            if ($cliente['fotoPerfil'] && file_exists('../uploads/' . $cliente['fotoPerfil'])) {
                unlink('../uploads/' . $cliente['fotoPerfil']);
            }
            // Actualizacion de referencia en BD a NULL
            $nombre_foto_final = null;
        }

        // Caso B: Carga de nueva foto (Reemplazo)
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

            // Validacion de restricciones de archivo (Tamaño y Tipo MIME implicito por extension)
            if ($_FILES['foto']['size'] > 2097152) {
                $mensaje = "La foto excede el tamaño maximo permitido (2MB).";
                $tipo_mensaje = "warning";
                $error_subida = true;
            } elseif (in_array($ext, ['jpg', 'png', 'jpeg', 'webp'])) {
                $newFileName = md5(time() . $_FILES['foto']['name']) . '.' . $ext;

                if (move_uploaded_file($_FILES['foto']['tmp_name'], '../uploads/' . $newFileName)) {
                    // Limpieza de basura: Borrar foto anterior si fue reemplazada y no borrada antes
                    if ($nombre_foto_final !== null && $nombre_foto_final !== $newFileName && file_exists('../uploads/' . $nombre_foto_final)) {
                        unlink('../uploads/' . $nombre_foto_final);
                    }
                    $nombre_foto_final = $newFileName;
                } else {
                    $mensaje = "Fallo al mover el archivo al servidor.";
                    $tipo_mensaje = "danger";
                    $error_subida = true;
                }
            } else {
                $mensaje = "Formato de archivo no valido.";
                $tipo_mensaje = "warning";
                $error_subida = true;
            }
        }

        // Persistencia de Datos si no hubo errores en la subida
        if (!$error_subida) {
            try {
                $pdo->beginTransaction();

                // Actualizacion de datos principales del cliente
                $sql = "UPDATE clientes SET nombre=:n, apellido=:a, fecha_nacimiento=:f, fotoPerfil=:p WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':n' => $nombre,
                    ':a' => $apellido,
                    ':f' => $fecha_nacimiento ?: null,
                    ':p' => $nombre_foto_final,
                    ':id' => $id
                ]);

                // Actualizacion de Relaciones"
                // 1. Eliminar todas las asociaciones previas
                $pdo->prepare("DELETE FROM cliente_especialidad WHERE cliente_id = :id")->execute([':id' => $id]);

                // 2. Insertar las nuevas selecciones
                if (!empty($especialidades_seleccionadas)) {
                    $stmt_pivot = $pdo->prepare("INSERT INTO cliente_especialidad (cliente_id, especialidad_id) VALUES (:cid, :eid)");
                    foreach ($especialidades_seleccionadas as $esp_id) {
                        $stmt_pivot->execute([':cid' => $id, ':eid' => $esp_id]);
                    }
                }

                $pdo->commit();

                // Notificacion Flash y Redireccion
                $_SESSION['flash_msg'] = "Cliente actualizado correctamente.";
                $_SESSION['flash_type'] = "success";
                header('Location: index.php');
                exit;
            } catch (PDOException $e) {
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
        <h2 class="fw-bold text-dark mb-0">Editar Cliente</h2>
        <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> Volver
        </a>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> shadow-sm border-0 rounded-3 d-flex align-items-center mb-4">
            <i class="fa-solid fa-circle-exclamation fa-lg me-2"></i>
            <div><strong>Atencion:</strong> <?= h($mensaje) ?></div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">

            <div class="text-end text-muted small fst-italic mb-3">
                Los campos con <span class="text-danger">*</span> son obligatorios
            </div>

            <form action="editar.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">

                <div class="row g-4">
                    <div class="col-md-8">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" class="form-control bg-light border-0"
                                    value="<?= h($cliente['nombre']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">Apellido <span class="text-danger">*</span></label>
                                <input type="text" name="apellido" class="form-control bg-light border-0"
                                    value="<?= h($cliente['apellido']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control bg-light border-0"
                                value="<?= h($cliente['fecha_nacimiento']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Foto de Perfil</label>
                            <div class="d-flex align-items-center gap-4 p-3 border rounded-4 bg-light">
                                <div class="position-relative">
                                    <!-- verificar si hay foto o no -->
                                    <?php if ($cliente['fotoPerfil']): ?>
                                        <img src="../uploads/<?= h($cliente['fotoPerfil']) ?>" width="80" height="80"
                                            class="rounded-circle border shadow-sm object-fit-cover bg-white">
                                    <?php else: ?>
                                        <div class="rounded-circle border bg-white d-flex align-items-center justify-content-center text-muted fw-bold"
                                            style="width: 80px; height: 80px; font-size: 1.5rem;">
                                            <?= h(strtoupper(substr($cliente['nombre'], 0, 1))) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="flex-grow-1">
                                    <input type="file" name="foto" class="form-control form-control-sm mb-2" accept="image/*">

                                    <?php if ($cliente['fotoPerfil']): ?>
                                        <div class="form-check mt-1">
                                            <input class="form-check-input border-danger" type="checkbox" name="borrar_foto" value="1" id="check_borrar">
                                            <label class="form-check-label text-danger fw-bold small cursor-pointer" for="check_borrar">
                                                <i class="fa-solid fa-trash-can me-1"></i> Eliminar foto actual
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                </div>
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
                                    <?php foreach ($todas_especialidades as $esp): ?>
                                        <?php
                                        // Logica de estado 'checked':
                                        // 1. Si es POST (hubo error), mantengo la seleccion del usuario.
                                        // 2. Si es carga inicial, marco las que vienen de la BD.
                                        $checked = '';
                                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                                            if (in_array($esp['id'], $especialidades_seleccionadas)) $checked = 'checked';
                                        } else {
                                            if (in_array($esp['id'], $mis_especialidades_ids)) $checked = 'checked';
                                        }
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4 border-light">

                <div class="d-flex gap-2 justify-content-end">
                    <a href="index.php" class="btn btn-light text-secondary rounded-pill px-4 fw-bold">Cancelar</a>
                    <button type="submit" class="btn btn-warning text-white rounded-pill px-5 fw-bold shadow-sm">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .hover-bg-light:hover {
        background-color: #f8f9fa;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    .object-fit-cover {
        object-fit: cover;
    }
</style>

<?php require_once '../includes/footer.php'; ?>