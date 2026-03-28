<?php
/**
 * Recree sur le disque les fichiers references dans `documents` mais absents.
 * php database/repair_missing_documents.php
 */
define('SECURE_ACCESS', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../modules/books/functions.php';

$res = $mysqli->query('SELECT id, book_id, filename, filepath, mime FROM documents');
if (!$res) {
    die('Erreur: ' . $mysqli->error);
}

$fixed = 0;
while ($row = $res->fetch_assoc()) {
    if (resolveDocumentRowAbsolutePath($row)) {
        continue;
    }

    $rel = trim((string) ($row['filepath'] ?? ''));
    $rel = str_replace('\\', '/', $rel);
    if ($rel === '' || strpos($rel, 'uploads/') !== 0) {
        $base = basename(str_replace('\\', '/', (string) ($row['filename'] ?? 'ressource.txt')));
        if ($base === '' || $base === '.') {
            $base = 'doc_' . (int) $row['id'] . '.txt';
        }
        $rel = 'uploads/books/' . $base;
    }

    $abs = getProjectRootPath() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $dir = dirname($abs);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    if ($ext === 'txt' || stripos((string) $row['mime'], 'text') !== false) {
        $content = "Ressource — Book Club\r\n\r\n";
        $content .= "Le fichier d'origine etait absent sur ce serveur.\r\n";
        $content .= "Remplacez-le par l'upload dans « Modifier le livre » (admin / moderateur).\r\n";
    } else {
        $content = "Fichier restaure automatiquement (placeholder).\n";
    }

    if (file_put_contents($abs, $content) === false) {
        echo "Echec ecriture: {$abs}\n";
        continue;
    }

    $stmt = $mysqli->prepare('UPDATE documents SET filepath = ?, size = ? WHERE id = ?');
    $size = (int) filesize($abs);
    $stmt->bind_param('sii', $rel, $size, $row['id']);
    $stmt->execute();
    $stmt->close();
    $fixed++;
    echo "OK document #{$row['id']} -> {$rel}\n";
}

echo "\nFichiers recrees: {$fixed}\n";
