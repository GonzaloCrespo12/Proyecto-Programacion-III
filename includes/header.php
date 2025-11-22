<?php
// Verificamos sesión por seguridad (aunque ya debería venir del config)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// NOTA: Acá ya NO definimos $base_url, porque asumimos que
// quien incluya este header (index.php, crear.php, etc)
// YA incluyó primero el archivo 'includes/config.php' donde está la constante BASE_URL.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Clientes - Roberto El Titán</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Estilos mínimos para el Sidebar */
        #sidebar-wrapper {
            min-height: 100vh;
            width: 250px;
            background-color: #212529; /* bg-dark */
        }
        #page-content-wrapper {
            width: 100%;
            padding: 20px;
        }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">

    <div class="text-white" id="sidebar-wrapper">
        <div class="sidebar-heading text-center py-4 fs-4 fw-bold border-bottom border-secondary">
            <i class="fa-solid fa-layer-group"></i> CRM V1
        </div>
        
        <div class="p-3 text-center border-bottom border-secondary bg-secondary bg-opacity-25">
            <div class="mb-1">
                <i class="fa-solid fa-circle-user fa-2x"></i>
            </div>
            <h6 class="m-0"><?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario Test'); ?></h6>
            <small class="text-info fst-italic">
                <?php echo htmlspecialchars(ucfirst($_SESSION['rol'] ?? 'Invitado')); ?>
            </small>
        </div>

        <div class="list-group list-group-flush my-3">
            <a href="<?php echo BASE_URL; ?>/index.php" class="list-group-item list-group-item-action bg-transparent text-white border-0">
                <i class="fa-solid fa-house me-2"></i> Inicio
            </a>
            
            <a href="<?php echo BASE_URL; ?>/clientes/index.php" class="list-group-item list-group-item-action bg-transparent text-white border-0">
                <i class="fa-solid fa-users me-2"></i> Ver Clientes
            </a>
            
            <a href="<?php echo BASE_URL; ?>/clientes/crear.php" class="list-group-item list-group-item-action bg-transparent text-white border-0">
                <i class="fa-solid fa-user-plus me-2"></i> Nuevo Cliente
            </a>
            
            <div class="mt-5 px-3">
                <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="btn btn-danger w-100">
                    <i class="fa-solid fa-power-off me-2"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
    <div id="page-content-wrapper" class="bg-light">