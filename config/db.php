<?php

$host = 'localhost';
$user = 'root';
$db_password = 'root';
$db = 'projet_web';

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