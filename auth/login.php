<?php
// auth/login.php

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
function obtener_registro_intentos($archivo)
{
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

// Limpiamos IPs que ya expiraron hace más de 10 mins
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

        $error = "Acceso bloqueado temporalmente. Demasiados intentos fallidos. Esperá $minutos_restantes minutos.";
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
                $error = "Esta cuenta ha sido desactivada.";
            } else {
                // [ÉXITO] - Limpiamos prontuario
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
            // [FALLO] - Registramos intento
            $error = "Email o contraseña incorrectos.";

            if (!isset($registro_ips[$ip_actual])) {
                $registro_ips[$ip_actual] = ['count' => 1, 'ultimo_intento' => time()];
            } else {
                $registro_ips[$ip_actual]['count']++;
                $registro_ips[$ip_actual]['ultimo_intento'] = time();
            }

            file_put_contents($archivo_intentos, json_encode($registro_ips));

            // Chequeo inmediato post-fallo
            if ($registro_ips[$ip_actual]['count'] >= $limite_intentos) {
                $bloqueado = true;
                $error = "Acceso bloqueado. Has superado los 5 intentos. Esperá 10 minutos.";
            }
        }
    } catch (PDOException $e) {
        $error = "Error de conexión con la base de datos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - CRM Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            /* Fondo gris suave (Clean Style) */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            /* Sombra suave y elegante estilo SaaS */
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04);
            padding: 3rem;
            width: 100%;
            max-width: 400px;
            border: 1px solid #e9ecef;
        }

        /* Inputs estilo "Clean" (Igual que en crear.php) */
        .form-control {
            background-color: #f8f9fa;
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: #dee2e6;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
            /* Focus azul suave */
        }

        /* Botón Azul Estándar */
        .btn-primary {
            background-color: #0d6efd;
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }

        /* Ojito de contraseña */
        .input-group-text {
            background: transparent;
            border: none;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            cursor: pointer;
            color: #adb5bd;
        }

        .input-group-text:hover {
            color: #0d6efd;
        }

        .login-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.8rem;
            color: #adb5bd;
        }

        /* Si está bloqueado */
        fieldset[disabled] {
            opacity: 0.6;
        }
    </style>
</head>

<body>

    <div class="login-card">

        <div class="text-center mb-4">
            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3"
                style="width: 60px; height: 60px;">
                <i class="fa-solid fa-lock fa-xl text-primary"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1">Bienvenido</h4>
            <p class="text-muted small">Ingresá tus credenciales para acceder</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4 rounded-3 p-3">
                <i class="fa-solid fa-circle-exclamation me-2 fs-5"></i>
                <div class="small fw-medium"><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <fieldset <?php echo ($bloqueado) ? 'disabled' : ''; ?> style="border:none; padding:0; margin:0;">

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary ms-1" style="font-size: 0.75rem;">EMAIL</label>
                    <input type="email" class="form-control" name="email" placeholder="nombre@empresa.com" required>
                </div>

                <div class="mb-4 position-relative">
                    <label class="form-label small fw-bold text-secondary ms-1" style="font-size: 0.75rem;">CONTRASEÑA</label>
                    <div class="position-relative">
                        <input type="password" class="form-control" name="password" id="passInput" placeholder="••••••••" required>
                        <span class="input-group-text" onclick="togglePassword()">
                            <i class="fa-regular fa-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        Iniciar Sesión
                    </button>
                </div>
            </fieldset>
        </form>

        <div class="login-footer">
            <i class="fa-solid fa-shield-halved me-1"></i> Acceso Seguro CRM Pro
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('passInput');
            const icon = document.getElementById('eyeIcon');

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>