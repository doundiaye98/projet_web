<?php
define('SECURE_ACCESS', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../modules/books/functions.php';

// Dossier où tu mets tes PDF (et éventuellement couvertures) avant d'executer ce script.
$seedDir = __DIR__ . '/../uploads/seed_books';

// Adresse de l'admin pour renseigner `created_by` dans `books` + `documents`
$adminEmail = 'admin@club.test';

// ---- mapping livre -> patterns (noms de fichiers) ----
$books = [
    [
        'titre' => "Le Petit Prince",
        'author' => 'Antoine de Saint-Exupery',
        'genre' => 'Conte',
        'description' => "Une histoire poetique sur l'essentiel, la decouverte et la responsabilite.",
        // le script cherche un PDF dont le nom contient "petit" + "prince"
        'pdfPatterns' => ['/petit.*prince/i', '/petit/i'],
        'nb_pages' => 0,
    ],
    [
        'titre' => "1984",
        'author' => 'George Orwell',
        'genre' => 'Dystopie',
        'description' => "Dans un monde totalitaire, la verite devient une arme et la liberte un reve interdit.",
        'pdfPatterns' => ['/1984/i'],
        'nb_pages' => 0,
    ],
    [
        'titre' => "L'Étranger",
        'author' => 'Albert Camus',
        'genre' => 'Roman',
        'description' => "Un recit bref et intense sur l'absurde, le regard detache et les consequences.",
        'pdfPatterns' => ['/l.?[eE]tranger/i', '/etranger/i', '/camus/i'],
        'nb_pages' => 0,
    ],
    [
        'titre' => "Le Comte de Monte-Cristo",
        'author' => 'Alexandre Dumas',
        'genre' => 'Aventure',
        'description' => "Trahison, emprisonnement et vengeance au grand souffle romanesque.",
        'pdfPatterns' => ['/monte.?cristo/i', '/monte/i', '/cristo/i', '/dumas/i'],
        'nb_pages' => 0,
    ],
];

// Si aucun fichier ne correspond aux patterns, prendre les PDFs par ordre de scan.
// (utile si tu as juste copié 4 PDFs quelconques dans le dossier)
$autoAssignByOrder = true;

function listPdfPaths(string $dir): array {
    if (!is_dir($dir)) return [];
    $files = scandir($dir) ?: [];
    $pdfs = [];
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $abs = $dir . '/' . $f;
        if (is_file($abs) && preg_match('/\.pdf$/i', $f)) {
            $pdfs[] = $abs;
        }
    }
    return $pdfs;
}

function pickPdfByPatterns(string $seedDir, array $pdfPatterns): ?string {
    if (!is_dir($seedDir)) return null;
    $files = scandir($seedDir) ?: [];
    $pdfs = array_values(array_filter($files, function ($f) use ($seedDir) {
        if ($f === '.' || $f === '..') return false;
        $abs = $seedDir . '/' . $f;
        return is_file($abs) && preg_match('/\.pdf$/i', $f);
    }));

    if (empty($pdfs)) return null;

    // Fallback : si le dossier de seed est vide,
    // chercher aussi dans `uploads/books` (au cas ou tu y as deja déposé les PDFs).
    if (empty($pdfs)) {
        $altDir = __DIR__ . '/../uploads/books';
        if (is_dir($altDir)) {
            $altFiles = scandir($altDir) ?: [];
            $altPdfs = array_values(array_filter($altFiles, function ($f) use ($altDir) {
                if ($f === '.' || $f === '..') return false;
                $abs = $altDir . '/' . $f;
                return is_file($abs) && preg_match('/\.pdf$/i', $f);
            }));
            $pdfs = $altPdfs;
        }
        if (empty($pdfs)) return null;
    }

    foreach ($pdfs as $file) {
        $ok = true;
        foreach ($pdfPatterns as $pattern) {
            if (!preg_match($pattern, $file)) {
                $ok = false;
                break;
            }
        }
        if ($ok) {
            return $seedDir . '/' . $file;
        }
    }

    // fallback: si aucun fichier ne match tout, on prend le premier match sur au moins un pattern
    foreach ($pdfs as $file) {
        foreach ($pdfPatterns as $pattern) {
            if (preg_match($pattern, $file)) {
                return $seedDir . '/' . $file;
            }
        }
    }

    return null;
}

function getUserIdByEmail($mysqli, string $email): ?int {
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return isset($row['id']) ? (int)$row['id'] : null;
}

function bookExists($mysqli, int $authorId, string $titre): bool {
    $stmt = $mysqli->prepare("SELECT id FROM books WHERE author_id = ? AND titre = ? LIMIT 1");
    $stmt->bind_param("is", $authorId, $titre);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (bool)$row;
}

function guessMime(string $path): string {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($path);
    return is_string($mime) && $mime ? $mime : 'application/pdf';
}

function sanitizeExtension(string $filename): string {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return $ext ?: 'pdf';
}

$adminId = getUserIdByEmail($mysqli, $adminEmail);
if (!$adminId) {
    die("Admin introuvable (email) : {$adminEmail}");
}

if (!is_dir($seedDir)) {
    mkdir($seedDir, 0775, true);
}

$projectRoot = dirname(__DIR__);
$coversDir = $projectRoot . '/uploads/covers';
$booksDir = $projectRoot . '/uploads/books';

if (!is_dir($coversDir)) mkdir($coversDir, 0775, true);
if (!is_dir($booksDir)) mkdir($booksDir, 0775, true);

$added = 0;
$skipped = 0;
$autoPdfIndex = 0;
$autoPdfPaths = array_merge(
    listPdfPaths($seedDir),
    listPdfPaths($projectRoot . '/uploads/books')
);

$mysqli->begin_transaction();
try {
    foreach ($books as $b) {
        $titre = trim($b['titre'] ?? '');
        $authorName = trim($b['author'] ?? '');
        $genre = $b['genre'] ?? null;
        $description = $b['description'] ?? null;
        $pdfPatterns = $b['pdfPatterns'] ?? [];
        if ($titre === '' || $authorName === '' || empty($pdfPatterns)) {
            continue;
        }

        $seedPdfPath = pickPdfByPatterns($seedDir, $pdfPatterns);
        if (!$seedPdfPath) {
            $seedFiles = scandir($seedDir) ?: [];
            $seedFiles = array_values(array_filter($seedFiles, fn($f) => $f !== '.' && $f !== '..'));
            if ($autoAssignByOrder && $autoPdfIndex < count($autoPdfPaths)) {
                $seedPdfPath = $autoPdfPaths[$autoPdfIndex];
                $autoPdfIndex++;
            } else {
                die("PDF introuvable pour '{$titre}'. Patterns: " . json_encode($pdfPatterns) . ". Fichiers disponibles: " . json_encode($seedFiles));
            }
        }

        $pdfOriginalName = basename($seedPdfPath);
        $pdfExt = sanitizeExtension($pdfOriginalName);

        $authorId = getOrCreateAuthor($mysqli, $authorName);
        if (!$authorId) {
            die("Impossible de creer/récuperer l'auteur : {$authorName}");
        }

        if (bookExists($mysqli, $authorId, $titre)) {
            $skipped++;
            continue;
        }

        // Cover optionnelle
        $coverPath = null;
        if (!empty($b['cover'])) {
            $seedCoverPath = $seedDir . '/' . $b['cover'];
            if (file_exists($seedCoverPath)) {
                $safeCoverName = uniqid('cover_seed_', true) . '.' . strtolower(pathinfo($seedCoverPath, PATHINFO_EXTENSION));
                $destCoverAbs = $coversDir . '/' . $safeCoverName;
                if (!@copy($seedCoverPath, $destCoverAbs)) {
                    die("Impossible de copier la couverture : " . $b['cover']);
                }
                $coverPath = 'uploads/covers/' . $safeCoverName;
            }
        }

        // Copier PDF vers uploads/books (pour coller a la structure existante)
        $safePdfName = uniqid('book_seed_', true) . '.' . $pdfExt;
        $destPdfAbs = $booksDir . '/' . $safePdfName;
        if (!@copy($seedPdfPath, $destPdfAbs)) {
            die("Impossible de copier le PDF : {$pdfOriginalName}");
        }

        $nbPages = (int)($b['nb_pages'] ?? 0);
        $detectedPages = getPdfPageCount($destPdfAbs);
        if ($detectedPages > 0) {
            $nbPages = $detectedPages;
        }

        // Insertion book
        $bookId = addBook($mysqli, $authorId, $titre, $genre, $description, $coverPath, $nbPages, $adminId);
        if (!$bookId) {
            die("Erreur insertion book: {$titre}");
        }

        // Insertion document principal (documents.is_main=1)
        $destPdfRel = 'uploads/books/' . $safePdfName;
        $mime = guessMime($destPdfAbs);
        $size = (int) filesize($destPdfAbs);
        addDocument(
            $mysqli,
            $bookId,
            $safePdfName,
            $destPdfRel,
            $mime,
            $size,
            $adminId,
            true
        );

        $added++;
    }

    $mysqli->commit();
} catch (Throwable $e) {
    $mysqli->rollback();
    die("Erreur seed_books_with_pdf.php : " . $e->getMessage());
}

echo "Seed (avec PDFs) terminé. Ajoutés: {$added}, déjà existants: {$skipped}.";

