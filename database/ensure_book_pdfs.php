<?php
/**
 * Attache un PDF minimal (lisible) à chaque livre sans entrée dans `documents`.
 * À lancer en CLI : php database/ensure_book_pdfs.php
 */
define('SECURE_ACCESS', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../modules/books/functions.php';

/**
 * Construit un PDF 1.1 valide avec une page de texte (Helvetica).
 */
function buildMinimalPdf(string $line1, string $line2 = ''): string {
    $line1 = mb_substr($line1, 0, 80);
    $line2 = mb_substr($line2, 0, 80);

    $esc = static function (string $s): string {
        return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $s);
    };

    $y = 720;
    $stream = '';
    if ($line1 !== '') {
        $stream .= 'BT /F1 14 Tf 72 ' . $y . ' Td (' . $esc($line1) . ') Tj ET' . "\n";
        $y -= 22;
    }
    if ($line2 !== '') {
        $stream .= 'BT /F1 11 Tf 72 ' . $y . ' Td (' . $esc($line2) . ') Tj ET' . "\n";
    }
    if ($stream === '') {
        $stream = 'BT /F1 14 Tf 72 720 Td (Book Club) Tj ET' . "\n";
    }

    $chunks = [];
    $chunks[] = "%PDF-1.1\n%\xE2\xE3\xCF\xD3\n";

    $objs = [];
    $objs[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objs[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objs[3] = "<< /Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 612 792] /Contents 5 0 R >>";
    $objs[4] = "<< /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >>";
    $objs[5] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";

    $body = $chunks[0];
    $offsets = [0];
    for ($i = 1; $i <= 5; $i++) {
        $offsets[$i] = strlen($body);
        $body .= $i . " 0 obj\n" . $objs[$i] . "\nendobj\n";
    }

    $xrefStart = strlen($body);
    $body .= "xref\n0 6\n0000000000 65535 f \n";
    for ($i = 1; $i <= 5; $i++) {
        $body .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $body .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . $xrefStart . "\n%%EOF\n";

    return $body;
}

$adminRes = $mysqli->query("SELECT id FROM users WHERE statut = 'actif' ORDER BY id ASC LIMIT 1");
$adminRow = $adminRes ? $adminRes->fetch_assoc() : null;
$adminId = $adminRow ? (int) $adminRow['id'] : 1;

$dir = dirname(__DIR__) . '/uploads/books';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$sql = "SELECT b.id, b.titre, a.nom AS auteur
        FROM books b
        JOIN authors a ON a.id = b.author_id
        WHERE NOT EXISTS (SELECT 1 FROM documents d WHERE d.book_id = b.id)";
$res = $mysqli->query($sql);
if (!$res) {
    die("Erreur SQL: " . $mysqli->error);
}

$added = 0;
while ($row = $res->fetch_assoc()) {
    $bookId = (int) $row['id'];
    $titre = $row['titre'];
    $auteur = $row['auteur'];

    $filename = 'demo_book_' . $bookId . '.pdf';
    $relPath = 'uploads/books/' . $filename;
    $absPath = $dir . '/' . $filename;

    $pdf = buildMinimalPdf($titre, 'par ' . $auteur);
    if (file_put_contents($absPath, $pdf) === false) {
        echo "Echec ecriture: {$absPath}\n";
        continue;
    }

    $size = (int) filesize($absPath);
    addDocument($mysqli, $bookId, $filename, $relPath, 'application/pdf', $size, $adminId, true);
    $added++;
    echo "OK livre #{$bookId} : {$titre}\n";
}

echo "\nTermine. Documents ajoutes: {$added}.\n";
echo "Astuce: remplace ces PDFs par les vrais fichiers via \"Modifier le livre\" (admin/moderateur).\n";
