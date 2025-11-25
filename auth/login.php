<?php

/**
 * auth/login.php
 * Modulo de Autenticacion de Usuarios.
 * Maneja el inicio de sesion, proteccion contra fuerza bruta (Rate Limiting)
 * y gestion de sesiones seguras.
 */

//  Configuracion e Inicializacion
require_once '../includes/config.php';

// Control de Sesion: Redireccionar al dashboard si el usuario ya está autenticado.
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';

// ==============================================================================
// SISTEMA DE RATE LIMITING (Proteccion contra Fuerza Bruta)
// Almacenamiento basado en archivo JSON temporal para bloquear IPs recurrentes.

$archivo_intentos = sys_get_temp_dir() . '/login_attempts_crm.json';
$ip_actual = $_SERVER['REMOTE_ADDR'];
$limite_intentos = 5;
$tiempo_bloqueo = 600; // Duracion del bloqueo en segundos (10 minutos)

/**
 * Recupera el registro de intentos de inicio de sesión.
 * @param string $archivo Ruta del archivo JSON.
 * @return array Datos decodificados o array vacío.
 */
function obtener_registro_intentos($archivo)
{
    if (file_exists($archivo)) {
        $contenido = file_get_contents($archivo);
        return json_decode($contenido, true) ?? [];
    }
    return [];
}

// Carga de registros y Garbage Collection (Limpieza de datos obsoletos)
$registro_ips = obtener_registro_intentos($archivo_intentos);
$ahora = time();
$cambios = false;

// Eliminar registros de IPs cuyo tiempo de bloqueo ha expirado
foreach ($registro_ips as $ip => $datos) {
    if (($ahora - $datos['ultimo_intento']) > $tiempo_bloqueo) {
        unset($registro_ips[$ip]);
        $cambios = true;
    }
}

// Verificación de estado de bloqueo para la IP actual
$bloqueado = false;
$minutos_restantes = 0;

if (isset($registro_ips[$ip_actual])) {
    $intentos = $registro_ips[$ip_actual]['count'];
    $ultimo_tiempo = $registro_ips[$ip_actual]['ultimo_intento'];

    // Condicion de bloqueo: Supero intentos maximos dentro de la ventana de tiempo
    if ($intentos >= $limite_intentos && ($ahora - $ultimo_tiempo) < $tiempo_bloqueo) {
        $bloqueado = true;
        $segundos_restantes = $tiempo_bloqueo - ($ahora - $ultimo_tiempo);
        $minutos_restantes = ceil($segundos_restantes / 60);

        $error = "Acceso bloqueado temporalmente por seguridad. Espere $minutos_restantes minutos.";
    }
}

// Persistencia de cambios en el archivo temporal
if ($cambios) {
    file_put_contents($archivo_intentos, json_encode($registro_ips));
}

// PROCESAMIENTO DE LA SOLICITUD DE LOGIN (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$bloqueado) {

    // Validacion de Token CSRF para prevenir ataques Cross-Site Request Forgery
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Error de seguridad: Token CSRF inválido.');
    }

    // Sanitizacion de entrada de datos
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    try {
        // Consulta preparada para prevenir Inyeccion SQL
        $sql = "SELECT id, nombre, email, pass_hash, rol, activo FROM usuarios WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        // Verificacion de contraseña mediante hash
        if ($usuario && password_verify($password, $usuario['pass_hash'])) {

            if ($usuario['activo'] == 0) {
                $error = "Esta cuenta ha sido desactivada por el administrador.";
            } else {
                // Autenticacion Exitosa: Limpiar registro de intentos fallidos
                if (isset($registro_ips[$ip_actual])) {
                    unset($registro_ips[$ip_actual]);
                    file_put_contents($archivo_intentos, json_encode($registro_ips));
                }

                // Regenerar ID de sesion para prevenir Session Fixation
                session_regenerate_id(true);

                // Inicializar variables de sesion
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['nombre_usuario'] = $usuario['nombre'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['rol'] = $usuario['rol'];

                header('Location: ' . BASE_URL . '/index.php');
                exit;
            }
        } else {
            // Autenticacion Fallida: Registrar intento y actualizar contador
            $error = "Credenciales incorrectas.";

            if (!isset($registro_ips[$ip_actual])) {
                $registro_ips[$ip_actual] = ['count' => 1, 'ultimo_intento' => time()];
            } else {
                $registro_ips[$ip_actual]['count']++;
                $registro_ips[$ip_actual]['ultimo_intento'] = time();
            }

            file_put_contents($archivo_intentos, json_encode($registro_ips));

            // Verificacion inmediata post-intento
            if ($registro_ips[$ip_actual]['count'] >= $limite_intentos) {
                $bloqueado = true;
                $error = "Acceso bloqueado por seguridad. Superó el límite de intentos.";
            }
        }
    } catch (PDOException $e) {
        $error = "Error de conexión con el servicio de datos.";
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
        /* Estilos UI del Login */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04);
            padding: 3rem;
            width: 100%;
            max-width: 400px;
            border: 1px solid #e9ecef;
        }

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
        }

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