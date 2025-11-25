<?php
// includes/header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

// Lógica de Navegación
$ruta_actual = $_SERVER['PHP_SELF'];
$estoy_en_lista  = (strpos($ruta_actual, '/clientes/index.php') !== false);
$estoy_en_crear  = (strpos($ruta_actual, '/clientes/crear.php') !== false);
$estoy_en_editar = (strpos($ruta_actual, '/clientes/editar.php') !== false);
$estoy_en_inicio = (strpos($ruta_actual, '/index.php') !== false) && !$estoy_en_lista;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Pro - Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }

        /* Layout Flex para Sidebar Fijo */
        #wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        /* --- SIDEBAR FIJO --- */
        #sidebar-wrapper {
            min-width: 260px;
            max-width: 260px;
            height: 100vh;
            position: sticky;
            /* Se queda pegado al scrollear */
            top: 0;
            background-color: #ffffff;
            border-right: 1px solid #e9ecef;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        /* Estilos de Marca y Menú */
        .sidebar-brand {
            padding: 1.5rem 1.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .list-group-item {
            border: none;
            padding: 0.8rem 1.5rem;
            font-weight: 500;
            color: #6c757d;
            background-color: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
            margin-bottom: 4px;
            border-right: 4px solid transparent;
        }

        .list-group-item:hover {
            color: #0d6efd;
            background-color: #f8f9fa;
            border-right: 4px solid rgba(13, 110, 253, 0.4);
        }

        .active-nav {
            color: #0d6efd !important;
            background-color: #e7f1ff !important;
            border-right: 4px solid #0d6efd !important;
        }

        .user-profile {
            margin-top: auto;
            padding: 1rem;
            border-top: 1px solid #e9ecef;
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 12px;
            transition: background 0.2s;
            text-decoration: none;
            color: #212529;
            cursor: pointer;
        }

        .user-card:hover {
            background-color: #f1f3f5;
        }

        #page-content-wrapper {
            width: 100%;
            padding: 30px;
            flex-grow: 1;
            /* Ocupa el resto del ancho */
        }

        /* Animaciones y Utilidades */
        .chevron-rotate {
            transition: transform 0.3s ease;
        }

        a[aria-expanded="true"] .chevron-rotate {
            transform: rotate(180deg);
        }

        .pointer-wrapper {
            cursor: pointer;
        }

        .hover-bg-light:hover {
            background-color: #f8f9fa;
        }

        .border-dashed {
            border-style: dashed !important;
        }
    </style>
</head>

<body>

    <div id="wrapper">

        <div id="sidebar-wrapper">

            <div class="sidebar-brand">
                <div class="rounded bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-size: 20px;">
                    <i class="fa-solid fa-chart-simple"></i>
                </div>
                <span style="letter-spacing: -0.5px;">CRM<span class="text-primary">Pro</span></span>
            </div>

            <div class="small fw-bold text-uppercase text-muted px-4 mt-3 mb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Principal</div>

            <div class="list-group list-group-flush">

                <a href="<?= BASE_URL ?>/index.php"
                    class="list-group-item list-group-item-action <?= $estoy_en_inicio ? 'active-nav' : '' ?>">
                    <i class="fa-solid fa-border-all"></i>
                    Inicio
                </a>

                <?php if (!$estoy_en_inicio && !$estoy_en_lista && !$estoy_en_editar): ?>
                    <a href="<?= BASE_URL ?>/clientes/index.php" class="list-group-item list-group-item-action">
                        <i class="fa-regular fa-user"></i>
                        Ver Clientes
                    </a>
                <?php endif; ?>

                <?php if (!$estoy_en_inicio && !$estoy_en_lista && !$estoy_en_crear && !$estoy_en_editar): ?>
                    <a href="<?= BASE_URL ?>/clientes/crear.php" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-user-plus"></i>
                        Nuevo Cliente
                    </a>
                <?php endif; ?>
            </div>

            <div class="user-profile">
                <div class="dropup">
                    <a href="#" class="user-card" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-secondary fw-bold"
                            style="width: 45px; height: 45px; font-size: 18px;">
                            <?= strtoupper(substr($_SESSION['nombre_usuario'] ?? 'A', 0, 1)) ?>
                        </div>

                        <div class="d-flex flex-column flex-grow-1" style="line-height: 1.2;">
                            <span class="fw-bold small"><?= h($_SESSION['nombre_usuario'] ?? 'Admin') ?></span>
                            <span class="text-muted" style="font-size: 0.75rem;"><?= h($_SESSION['email'] ?? 'admin@crm.com') ?></span>
                        </div>

                        <i class="fa-solid fa-chevron-up text-muted small chevron-rotate"></i>
                    </a>

                    <ul class="dropdown-menu shadow border-0 mb-2 w-100">
                        <li>
                            <h6 class="dropdown-header">Rol: <?= h(ucfirst($_SESSION['rol'] ?? '')) ?></h6>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php">
                                <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
        <div id="page-content-wrapper">