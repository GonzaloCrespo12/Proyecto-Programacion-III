<?php

/**
 * index.php
 * Dashboard Principal (Tablero de Control).
 *
 * Punto de entrada a la aplicacion post-login.
 * Responsabilidades:
 * 1. Verificar la sesion activa del usuario.
 * 2. Calcular metricas clave (KPIs) para mostrar el estado del sistema.
 * 3. Proveer accesos directos a las funcionalidades principales (CRUD Clientes).
 */

require_once 'includes/config.php';
require_once 'includes/require_login.php';

// ============================================================================
// LOGICA DE NEGOCIO: CALCULO DE METRICAS
// ============================================================================

try {
    // 1. Total de Clientes Activos
    $stmt = $pdo->query("SELECT COUNT(*) FROM clientes WHERE activo = 1");
    $total_clientes = $stmt->fetchColumn();
} catch (PDOException $e) {
    $total_clientes = 0;
}

require_once 'includes/header.php';
?>

<style>
    /* Tarjetas de Accion Principal */
    .card-action {
        border: none;
        border-radius: 16px;
        background: var(--card-bg, #ffffff);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;

        /* FIX DE ALINEACION: Flexbox vertical */
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        /* Centra todo horizontalmente */

        text-align: center;
        padding: 3rem 2rem;
    }

    .card-action:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .icon-circle {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem auto;
        /* Reduje un poco el margen inferior */
        font-size: 2.5rem;
        flex-shrink: 0;
        /* Evita que el icono se achique */
    }

    .bg-soft-green {
        background-color: var(--icon-green-bg, #e0f2f1);
        color: var(--icon-green-color, #009688);
    }

    .bg-soft-blue {
        background-color: var(--icon-blue-bg, #e7f1ff);
        color: var(--icon-blue-color, #0d6efd);
    }

    .card-title {
        font-weight: 800;
        font-size: 1.5rem;
        color: var(--card-title-color, #2c3e50);
        margin-bottom: 1rem;
    }

    .card-text {
        color: var(--card-text-color, #6c757d);
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 2rem;
        width: 100%;
        /* Asegura ancho total para centrado */
    }

    /* Numero destacado */
    .highlight-number {
        font-size: 3.5rem;
        /* Un poco mas grande para impacto */
        font-weight: 800;
        letter-spacing: -2px;
        line-height: 1;
        margin: 1rem 0 0.5rem 0;
        /* Margenes equilibrados */
        display: block;
    }

    /* Header Hero Ajustado */
    .header-hero {
        margin-top: 0;
        /* Quitamos margen extra */
        margin-bottom: 2rem;
    }

    .header-hero .brand-main {
        color: var(--brand-blue, #0d6efd);
        text-transform: uppercase;
        letter-spacing: 0.6px;
        text-shadow: 0 4px 10px rgba(13, 110, 253, 0.08);
    }

    .header-hero .brand-pro {
        color: #0b0b0b;
        text-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
    }
</style>

<div class="container-fluid px-0 mt-2">

    <?php if (isset($_SESSION['flash_msg'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show mb-4 shadow-sm border-0 rounded-3">
            <?= h($_SESSION['flash_msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="text-center header-hero">
        <h1 class="display-5 fw-bold mb-1"><span class="brand-main">CRUD MANAGER</span> <span class="brand-pro">Pro</span></h1>
        <p class="lead text-muted">Sistema Integral de Gestion de Clientes</p>
    </div>

    <div class="row g-4 justify-content-center">

        <div class="col-md-6 col-lg-5">
            <div class="card card-action">
                <div class="icon-circle bg-soft-green">
                    <i class="fa-solid fa-users-viewfinder"></i>
                </div>
                <h3 class="card-title">Listado de Clientes</h3>
                <div class="card-text">
                    Accede a la base de datos completa y gestiona el estado de tu cartera actual.

                    <div class="mt-3">
                        <span class="text-success highlight-number">
                            <?= $total_clientes ?>
                        </span>
                        <span class="small text-muted text-uppercase fw-bold letter-spacing-1">Clientes Activos</span>
                    </div>
                </div>

                <a href="clientes/index.php" class="btn btn-outline-success rounded-pill px-5 py-2 fw-bold mt-auto w-100" style="max-width: 250px;">
                    Ver Base de Datos
                </a>
            </div>
        </div>

        <div class="col-md-6 col-lg-5">
            <div class="card card-action">
                <div class="icon-circle bg-soft-blue">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h3 class="card-title">Ingresar Nuevo Cliente</h3>
                <div class="card-text">
                    Da de alta un nuevo perfil en el sistema completando el formulario de registro.
                    <span class="d-block mt-4 text-muted fs-6">
                        Incluye validacion automatica de imagenes, datos obligatorios y control de duplicados.
                    </span>
                </div>

                <a href="clientes/crear.php" class="btn btn-outline-primary rounded-pill px-5 py-2 fw-bold mt-auto w-100" style="max-width: 250px;">
                    Comenzar Registro
                </a>
            </div>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>