<?php
// 1. Configuración
require_once '../includes/config.php';

// Si ya está logueado, al panel
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';

// ==============================================================================
// [SISTEMA RATE LIMIT] SIN BASE DE DATOS (Usa archivo temporal)
// ==============================================================================
$archivo_intentos = sys_get_temp_dir() . '/login_attempts_crm.json';
$ip_actual = $_SERVER['REMOTE_ADDR'];
$limite_intentos = 5;
$tiempo_bloqueo = 600; // 10 minutos en segundos

// Función auxiliar para leer/escribir el registro
function obtener_registro_intentos($archivo) {
    if (file_exists($archivo)) {
        $contenido = file_get_contents($archivo);
        return json_decode($contenido, true) ?? [];
    }
    return [];
}

// 1. Cargar registro y limpiar datos viejos (Garbage Collection)
$registro_ips = obtener_registro_intentos($archivo_intentos);
$ahora = time();
$cambios = false;

// Limpiamos IPs que ya expiraron hace más de 10 mins para que el archivo no pese una tonelada
foreach ($registro_ips as $ip => $datos) {
    if (($ahora - $datos['ultimo_intento']) > $tiempo_bloqueo) {
        unset($registro_ips[$ip]);
        $cambios = true;
    }
}

// 2. Verificar si la IP actual está bloqueada
$bloqueado = false;
$minutos_restantes = 0;

if (isset($registro_ips[$ip_actual])) {
    $intentos = $registro_ips[$ip_actual]['count'];
    $ultimo_tiempo = $registro_ips[$ip_actual]['ultimo_intento'];
    
    // Si superó intentos Y está dentro de la ventana de tiempo
    if ($intentos >= $limite_intentos && ($ahora - $ultimo_tiempo) < $tiempo_bloqueo) {
        $bloqueado = true;
        $segundos_restantes = $tiempo_bloqueo - ($ahora - $ultimo_tiempo);
        $minutos_restantes = ceil($segundos_restantes / 60);
        
        $error = "Acceso bloqueado temporalmente por seguridad. Demasiados intentos fallidos. Esperá $minutos_restantes minutos.";
    }
}

if ($cambios) {
    file_put_contents($archivo_intentos, json_encode($registro_ips));
}

// ==============================================================================
// [PROCESAMIENTO DEL FORMULARIO]
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$bloqueado) {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Error de seguridad: Token inválido.');
    }

    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    try {
        $sql = "SELECT id, nombre, email, pass_hash, rol, activo FROM usuarios WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['pass_hash'])) {
            
            if ($usuario['activo'] == 0) {
                $error = "Cuenta desactivada.";
            } else {
                // [ÉXITO] - Borramos la IP del registro de fallos (Limpiamos prontuario)
                if (isset($registro_ips[$ip_actual])) {
                    unset($registro_ips[$ip_actual]);
                    file_put_contents($archivo_intentos, json_encode($registro_ips));
                }

                session_regenerate_id(true);
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['nombre_usuario'] = $usuario['nombre'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['rol'] = $usuario['rol'];

                header('Location: ' . BASE_URL . '/index.php');
                exit;
            }

        } else {
            // [FALLO] - Registramos el intento en el archivo JSON
            $error = "Email o contraseña incorrectos.";
            
            // Actualizamos o creamos el registro para esta IP
            if (!isset($registro_ips[$ip_actual])) {
                $registro_ips[$ip_actual] = ['count' => 1, 'ultimo_intento' => time()];
            } else {
                $registro_ips[$ip_actual]['count']++;
                $registro_ips[$ip_actual]['ultimo_intento'] = time();
            }
            
            // Guardamos en disco
            file_put_contents($archivo_intentos, json_encode($registro_ips));
            
            // Chequeo inmediato post-fallo para mostrar mensaje de bloqueo si llegamos a 5 justo ahora
            if ($registro_ips[$ip_actual]['count'] >= $limite_intentos) {
                $bloqueado = true; // Para deshabilitar el form visualmente abajo
                $error = "Acceso bloqueado. Has superado los 5 intentos. Esperá 10 minutos.";
            }
        }

    } catch (PDOException $e) {
        $error = "Error de conexión.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-login {
            width: 100%;
            max-width: 400px;
            border-radius: 15px;
        }
    </style>
</head>
<body>

    <div class="card card-login shadow-lg">
        <div class="card-header bg-light text-center py-3" style="border-radius: 15px 15px 0 0;">
            <h3 class="mb-0 text-primary"><i class="fa-solid fa-shield-halved"></i> Acceso Seguro</h3>
        </div>
        
        <div class="card-body p-4">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>

            <fieldset <?php echo ($bloqueado) ? 'disabled' : ''; ?>>
                <form action="login.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">Entrar</button>
                    </div>
                </form>
            </fieldset>
        </div>
    </div>

</body>
</html>