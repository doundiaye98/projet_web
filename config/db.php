<?php

date_default_timezone_set('Europe/Paris');

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$db_password = getenv('DB_PASS') ?: 'root';
$db = getenv('DB_NAME') ?: 'projet_web';

$mysqli = new mysqli($host, $user, $db_password, $db);

// Affichage des erreurs pour debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($mysqli->connect_error) {
    die('Erreur de connexion : ' . $mysqli->connect_error);
}

$mysqli->set_charset("utf8");

?>