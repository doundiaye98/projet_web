<?php
defined("SECURE_ACCESS") or die("Accès direct interdit");
/**
 * Script de téléchargement - Version Correcte (Champs BDD correspondants)
 */
require_once __DIR__ . '/functions.php';

$id = (int)($_GET['id'] ?? 0);
$doc = getDocumentById($mysqli, $id);

if ($doc) {
    $filePath = resolveDocumentRowAbsolutePath($doc);
    
    if ($filePath && is_readable($filePath)) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $ctype = preg_match('/\.pdf$/i', $filePath)
            ? resolvePdfContentType($filePath, $doc['mime'] ?? '')
            : resolveDownloadContentType($filePath, $doc['mime'] ?? '');
        $safeName = basename($doc['filename'] ?? 'livre.pdf');

        header('Content-Type: ' . $ctype);
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('X-Content-Type-Options: nosniff');

        readfile($filePath);
        exit();
    }
}

http_response_code(404);
die("Erreur : Fichier introuvable.");
