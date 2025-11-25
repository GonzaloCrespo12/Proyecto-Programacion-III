<?php
// clientes/editar.php
require_once '../includes/config.php';
require_once '../includes/require_login.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}
$mensaje = "";
$tipo_mensaje = "";

try {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $cliente = $stmt->fetch();
    if (!$cliente) die("Cliente no encontrado.");

    $todas_especialidades = $pdo->query("SELECT * FROM especialidades ORDER BY nombre ASC")->fetchAll();
    $stmt_esp = $pdo->prepare("SELECT especialidad_id FROM cliente_especialidad WHERE cliente_id = :id");
    $stmt_esp->execute([':id' => $id]);
    $mis_especialidades_ids = $stmt_esp->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("Error CSRF.");

    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $especialidades_seleccionadas = $_POST['especialidades'] ?? [];

    if (empty($nombre) || empty($apellido)) {
        $mensaje = "Nombre y apellido son obligatorios.";
        $tipo_mensaje = "danger";
    } elseif (count($especialidades_seleccionadas) === 0) {
        $mensaje = "Debés seleccionar al menos una especialidad.";
        $tipo_mensaje = "warning";
        $mis_especialidades_ids = [];
    } else {
        $nombre_foto_final = $cliente['fotoPerfil'];
        $error_subida = false;

        if (isset($_POST['borrar_foto']) && $_POST['borrar_foto'] == '1') {
            if ($cliente['fotoPerfil'] && file_exists('../uploads/' . $cliente['fotoPerfil'])) unlink('../uploads/' . $cliente['fotoPerfil']);
            $nombre_foto_final = null;
        }

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if ($_FILES['foto']['size'] > 2097152) {
                $mensaje = "Foto muy pesada (Max 2MB).";
                $tipo_mensaje = "warning";
                $error_subida = true;
            } elseif (in_array($ext, ['jpg', 'png', 'jpeg', 'webp'])) {
                $newFileName = md5(time() . $_FILES['foto']['name']) . '.' . $ext;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], '../uploads/' . $newFileName)) {
                    if ($nombre_foto_final !== null && $nombre_foto_final !== $newFileName && file_exists('../uploads/' . $nombre_foto_final)) {
                        unlink('../uploads/' . $nombre_foto_final);
                    }
                    $nombre_foto_final = $newFileName;
                } else {
                    $mensaje = "Error al mover archivo.";
                    $tipo_mensaje = "danger";
                    $error_subida = true;
                }
            } else {
                $mensaje = "Formato inválido.";
                $tipo_mensaje = "warning";
                $error_subida = true;
            }
        }

        if (!$error_subida) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE clientes SET nombre=:n, apellido=:a, fecha_nacimiento=:f, fotoPerfil=:p WHERE id=:id");
                $stmt->execute([':n' => $nombre, ':a' => $apellido, ':f' => $fecha_nacimiento ?: null, ':p' => $nombre_foto_final, ':id' => $id]);

                $pdo->prepare("DELETE FROM cliente_especialidad WHERE cliente_id = :id")->execute([':id' => $id]);
                if (!empty($especialidades_seleccionadas)) {
                    $stmt_p = $pdo->prepare("INSERT INTO cliente_especialidad (cliente_id, especialidad_id) VALUES (:cid, :eid)");
                    foreach ($especialidades_seleccionadas as $esp_id) $stmt_p->execute([':cid' => $id, ':eid' => $esp_id]);
                }
                $pdo->commit();
                $_SESSION['flash_msg'] = "¡Cliente actualizado!";
                $_SESSION['flash_type'] = "success";
                header('Location: index.php');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $mensaje = "Error DB: " . $e->getMessage();
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
            <div><strong>Atención:</strong> <?= h($mensaje) ?></div>
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
                                <input type="text" name="nombre" class="form-control bg-light border-0" value="<?= h($cliente['nombre']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">Apellido <span class="text-danger">*</span></label>
                                <input type="text" name="apellido" class="form-control bg-light border-0" value="<?= h($cliente['apellido']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary">Fecha de Nacimiento(opcional)</label>
                            <input type="date" name="fecha_nacimiento" class="form-control bg-light border-0" value="<?= h($cliente['fecha_nacimiento']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Foto de Perfil</label>
                            <div class="d-flex align-items-center gap-4 p-3 border rounded-4 bg-light">
                                <div class="position-relative">
                                    <?php if ($cliente['fotoPerfil']): ?>
                                        <img src="../uploads/<?= h($cliente['fotoPerfil']) ?>" width="80" height="80" class="rounded-circle border shadow-sm object-fit-cover bg-white">
                                    <?php else: ?>
                                        <div class="rounded-circle border bg-white d-flex align-items-center justify-content-center text-muted fw-bold" style="width: 80px; height: 80px; font-size: 1.5rem;">
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