<?php
// 1. Incluimos la configuración maestra (Base de datos y Sesiones)
require_once '../includes/config.php'; 

// Variable para mensajes de retroalimentación (Feedback)
$mensaje = "";
$tipo_mensaje = ""; // success o danger

// --------------------------------------------------------------------------
// PROCESAMIENTO DEL FORMULARIO
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 2. Validación CSRF (Evitamos ataques cruzados)
    // Si el token no viaja o no coincide, cortamos todo.
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Error de seguridad: Token CSRF inválido.");
    }

    // 3. Sanitización y recolección de datos
    $nombre = trim($_POST['nombre']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $rol = 'operador'; // Forzamos que sea admin, ya que este script es para eso.

    // Validamos que no estén vacíos
    if (empty($nombre) || empty($email) || empty($password)) {
        $mensaje = "Por favor, completá todos los campos.";
        $tipo_mensaje = "danger";
    } else {
        try {
            // 4. HASHING DE CONTRASEÑA (CRÍTICO)
            // Nunca guardamos texto plano. password_hash() usa algoritmos fuertes (Bcrypt/Argon2).
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);

            // 5. Insertamos en la Base de Datos
            $sql = "INSERT INTO usuarios (nombre, email, pass_hash, rol, activo) 
                    VALUES (:nombre, :email, :pass_hash, :rol, 1)";
            
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':nombre' => $nombre,
                ':email' => $email,
                ':pass_hash' => $pass_hash,
                ':rol' => $rol
            ]);

            $mensaje = "¡Usuario Admin creado exitosamente! Ya podés borrar este archivo.";
            $tipo_mensaje = "success";

        } catch (PDOException $e) {
            // El código 23000 suele ser "Duplicate entry" (Email repetido)
            if ($e->getCode() == 23000) {
                $mensaje = "Ese email ya está registrado en el sistema.";
                $tipo_mensaje = "warning";
            } else {
                $mensaje = "Error técnico: " . $e->getMessage();
                $tipo_mensaje = "danger";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Primer Admin - Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="height: 100vh;">

    <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px;">
        <div class="text-center mb-3">
            <h3>🛠 Setup Inicial</h3>
            <p class="text-muted">Crear Super Usuario</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>" role="alert">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form action="crearAdmin.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="mb-3">
                <label class="form-label">Nombre Completo</label>
                <input type="text" name="nombre" class="form-control" required placeholder="Ej: Roberto Tech">
            </div>

            <div class="mb-3">
                <label class="form-label">Email (Usuario)</label>
                <input type="email" name="email" class="form-control" required placeholder="admin@sistema.com">
            </div>

            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required placeholder="Mínimo 6 caracteres">
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Crear Admin</button>
                
                <?php if($tipo_mensaje === 'success'): ?>
                    <a href="login.php" class="btn btn-outline-success">Ir al Login</a>
                <?php endif; ?>
            </div>
        </form>
        
        <div class="mt-3 text-center">
            <small class="text-danger fw-bold">
                ⚠️ Importante: Borrar este archivo al terminar.
            </small>
        </div>
    </div>

</body>
</html>