<?php

/**
 * includes/header.php
 * Encabezado Global y Barra de Navegacion Lateral (Sidebar).
 *
 * Contiene la estructura HTML inicial, estilos CSS, logica de navegacion activa
 * Tambien gestiona la visualizacion condicional de opciones segun la pagina actual.
 */

// Asegurar que la sesion este iniciada para acceder a variables de sesion
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar configuracion global
require_once __DIR__ . '/config.php';

// LOGICA DE NAVEGACION ACTIVA
// Detecta el script en ejecucion para resaltar la opcion correspondiente en el menu
// y ocultar botones redundantes (ej: no mostrar "Nuevo Cliente" si ya estamos en esa pantalla).
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
    <title>CRM Pro - Panel de Control</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        /* GESTION DE TEMAS (Variables CSS) */
        :root {
            /* Paleta de colores para modo Claro */
            --bg-body: #f8f9fa;
            --sidebar-bg: #ffffff;
            --sidebar-border: #e9ecef;
            --list-item-color: #6c757d;
            --list-item-hover-bg: #f8f9fa;
            --list-item-border-hover: rgba(13, 110, 253, 0.4);
            --user-card-bg-hover: #f1f3f5;
            --page-padding: 30px;

            /* Componentes UI */
            --card-bg: #ffffff;
            --card-title-color: #2c3e50;
            --card-text-color: #6c757d;
            --icon-green-bg: #e0f2f1;
            --icon-green-color: #009688;
            --icon-blue-bg: #e7f1ff;
            --icon-blue-color: #0d6efd;
            --btn-border-color: rgba(33, 37, 41, 0.08);
            --btn-hover-bg: rgba(13, 110, 253, 0.12);
        }

        /* Clase activada mediante JavaScript para Modo Oscuro */
        .dark-theme {
            --bg-body: #d7d7d7ff;
            --sidebar-bg: #d7d7d7ff;
            --sidebar-border: #d0d0d0;
            --list-item-color: #4a4a4a;
            --list-item-hover-bg: #c6c6c6ff;
            --list-item-border-hover: rgba(13, 110, 253, 0.4);
            --user-card-bg-hover: #f1f3f5;
            --page-padding: 30px;

            /* Ajustes de contraste para modo oscuro */
            --card-bg: #f0f0f0;
            --card-title-color: #1a1a1a;
            --card-text-color: #3a3a3a;
            --icon-green-bg: #004d47;
            --icon-green-color: #4dd0c1;
            --icon-blue-bg: #00356b;
            --icon-blue-color: #64b5f6;
            --btn-border-color: rgba(33, 37, 41, 0.12);
            --btn-hover-bg: rgba(100, 181, 246, 0.12);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            overflow-x: hidden;
        }

        /* Transiciones globales para cambio suave de tema */
        html,
        body,
        #sidebar-wrapper,
        .card-action,
        .list-group-item,
        .card-title {
            transition: background-color 0.25s ease, color 0.25s ease, border-color 0.25s ease;
        }

        /* Boton toggle de tema */
        #theme-toggle {
            border: 2px solid var(--btn-border-color) !important;
            padding: 4px 8px;
            box-shadow: none !important;
            background-color: transparent !important;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        #theme-toggle:hover {
            background-color: var(--btn-hover-bg) !important;
            color: var(--icon-blue-color) !important;
        }

        /* ESTRUCTURA DE LAYOUT (Flexbox) */
        #wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        /* BARRA LATERAL FIJA (Sticky Sidebar) */
        #sidebar-wrapper {
            min-width: 260px;
            max-width: 260px;
            height: 100vh;
            position: sticky;
            /* Se mantiene fija al hacer scroll */
            top: 0;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            display: flex;
            flex-direction: column;
            /* Organizacion vertical de elementos */
            z-index: 1000;
        }

        /* Estilos de Elementos del Menu */
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
            color: var(--list-item-color);
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
            background-color: var(--list-item-hover-bg);
            border-right: 4px solid var(--list-item-border-hover);
        }

        /* Estado Activo (Pagina actual) */
        .active-nav {
            color: #0d6efd !important;
            background-color: #e7f1ff !important;
            border-right: 4px solid #0d6efd !important;
        }

        .user-profile {
            margin-top: auto;
            /* Empuja este bloque al fondo del sidebar */
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
        }

        /* Utilidades: Rotacion de flecha y cursores */
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

                <button id="theme-toggle" class="btn btn-sm btn-outline-secondary ms-3" title="Alternar tema" style="height:36px; align-self:center;">
                    <i id="theme-icon" class="fa-solid fa-moon"></i>
                </button>
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
                                <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Cerrar Sesion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
        <div id="page-content-wrapper">