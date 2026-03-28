<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../config/db.php';

$res = $mysqli->query("SHOW COLUMNS FROM books LIKE 'genre'");
if ($res && $res->num_rows > 0) {
    echo "OK: colonne 'genre' existe deja.\n";
    exit;
}

if ($mysqli->query("ALTER TABLE books ADD COLUMN genre VARCHAR(100) DEFAULT NULL AFTER titre")) {
    echo "OK: colonne 'genre' ajoutee.\n";
} else {
    echo "Erreur ALTER TABLE: " . $mysqli->error . "\n";
    exit(1);
}

