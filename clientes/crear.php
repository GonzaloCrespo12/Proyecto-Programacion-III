<?php
// 1. Configuración y Seguridad
require_once '../includes/config.php';

// Verificamos si está logueado (Seguridad básica)
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$mensaje = "";
$tipo_mensaje = "";

// 2. Obtener Especialidades para el formulario (Checkboxes)
try {
    $stmt = $pdo->query("SELECT * FROM especialidades ORDER BY nombre ASC");
    $lista_especialidades = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al cargar especialidades: " . $e->getMessage());
}

// --------------------------------------------------------------------------
// [LÓGICA] PROCESAMIENTO DEL POST
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validación CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Error de seguridad CSRF.");
    }

    // Recolección de datos (Sin Email)
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $especialidades_seleccionadas = $_POST['especialidades'] ?? []; // Array de IDs

    // Validaciones básicas (Solo Nombre y Apellido requeridos)
    if (empty($nombre) || empty($apellido)) {
        $mensaje = "Nombre y apellido son obligatorios.";
        $tipo_mensaje = "danger";
    } else {
        
        // -----------------------------------------------------------
        // [PROCESO] SUBIDA DE IMAGEN
        // -----------------------------------------------------------
        $nombre_foto = null; 
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['foto']['tmp_name'];
            $fileName = $_FILES['foto']['name'];
            $fileType = $_FILES['foto']['type'];
            
            // Separamos extensión
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');
            
            if (in_array($fileExtension, $allowedfileExtensions)) {
                // Nombre único
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                
                // Ruta de destino
                $uploadFileDir = '../uploads/';
                $dest_path = $uploadFileDir . $newFileName;
                
                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $nombre_foto = $newFileName;
                } else {
                    $mensaje = "Error al guardar la imagen en el servidor.";
                    $tipo_mensaje = "danger";
                }
            } else {
                $mensaje = "Formato inválido. Solo JPG, PNG, GIF, WEBP.";
                $tipo_mensaje = "warning";
            }
        }

        // Si no hubo error bloqueante, guardamos en BD
        if (empty($mensaje)) {
            try {
                // -------------------------------------------------------
                // [DB] INICIO DE TRANSACCIÓN
                // -------------------------------------------------------
                $pdo->beginTransaction();

                // 1. Insertamos al Cliente (SIN EMAIL)
                $sql = "INSERT INTO clientes (nombre, apellido, fecha_nacimiento, fotoPerfil) 
                        VALUES (:nombre, :apellido, :fecha, :foto)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':apellido' => $apellido,
                    ':fecha' => $fecha_nacimiento ?: null,
                    ':foto' => $nombre_foto
                ]);

                // ID del nuevo cliente
                $cliente_id = $pdo->lastInsertId();

                // 2. Insertamos las Especialidades
                if (!empty($especialidades_seleccionadas)) {
                    $sql_pivot = "INSERT INTO cliente_especialidad (cliente_id, especialidad_id) VALUES (:cid, :eid)";
                    $stmt_pivot = $pdo->prepare($sql_pivot);

                    foreach ($especialidades_seleccionadas as $esp_id) {
                        $stmt_pivot->execute([
                            ':cid' => $cliente_id,
                            ':eid' => $esp_id
                        ]);
                    }
                }

                // [DB] CONFIRMAR CAMBIOS
                $pdo->commit();

                $mensaje = "¡Cliente registrado con éxito!";
                $tipo_mensaje = "success";
                
                // Opcional: Redirigir
                // header("refresh:2;url=index.php");

            } catch (PDOException $e) {
                // [DB] REVERTIR CAMBIOS
                $pdo->rollBack();
                $mensaje = "Error de Base de Datos: " . $e->getMessage();
                $tipo_mensaje = "danger";
            }
        }
    }
}

// 3. Incluimos el Header
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="mt-4">Registrar Nuevo Cliente</h1>
    <p class="text-muted">Completá el formulario para dar de alta un perfil.</p>
    <hr>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensaje; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-5">
        <div class="card-body">
            <form action="crear.php" method="POST" enctype="multipart/form-data">
                
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="row">
                    <div class="col-md-8">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido *</label>
                                <input type="text" name="apellido" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Foto de Perfil</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                            <div class="form-text">Formatos: JPG, PNG, WEBP. Máx 2MB.</div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h5 class="card-title mb-3"><i class="fa-solid fa-code"></i> Especialidades</h5>
                                <p class="small text-muted">Seleccioná las áreas técnicas:</p>
                                
                                <div class="vstack gap-2">
                                    <?php foreach ($lista_especialidades as $esp): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="especialidades[]" 
                                                   value="<?php echo $esp['id']; ?>" 
                                                   id="esp_<?php echo $esp['id']; ?>">
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
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-save"></i> Guardar Cliente
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-lg">Cancelar</a>
                </div>

            </form>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>