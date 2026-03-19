<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/includes/auth/session.php';
requireLogin();

// Affichage du header (navbar)
include __DIR__ . '/includes/layout/header.php';

// ... contenu de la page d'accueil ...
echo "Bienvenue sur la page d'accueil !";

include __DIR__ . '/includes/layout/footer.php';
