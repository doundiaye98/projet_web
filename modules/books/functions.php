<?php
defined("SECURE_ACCESS") or die("Accès direct interdit");

/**
 * Récupère tous les auteurs triés alphabétiquement
 */
function getAllAuthors($mysqli) {
    $result = $mysqli->query("SELECT id, nom FROM authors ORDER BY nom ASC");
    $authors = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) $authors[] = $row;
    }
    return $authors;
}

/**
 * Trouve un auteur par son nom exact ou le crée s'il n'existe pas
 */
function getOrCreateAuthor($mysqli, $nom) {
    $nom = trim($nom);
    if (empty($nom)) return false;

    $stmt = $mysqli->prepare("SELECT id FROM authors WHERE nom = ?");
    $stmt->bind_param("s", $nom);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) return (int)$row['id'];

    $stmt = $mysqli->prepare("INSERT INTO authors (nom) VALUES (?)");
    $stmt->bind_param("s", $nom);
    if ($stmt->execute()) return (int)$mysqli->insert_id;
    return false;
}

/**
 * Récupère tous les livres avec le nom de l'auteur
 */
function getAllBooks($mysqli) {
    $hasIsMain = hasTableColumn($mysqli, 'documents', 'is_main');
    $resourcesExpr = $hasIsMain
        ? "(SELECT GROUP_CONCAT(CONCAT(d.id, '::', d.filename, '::', d.filepath)
                ORDER BY d.is_main DESC, d.id ASC SEPARATOR '||')
           FROM documents d
           WHERE d.book_id = b.id) AS resources"
        : "(SELECT GROUP_CONCAT(CONCAT(d.id, '::', d.filename, '::', d.filepath)
                ORDER BY d.id ASC SEPARATOR '||')
           FROM documents d
           WHERE d.book_id = b.id) AS resources";

    // Requete compatible avec MySQL en mode ONLY_FULL_GROUP_BY
    $sql = "SELECT
                b.id, b.author_id, b.titre, b.genre, b.description, b.cover_path, b.nb_pages, b.created_at,
                a.nom AS auteur,
                (SELECT AVG(r.note) FROM reviews r WHERE r.book_id = b.id AND r.visible = 1) AS avg_rating,
                {$resourcesExpr}
            FROM books b
            JOIN authors a ON a.id = b.author_id
            ORDER BY b.created_at DESC";
    $result = $mysqli->query($sql);
    $books = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['resources_list'] = [];
            if (!empty($row['resources'])) {
                $parts = explode('||', $row['resources']);
                foreach ($parts as $p) {
                    $details = explode('::', $p);
                    if (count($details) === 3) {
                        $row['resources_list'][] = [
                            'id'       => $details[0],
                            'filename' => $details[1],
                            'filepath' => $details[2]
                        ];
                    }
                }
            }
            $books[] = $row;
        }
    }
    return $books;
}

/**
 * Récupère les détails complets d'un livre (avec auteur et stats de notes)
 */
function getBookDetails($mysqli, $id) {
    $hasIsMain = hasTableColumn($mysqli, 'documents', 'is_main');
    $mainPdfIdExpr = $hasIsMain
        ? "(SELECT id FROM documents d WHERE d.book_id = b.id AND d.is_main = 1 ORDER BY d.id ASC LIMIT 1) AS main_pdf_id"
        : "(SELECT id FROM documents d WHERE d.book_id = b.id ORDER BY d.id ASC LIMIT 1) AS main_pdf_id";
    $mainPdfPathExpr = $hasIsMain
        ? "(SELECT filepath FROM documents d WHERE d.book_id = b.id AND d.is_main = 1 ORDER BY d.id ASC LIMIT 1) AS main_pdf"
        : "(SELECT filepath FROM documents d WHERE d.book_id = b.id ORDER BY d.id ASC LIMIT 1) AS main_pdf";

    // Requete compatible avec MySQL en mode ONLY_FULL_GROUP_BY
    $sql = "SELECT
                b.*,
                a.nom AS auteur,
                (SELECT AVG(r.note) FROM reviews r WHERE r.book_id = b.id AND r.visible = 1) AS avg_rating,
                (SELECT COUNT(*) FROM reviews r WHERE r.book_id = b.id AND r.visible = 1) AS reviews_count,
                {$mainPdfIdExpr},
                {$mainPdfPathExpr}
            FROM books b
            JOIN authors a ON a.id = b.author_id
            WHERE b.id = ?
            LIMIT 1";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Récupère les ressources complémentaires d'un livre
 */
function getBookResources($mysqli, $id) {
    $hasIsMain = hasTableColumn($mysqli, 'documents', 'is_main');
    $sql = $hasIsMain
        ? "SELECT * FROM documents WHERE book_id = ? AND is_main = 0 ORDER BY id ASC"
        : "SELECT * FROM documents WHERE book_id = ? ORDER BY id ASC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Récupère tous les avis visibles pour un livre
 */
function getBookReviewsList($mysqli, $id) {
    $sql = "SELECT r.*, u.nom AS auteur_critique 
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.book_id = ? AND r.visible = 1
            ORDER BY r.created_at DESC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Ajoute un nouvel avis pour un livre (en attente de modération)
 */
function addReview($mysqli, $bookId, $userId, $note, $comment, $visible = 0) {
    $sql = "INSERT INTO reviews (book_id, user_id, note, commentaire, visible) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE note = VALUES(note), commentaire = VALUES(commentaire), visible = VALUES(visible)";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("iidss", $bookId, $userId, $note, $comment, $visible);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Supprime un document (fichier physique + base)
 */
function deleteDocument($mysqli, $docId) {
    $stmt = $mysqli->prepare("SELECT filepath, filename FROM documents WHERE id = ?");
    $stmt->bind_param("i", $docId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $abs = resolveDocumentRowAbsolutePath($row ?: []);
    safeUnlink($abs);
    $stmt = $mysqli->prepare("DELETE FROM documents WHERE id = ?");
    $stmt->bind_param("i", $docId);
    return $stmt->execute();
}

/**
 * Extrait le nombre de pages depuis le binaire d'un PDF
 * Compte les objets de type /Page (fonctionne sur la majorité des PDFs standard)
 */
function getPdfPageCount($filepath) {
    // Linux/Mac : pdfinfo (poppler-utils)
    $out = @shell_exec('pdfinfo ' . escapeshellarg($filepath) . ' 2>/dev/null');
    if ($out && preg_match('/Pages:\s+(\d+)/i', $out, $m)) return (int)$m[1];

    // Cross-platform : mutool (MuPDF)
    $out = @shell_exec('mutool info ' . escapeshellarg($filepath) . ' 2>/dev/null');
    if ($out && preg_match('/Pages:\s+(\d+)/i', $out, $m)) return (int)$m[1];

    // Fallback : regex sur le binaire brut (PDFs non compressés uniquement)
    $content = @file_get_contents($filepath);
    if (!$content) return 0;
    preg_match_all('/\/Count\s+(\d+)/', $content, $matches);
    if (!empty($matches[1])) return (int)max($matches[1]);

    return 0;
}

/**
 * Valide les erreurs PHP d'upload et la taille du fichier.
 * Retourne ['path' => null] si aucun fichier, ['error' => '...'] si problème, null si OK.
 */
function validateUpload($file, $maxBytes, $label) {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return ['path' => null];
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE)
        return ['error' => "$label dépasse la limite autorisée."];
    if ($file['error'] !== UPLOAD_ERR_OK)
        return ['error' => "Erreur lors du téléchargement de $label."];
    if ($file['size'] > $maxBytes)
        return ['error' => "$label ne doit pas dépasser " . ($maxBytes / 1024 / 1024) . ' Mo.'];
    return null;
}

/**
 * Supprime un fichier physique de manière silencieuse.
 */
function safeUnlink($filepath) {
    if ($filepath && file_exists($filepath)) {
        @unlink($filepath);
    }
}

/**
 * Racine du projet (dossier contenant index.php et uploads/).
 */
function getProjectRootPath() {
    return dirname(__DIR__, 2);
}

/**
 * Chemin absolu vers un fichier document à partir de la valeur en BDD (relatif ou absolu Windows).
 */
function resolveDocumentAbsolutePath($storedPath) {
    if ($storedPath === null || $storedPath === '') {
        return null;
    }
    $storedPath = trim($storedPath);
    if ($storedPath === '') {
        return null;
    }

    if (preg_match('#^[a-zA-Z]:[\\\\/]#', $storedPath)) {
        $normalized = str_replace('/', DIRECTORY_SEPARATOR, $storedPath);
        return file_exists($normalized) ? $normalized : null;
    }
    if ($storedPath[0] === '/' || $storedPath[0] === '\\') {
        $normalized = str_replace('/', DIRECTORY_SEPARATOR, $storedPath);
        return file_exists($normalized) ? $normalized : null;
    }

    $rel = str_replace(['\\'], '/', $storedPath);
    $rel = ltrim($rel, '/');
    $full = getProjectRootPath() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);

    return file_exists($full) ? $full : null;
}

/**
 * Résout le chemin absolu d'un document en BDD (chemin relatif, absolu, ou seulement nom de fichier dans uploads/books).
 */
function resolveDocumentRowAbsolutePath(array $doc) {
    $primary = resolveDocumentAbsolutePath($doc['filepath'] ?? '');
    if ($primary) {
        return $primary;
    }

    $candidates = [];
    $fp = trim((string) ($doc['filepath'] ?? ''));
    if ($fp !== '') {
        $candidates[] = basename(str_replace('\\', '/', $fp));
    }
    $fn = trim((string) ($doc['filename'] ?? ''));
    if ($fn !== '') {
        $candidates[] = basename(str_replace('\\', '/', $fn));
    }

    $root = getProjectRootPath();
    $booksDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'books';
    foreach (array_unique(array_filter($candidates)) as $base) {
        if ($base === '' || $base === '.') {
            continue;
        }
        $try = $booksDir . DIRECTORY_SEPARATOR . $base;
        if (file_exists($try)) {
            return $try;
        }
    }

    return null;
}

/**
 * Content-Type fiable pour un PDF (évite les PDFs illisibles si mime BDD est vide ou erroné).
 */
function resolvePdfContentType($absolutePath, $dbMime) {
    $dbMime = trim((string) $dbMime);
    if ($absolutePath && preg_match('/\.pdf$/i', $absolutePath)) {
        return 'application/pdf';
    }
    if ($dbMime !== '' && stripos($dbMime, 'pdf') !== false) {
        return 'application/pdf';
    }
    return $dbMime !== '' ? $dbMime : 'application/pdf';
}

/**
 * Content-Type pour téléchargement (PDF, texte, etc.).
 */
function resolveDownloadContentType($absolutePath, $dbMime) {
    if ($absolutePath && is_readable($absolutePath)) {
        if (function_exists('finfo_open')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->file($absolutePath);
            if (is_string($detected) && $detected !== '' && $detected !== 'application/octet-stream') {
                return $detected;
            }
        }
    }
    $dbMime = trim((string) $dbMime);
    if ($dbMime !== '') {
        return $dbMime;
    }
    if ($absolutePath && preg_match('/\.txt$/i', $absolutePath)) {
        return 'text/plain; charset=UTF-8';
    }
    return 'application/octet-stream';
}

/**
 * Vérifie l'existence d'une colonne (avec cache local)
 */
function hasTableColumn($mysqli, $table, $column) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    // Sécurise le nom de la table (évite l'injection SQL)
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = $mysqli->real_escape_string($column);
    $sql = "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'";
    $result = $mysqli->query($sql);
    $exists = ($result && $result->num_rows > 0);
    $cache[$key] = $exists;
    return $exists;
}

/**
 * Gère l'upload d'une couverture (JPEG, PNG, WebP — max 2 Mo)
 */
function uploadCover($file, $uploadDir) {
    $err = validateUpload($file, 2 * 1024 * 1024, 'La couverture');
    if ($err !== null) return $err;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) return ['error' => 'Format non autorisé. Utilisez JPEG, PNG ou WebP.'];

    $filename = uniqid('cover_', true) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
        return ['error' => 'Impossible de sauvegarder la couverture.'];
    }
    return ['path' => 'uploads/covers/' . $filename];
}

/**
 * Gère l'upload d'une ressource générique (PDF, Image — max 32 Mo)
 */
function uploadResource($file, $uploadDir) {
    $err = validateUpload($file, 32 * 1024 * 1024, 'La ressource');
    if ($err !== null) return $err;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $allowed = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) return ['error' => 'Format de ressource non supporté (PDF, JPG, PNG, WebP uniquement).'];

    $filename = uniqid('res_', true) . '.' . $allowed[$mime];
    $destPath = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['error' => 'Impossible de sauvegarder la ressource.'];
    }
    return [
        'path'     => 'uploads/books/' . $filename,
        'filepath' => $destPath,
        'filename' => $file['name'],
        'mime'     => $mime,
        'size'     => $file['size'],
    ];
}

/**
 * Gère l'upload d'un PDF principal (max 32 Mo)
 */
function uploadPdf($file, $uploadDir) {
    $err = validateUpload($file, 32 * 1024 * 1024, 'Le PDF');
    if ($err !== null) return $err;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if ($mime !== 'application/pdf') return ['error' => 'Seuls les fichiers PDF sont acceptés.'];

    $filename = uniqid('book_', true) . '.pdf';
    $destPath = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['error' => 'Impossible de sauvegarder le PDF.'];
    }
    return [
        'path'     => 'uploads/books/' . $filename,
        'filepath' => $destPath,
        'filename' => $filename,
        'size'     => $file['size'],
        'mime'     => $mime,
    ];
}

/**
 * Insère un livre en base de données
 */
function addBook($mysqli, $authorId, $titre, $genre, $description, $coverPath, $nbPages, $userId) {
    $hasGenre = hasTableColumn($mysqli, 'books', 'genre');
    $hasDateDebut = hasTableColumn($mysqli, 'books', 'date_debut');
    $hasDateFin = hasTableColumn($mysqli, 'books', 'date_fin');

    if ($hasGenre && $hasDateDebut && $hasDateFin) {
        $sql = "INSERT INTO books (author_id, titre, genre, description, cover_path, nb_pages, date_debut, date_fin, created_by)
                VALUES (?, ?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?)";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("issssii", $authorId, $titre, $genre, $description, $coverPath, $nbPages, $userId);
    } elseif ($hasGenre) {
        $sql = "INSERT INTO books (author_id, titre, genre, description, cover_path, nb_pages, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("issssii", $authorId, $titre, $genre, $description, $coverPath, $nbPages, $userId);
    } else {
        $sql = "INSERT INTO books (author_id, titre, description, cover_path, nb_pages, created_by)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("isssii", $authorId, $titre, $description, $coverPath, $nbPages, $userId);
    }

    if ($stmt->execute()) return (int)$mysqli->insert_id;
    return false;
}

/**
 * Insère un document (PDF) lié à un livre dans la table documents
 */
function addDocument($mysqli, $bookId, $filename, $filepath, $mime, $size, $userId, $isMain = false) {
    $hasIsMain = hasTableColumn($mysqli, 'documents', 'is_main');
    if ($hasIsMain) {
        $sql = "INSERT INTO documents (book_id, filename, filepath, mime, size, is_main, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $mainVal = $isMain ? 1 : 0;
        $stmt->bind_param("isssiii", $bookId, $filename, $filepath, $mime, $size, $mainVal, $userId);
    } else {
        $sql = "INSERT INTO documents (book_id, filename, filepath, mime, size, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("isssii", $bookId, $filename, $filepath, $mime, $size, $userId);
    }
    return $stmt->execute();
}
/**
 * Met à jour un livre (métadonnées + couverture optionnelle)
 */
function updateBook($mysqli, $id, $authorId, $titre, $genre, $description, $coverPath, $nbPages) {
    $hasGenre = hasTableColumn($mysqli, 'books', 'genre');

    if ($coverPath) {
        if ($hasGenre) {
            $sql = "UPDATE books SET author_id = ?, titre = ?, genre = ?, description = ?, cover_path = ?, nb_pages = ? WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("issssii", $authorId, $titre, $genre, $description, $coverPath, $nbPages, $id);
        } else {
            $sql = "UPDATE books SET author_id = ?, titre = ?, description = ?, cover_path = ?, nb_pages = ? WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("isssii", $authorId, $titre, $description, $coverPath, $nbPages, $id);
        }
    } else {
        if ($hasGenre) {
            $sql = "UPDATE books SET author_id = ?, titre = ?, genre = ?, description = ?, nb_pages = ? WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("isssii", $authorId, $titre, $genre, $description, $nbPages, $id);
        } else {
            $sql = "UPDATE books SET author_id = ?, titre = ?, description = ?, nb_pages = ? WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("issii", $authorId, $titre, $description, $nbPages, $id);
        }
    }
    return $stmt->execute();
}

/**
 * Met à jour ou insère le document PDF d'un livre
 */
function updateDocument($mysqli, $bookId, $filename, $filepath, $mime, $size, $userId) {
    $hasIsMain = hasTableColumn($mysqli, 'documents', 'is_main');

    // Supprimer l'ancien fichier physique s'il existe
    $sqlOld = $hasIsMain
        ? "SELECT filepath FROM documents WHERE book_id = ? AND is_main = 1 ORDER BY id ASC LIMIT 1"
        : "SELECT filepath FROM documents WHERE book_id = ? ORDER BY id ASC LIMIT 1";
    $stmt = $mysqli->prepare($sqlOld);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    safeUnlink(resolveDocumentRowAbsolutePath($row ?: []));

    if ($hasIsMain) {
        $sql = "UPDATE documents SET filename = ?, filepath = ?, mime = ?, size = ?, uploaded_by = ?, uploaded_at = NOW()
                WHERE book_id = ? AND is_main = 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssiii", $filename, $filepath, $mime, $size, $userId, $bookId);
        return $stmt->execute();
    }

    // Sans is_main: update du premier document trouvé, sinon insertion
    $stmtId = $mysqli->prepare("SELECT id FROM documents WHERE book_id = ? ORDER BY id ASC LIMIT 1");
    $stmtId->bind_param("i", $bookId);
    $stmtId->execute();
    $existing = $stmtId->get_result()->fetch_assoc();
    $stmtId->close();

    if ($existing) {
        $docId = (int)$existing['id'];
        $sql = "UPDATE documents SET filename = ?, filepath = ?, mime = ?, size = ?, uploaded_by = ?, uploaded_at = NOW() WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssiii", $filename, $filepath, $mime, $size, $userId, $docId);
        return $stmt->execute();
    }

    return addDocument($mysqli, $bookId, $filename, $filepath, $mime, $size, $userId, true);
}

/**
 * Supprime un livre et ses fichiers associés
 */
function deleteBook($mysqli, $id) {
    // 1. Récupérer les chemins des fichiers pour suppression physique
    $sql = "SELECT b.cover_path, d.filepath 
            FROM books b 
            LEFT JOIN documents d ON b.id = d.book_id 
            WHERE b.id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $coverRel = $row['cover_path'] ?? '';
        safeUnlink($coverRel ? resolveDocumentAbsolutePath($coverRel) : null);
        safeUnlink(resolveDocumentAbsolutePath($row['filepath'] ?? ''));
    }
    
    // 2. Supprimer de la DB
    $stmtDocs = $mysqli->prepare("DELETE FROM documents WHERE book_id = ?");
    $stmtDocs->bind_param("i", $id);
    $stmtDocs->execute();

    $stmt = $mysqli->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

/**
 * Récupère les détails d'un document par son ID
 */
function getDocumentById($mysqli, $id) {
    $sql = "SELECT d.*, b.titre AS book_title, a.nom AS author_name, b.nb_pages
            FROM documents d
            JOIN books b ON d.book_id = b.id
            JOIN authors a ON b.author_id = a.id
            WHERE d.id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Met à jour ou insère la progression de lecture en solo
 */
function updateReadingProgress($mysqli, $userId, $bookId, $page) {
    $sql = "INSERT INTO progress_solo (user_id, book_id, page_actuelle) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE page_actuelle = VALUES(page_actuelle)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("iii", $userId, $bookId, $page);
    return $stmt->execute();
}

/**
 * Récupère la progression de lecture d'un utilisateur pour un livre
 */
function getUserProgress($mysqli, $userId, $bookId) {
    $sql = "SELECT page_actuelle FROM progress_solo WHERE user_id = ? AND book_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $userId, $bookId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ? (int)$result['page_actuelle'] : 0;
}

/**
 * Récupère les livres que l'utilisateur est en train de lire (avec progression)
 */
function getUserReadingList($mysqli, $userId) {
    $sql = "SELECT b.id, b.author_id, b.titre, b.genre, b.description, b.cover_path, b.nb_pages,
                   a.nom AS auteur,
                   ps.page_actuelle, ps.updated_at AS last_read,
                   (SELECT AVG(r.note) FROM reviews r WHERE r.book_id = b.id) AS avg_rating
            FROM books b
            JOIN authors a ON b.author_id = a.id
            JOIN progress_solo ps ON b.id = ps.book_id
            WHERE ps.user_id = ?
            ORDER BY ps.updated_at DESC";
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $books = [];
    while ($row = $result->fetch_assoc()) {
        $row['resources_list'] = [];
        $books[] = $row;
    }
    return $books;
}
