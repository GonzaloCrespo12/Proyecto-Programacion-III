<?php
// index.php
require_once 'includes/config.php';
require_once 'includes/require_login.php';

// Contadores
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM clientes WHERE activo = 1");
    $total_clientes = $stmt->fetchColumn();
} catch (PDOException $e) {
    $total_clientes = 0;
}

require_once 'includes/header.php';
?>

<style>
    /* Estilos específicos para las tarjetas del Index */
    .card-action {
        border: none;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
        text-align: center;
        padding: 3rem 2rem;
    }

    .card-action:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    /* Círculos de los íconos */
    .icon-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem auto;
        font-size: 2rem;
    }

    .bg-soft-green {
        background-color: #e0f2f1;
        color: #009688;
    }

    .bg-soft-blue {
        background-color: #e7f1ff;
        color: #0d6efd;
    }

    .card-title {
        font-weight: 700;
        font-size: 1.25rem;
        color: #2c3e50;
        margin-bottom: 1rem;
    }

    .card-text {
        color: #6c757d;
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 2rem;
    }
</style>

<div class="container-fluid px-0 mt-4">

    <?php if (isset($_SESSION['flash_msg'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show mb-4 shadow-sm border-0">
            <?= h($_SESSION['flash_msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-md-6">
            <div class="card card-action">
                <div class="icon-circle bg-soft-green">
                    <i class="fa-solid fa-users-viewfinder"></i>
                </div>
                <h3 class="card-title">Listado de Clientes</h3>
                <p class="card-text">
                    Accede a la base de datos completa. Consulta, edita o elimina registros
                    existentes y gestiona el estado de tus <strong><?= $total_clientes ?> clientes activos</strong>.
                </p>
                <a href="clientes/index.php" class="btn btn-outline-success rounded-pill px-4 fw-bold">
                    Ver base de datos
                </a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-action">
                <div class="icon-circle bg-soft-blue">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h3 class="card-title">Ingresar Nuevo Cliente</h3>
                <p class="card-text">
                    Da de alta un nuevo perfil en el sistema completando el formulario de registro.
                    Incluye validación automática de imágenes y datos.
                </p>
                <a href="clientes/crear.php" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
                    Comenzar registro
                </a>
            </div>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>