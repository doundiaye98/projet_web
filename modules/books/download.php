<?php
defined("SECURE_ACCESS") or die("Accès direct interdit");
/**
 * Script de téléchargement - Version Correcte (Champs BDD correspondants)
 */
require_once __DIR__ . '/functions.php';

$id = (int)($_GET['id'] ?? 0);
$doc = getDocumentById($mysqli, $id);

if ($doc) {
    // Dans la table documents : 'filepath' et 'mime'
    $filePath = __DIR__ . '/../../' . $doc['filepath'];
    
    if (file_exists($filePath)) {
        // Nettoyage pour éviter que des espaces parasites ne corrompent le PDF
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: ' . $doc['mime']);
        header('Content-Disposition: attachment; filename="' . basename($doc['filename']) . '"');
        header('Content-Length: ' . filesize($filePath));
        
        readfile($filePath);
        exit();
    }
}

die("Erreur : Fichier introuvable.");
