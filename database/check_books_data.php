<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../config/db.php';

function countTable($mysqli, $table) {
    $res = $mysqli->query("SELECT COUNT(*) AS c FROM {$table}");
    if (!$res) return 'ERR: ' . $mysqli->error;
    $row = $res->fetch_assoc();
    return (int)($row['c'] ?? 0);
}

echo "users=" . countTable($mysqli, 'users') . PHP_EOL;
echo "authors=" . countTable($mysqli, 'authors') . PHP_EOL;
echo "books=" . countTable($mysqli, 'books') . PHP_EOL;
echo "documents=" . countTable($mysqli, 'documents') . PHP_EOL;

$sql = "SELECT b.id, b.titre, b.author_id, a.nom AS auteur
        FROM books b
        LEFT JOIN authors a ON a.id = b.author_id
        ORDER BY b.id DESC
        LIMIT 10";
$res = $mysqli->query($sql);
if (!$res) {
    echo "query_error=" . $mysqli->error . PHP_EOL;
    exit;
}

echo "--- latest books ---" . PHP_EOL;
while ($row = $res->fetch_assoc()) {
    echo $row['id'] . " | " . $row['titre'] . " | author_id=" . $row['author_id'] . " | auteur=" . ($row['auteur'] ?? 'NULL') . PHP_EOL;
}

