<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../config/db.php';

$res = $mysqli->query("SHOW COLUMNS FROM books");
if (!$res) {
    die("Erreur SHOW COLUMNS: " . $mysqli->error);
}

echo "Colonnes table books:\n";
while ($row = $res->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

