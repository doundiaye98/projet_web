<?php

$host = 'localhost';
$user = 'root';
$db_password = 'root';
$db = 'projet_web';

$mysqli = new mysqli($host, $user, $db_password, $db);

if ($mysqli->connect_error) {
    die('Erreur de connexion : ' . $mysqli->connect_error);
}

$mysqli->set_charset("utf8");

?>