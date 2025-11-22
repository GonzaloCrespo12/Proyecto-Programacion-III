<?php
// 1. Configuración
require_once 'includes/config.php';

// [SEGURIDAD TEMPORAL]
// Descomentar cuando tengamos el login listo
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

// 2. Header (Sidebar)
require_once 'includes/header.php';
?>

<div class="container-fluid">
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-primary shadow-sm" role="alert">
                <h4 class="alert-heading"><i class="fa-solid fa-hand-sparkles"></i> ¡Bienvenido al Panel!</h4>
                <p>Estás conectado al sistema de gestión de clientes. Desde acá podés administrar toda la cartera de usuarios.</p>
                <hr>
                <p class="mb-0">Seleccioná una opción para empezar.</p>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100 border-0">
                <div class="card-body text-center py-5">
                    <div class="mb-3 text-primary">
                        <i class="fa-solid fa-users-viewfinder fa-4x"></i>
                    </div>
                    <h3 class="card-title">Listado de Clientes</h3>
                    <p class="card-text text-muted">Consultá, editá o eliminá los clientes registrados en la base de datos.</p>
                    <a href="clientes/index.php" class="btn btn-outline-primary btn-lg mt-3">
                        Ver Clientes
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow h-100 border-0">
                <div class="card-body text-center py-5">
                    <div class="mb-3 text-success">
                        <i class="fa-solid fa-user-plus fa-4x"></i>
                    </div>
                    <h3 class="card-title">Ingresar Cliente</h3>
                    <p class="card-text text-muted">Da de alta un nuevo perfil completando el formulario de registro.</p>
                    <a href="clientes/crear.php" class="btn btn-success btn-lg mt-3">
                        <i class="fa-solid fa-plus"></i> Cargar Nuevo
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
<?php
require_once 'includes/footer.php';
?>