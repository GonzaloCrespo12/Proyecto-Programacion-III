<?php
// 1. Configuración y Seguridad
require_once '../includes/config.php';

// Verificamos Login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Obtenemos el ID de la URL
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$mensaje = "";
$tipo_mensaje = "";

// --------------------------------------------------------------------------
// [GET] CARGAR DATOS ACTUALES DEL CLIENTE
// --------------------------------------------------------------------------
try {
    // A) Datos del Cliente
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        die("El cliente no existe.");
    }

    // B) Catálogo de Especialidades (Todas las disponibles)
    $stmt_cat = $pdo->query("SELECT * FROM especialidades ORDER BY nombre ASC");
    $todas_especialidades = $stmt_cat->fetchAll();

    // C) Especialidades QUE YA TIENE el cliente
    // Usamos FETCH_COLUMN para obtener un array simple tipo [1, 3, 5]
    $stmt_mis_esp = $pdo->prepare("SELECT especialidad_id FROM cliente_especialidad WHERE cliente_id = :id");
    $stmt_mis_esp->execute([':id' => $id]);
    $mis_especialidades_ids = $stmt_mis_esp->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Error de carga: " . $e->getMessage());
}

// --------------------------------------------------------------------------
// [POST] PROCESAR ACTUALIZACIÓN
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF Check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Error de seguridad CSRF.");
    }

    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $especialidades_seleccionadas = $_POST['especialidades'] ?? [];

    if (empty($nombre) || empty($apellido)) {
        $mensaje = "Nombre y Apellido son obligatorios.";
        $tipo_mensaje = "danger";
    } else {
        
        // LÓGICA DE LA FOTO (Lo más delicado)
        // Mantenemos la foto vieja por defecto
        $nombre_foto_final = $cliente['fotoPerfil']; 
        $hubo_upload = false;

        // Si el usuario subió una NUEVA foto...
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['foto']['tmp_name'];
            $fileName = $_FILES['foto']['name'];
            $fileType = $_FILES['foto']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');

            if (in_array($fileExtension, $allowedfileExtensions)) {
                // 1. Subimos la nueva
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = '../uploads/';
                $dest_path = $uploadFileDir . $newFileName;

                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $nombre_foto_final = $newFileName; // Actualizamos la variable para la BD
                    $hubo_upload = true;
                    
                    // 2. Borramos la vieja del disco (Limpieza)
                    if ($cliente['fotoPerfil'] && file_exists($uploadFileDir . $cliente['fotoPerfil'])) {
                        unlink($uploadFileDir . $cliente['fotoPerfil']);
                    }
                } else {
                    $mensaje = "Error al mover la nueva imagen.";
                    $tipo_mensaje = "danger";
                }
            } else {
                $mensaje = "Formato de imagen inválido.";
                $tipo_mensaje = "warning";
            }
        }

        // Si no hubo errores de upload, guardamos en BD
        if (empty($mensaje)) {
            try {
                $pdo->beginTransaction();

                // 1. Update Tabla Clientes
                $sql = "UPDATE clientes SET 
                            nombre = :nombre, 
                            apellido = :apellido, 
                            fecha_nacimiento = :fecha, 
                            fotoPerfil = :foto 
                        WHERE id = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':apellido' => $apellido,
                    ':fecha' => $fecha_nacimiento ?: null,
                    ':foto' => $nombre_foto_final,
                    ':id' => $id
                ]);

                // 2. Sincronización de Especialidades (Delete All + Insert New)
                // Primero borramos las que tenía antes
                $stmt_del = $pdo->prepare("DELETE FROM cliente_especialidad WHERE cliente_id = :id");
                $stmt_del->execute([':id' => $id]);

                // Después insertamos las que seleccionó ahora
                if (!empty($especialidades_seleccionadas)) {
                    $sql_pivot = "INSERT INTO cliente_especialidad (cliente_id, especialidad_id) VALUES (:cid, :eid)";
                    $stmt_pivot = $pdo->prepare($sql_pivot);
                    foreach ($especialidades_seleccionadas as $esp_id) {
                        $stmt_pivot->execute([':cid' => $id, ':eid' => $esp_id]);
                    }
                }

                $pdo->commit();
                
                // Actualizamos los datos en memoria para que se refresque el form sin recargar
                $cliente['nombre'] = $nombre;
                $cliente['apellido'] = $apellido;
                $cliente['fecha_nacimiento'] = $fecha_nacimiento;
                $cliente['fotoPerfil'] = $nombre_foto_final;
                // Recargamos los IDs de especialidades
                $mis_especialidades_ids = $especialidades_seleccionadas; 

                $mensaje = "¡Datos actualizados correctamente!";
                $tipo_mensaje = "success";

            } catch (PDOException $e) {
                $pdo->rollBack();
                $mensaje = "Error al guardar: " . $e->getMessage();
                $tipo_mensaje = "danger";
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="mt-4">Editar Cliente</h1>
    <p class="text-muted">Modificá los datos del perfil.</p>
    <hr>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-5">
        <div class="card-body">
            <form action="editar.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="row">
                    <div class="col-md-8">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" class="form-control" 
                                       value="<?php echo htmlspecialchars($cliente['nombre']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido *</label>
                                <input type="text" name="apellido" class="form-control" 
                                       value="<?php echo htmlspecialchars($cliente['apellido']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control" 
                                   value="<?php echo $cliente['fecha_nacimiento']; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Foto de Perfil</label>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <?php if($cliente['fotoPerfil']): ?>
                                    <img src="../uploads/<?php echo $cliente['fotoPerfil']; ?>" 
                                         alt="Actual" class="rounded-circle border" width="60" height="60" style="object-fit:cover;">
                                    <small class="text-muted">Foto actual</small>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Sin foto</span>
                                <?php endif; ?>
                            </div>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                            <div class="form-text">Dejar vacío para mantener la foto actual.</div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><i class="fa-solid fa-code"></i> Especialidades</h5>
                                <div class="vstack gap-2">
                                    <?php foreach ($todas_especialidades as $esp): ?>
                                        <?php 
                                            // Truco: Verificamos si el ID está en el array de las que tiene el usuario
                                            // Ojo: in_array a veces es estricto con strings vs ints, pero acá suele andar bien.
                                            $checked = in_array($esp['id'], $mis_especialidades_ids) ? 'checked' : '';
                                        ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="especialidades[]" 
                                                   value="<?php echo $esp['id']; ?>" 
                                                   id="esp_<?php echo $esp['id']; ?>"
                                                   <?php echo $checked; ?>>
                                            <label class="form-check-label" for="esp_<?php echo $esp['id']; ?>">
                                                <?php echo htmlspecialchars($esp['nombre']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-warning btn-lg text-dark fw-bold">
                        <i class="fa-solid fa-save"></i> Actualizar Datos
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-lg">Volver</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; 