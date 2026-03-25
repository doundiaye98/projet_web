<?php
/**
 * Arrête l'exécution et affiche une page d'erreur stylisée 403
 */
function abort_access() {
    header('HTTP/1.0 403 Forbidden');
    include __DIR__ . '/../layout/error_page.php';
    exit();
}

// Vérifie si la session utilisateur est active
function isUserLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

// Fonction centralisée pour protéger les routes
function requireLogin() {
    if (!isUserLoggedIn()) {
        header('Location: /modules/auth/login.php');
        exit();
    }
}

// Récupère le rôle de l'utilisateur connecté
function getUserRole() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

// LE GARDIEN : Vérifie si l'accès est autorisé via un point d'entrée officiel
if (!defined('SECURE_ACCESS')) {
    abort_access();
}

