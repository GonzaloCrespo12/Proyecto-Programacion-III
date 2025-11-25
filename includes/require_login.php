<?php

// Aseguramos que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. El Portero: ¿Tenés credencial (user_id)?
if (!isset($_SESSION['user_id'])) {
    // Si no tenés, te mando al login usando la constante global
    // Asumimos que config.php ya se cargo antes y definio BASE_URL
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit; // Para que no se ejecute nada más.
}
