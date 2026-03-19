<?php
define('SECURE_ACCESS', true);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Détruit toutes les données de session
session_unset();
session_destroy();
// Redirige vers la page de login
header('Location: login.php');
exit();
