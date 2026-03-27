<?php

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
    $sql = "SELECT b.id, b.author_id, b.titre, b.genre, b.description, b.cover_path, b.nb_pages, b.created_at,
                   a.nom AS auteur,
                   AVG(rev.note) AS avg_rating,
                   GROUP_CONCAT(CONCAT(d.id, '::', d.filename, '::', d.filepath) ORDER BY d.is_main DESC, d.id ASC SEPARATOR '||') AS resources
            FROM books b
            JOIN authors a ON b.author_id = a.id
            LEFT JOIN documents d ON b.id = d.book_id
            LEFT JOIN reviews rev ON b.id = rev.book_id AND rev.visible = 1
            GROUP BY b.id
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
    $sql = "SELECT b.*, a.nom AS auteur, 
                   AVG(r.note) AS avg_rating, 
                   COUNT(r.id) AS reviews_count,
                   (SELECT id FROM documents WHERE book_id = b.id AND is_main = 1 LIMIT 1) AS main_pdf_id,
                   (SELECT filepath FROM documents WHERE book_id = b.id AND is_main = 1 LIMIT 1) AS main_pdf
            FROM books b
            JOIN authors a ON b.author_id = a.id
            LEFT JOIN reviews r ON b.id = r.book_id AND r.visible = 1
            WHERE b.id = ?
            GROUP BY b.id";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Récupère les ressources complémentaires d'un livre
 */
function getBookResources($mysqli, $id) {
    $sql = "SELECT * FROM documents WHERE book_id = ? AND is_main = 0 ORDER BY id ASC";
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
    $stmt = $mysqli->prepare("SELECT filepath FROM documents WHERE id = ?");
    $stmt->bind_param("i", $docId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    safeUnlink($row['filepath'] ?? null);
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
    $sql = "INSERT INTO books (author_id, titre, genre, description, cover_path, nb_pages, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("issssii", $authorId, $titre, $genre, $description, $coverPath, $nbPages, $userId);
    if ($stmt->execute()) return (int)$mysqli->insert_id;
    return false;
}

/**
 * Insère un document (PDF) lié à un livre dans la table documents
 */
function addDocument($mysqli, $bookId, $filename, $filepath, $mime, $size, $userId, $isMain = false) {
    $sql = "INSERT INTO documents (book_id, filename, filepath, mime, size, is_main, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $mainVal = $isMain ? 1 : 0;
    $stmt->bind_param("isssiii", $bookId, $filename, $filepath, $mime, $size, $mainVal, $userId);
    return $stmt->execute();
}
/**
 * Met à jour un livre (métadonnées + couverture optionnelle)
 */
function updateBook($mysqli, $id, $authorId, $titre, $genre, $description, $coverPath, $nbPages) {
    if ($coverPath) {
        $sql = "UPDATE books SET author_id = ?, titre = ?, genre = ?, description = ?, cover_path = ?, nb_pages = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("issssii", $authorId, $titre, $genre, $description, $coverPath, $nbPages, $id);
    } else {
        $sql = "UPDATE books SET author_id = ?, titre = ?, genre = ?, description = ?, nb_pages = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("isssii", $authorId, $titre, $genre, $description, $nbPages, $id);
    }
    return $stmt->execute();
}

/**
 * Met à jour ou insère le document PDF d'un livre
 */
function updateDocument($mysqli, $bookId, $filename, $filepath, $mime, $size, $userId) {
    // Supprimer l'ancien fichier physique s'il existe
    $stmt = $mysqli->prepare("SELECT filepath FROM documents WHERE book_id = ? AND is_main = 1");
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    safeUnlink($row['filepath'] ?? null);

    $sql = "UPDATE documents SET filename = ?, filepath = ?, mime = ?, size = ?, uploaded_by = ?, uploaded_at = NOW() WHERE book_id = ? AND is_main = 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssiii", $filename, $filepath, $mime, $size, $userId, $bookId);
    return $stmt->execute();
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
        safeUnlink($row['cover_path'] ? __DIR__ . '/../../' . $row['cover_path'] : null);
        safeUnlink($row['filepath'] ?? null);
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
                   AVG(rev.note) AS avg_rating
            FROM books b
            JOIN authors a ON b.author_id = a.id
            JOIN progress_solo ps ON b.id = ps.book_id
            LEFT JOIN reviews rev ON b.id = rev.book_id AND rev.visible = 1
            WHERE ps.user_id = ?
            GROUP BY b.id
            ORDER BY ps.updated_at DESC";
    
    $stmt = $mysqli->prepare($sql);
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
