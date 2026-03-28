<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../modules/books/functions.php';

$books = getAllBooks($mysqli);
echo "getAllBooks_count=" . count($books) . PHP_EOL;
if (empty($books) && !empty($mysqli->error)) {
    echo "mysqli_error=" . $mysqli->error . PHP_EOL;
}
foreach ($books as $b) {
    echo $b['id'] . " | " . $b['titre'] . " | " . ($b['auteur'] ?? 'NULL') . PHP_EOL;
}

