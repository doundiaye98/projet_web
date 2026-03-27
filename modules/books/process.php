<?php
defined("SECURE_ACCESS") or die("Accès direct interdit");
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/books');
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'add_book') {
    if (!in_array(getUserRole(), ['admin', 'moderateur'])) {
        $_SESSION['flash_error'] = 'Action non autorisée.';
        header('Location: ' . BASE_URL . '/books');
        exit();
    }

    $titre       = trim($_POST['titre'] ?? '');
    $authorName  = trim($_POST['author_name'] ?? '');
    $genre       = trim($_POST['genre'] ?? '') ?: null;
    $description = trim($_POST['description'] ?? '') ?: null;

    if (empty($titre) || empty($authorName)) {
        $_SESSION['flash_error'] = 'Le titre et l\'auteur sont obligatoires.';
        header('Location: ' . BASE_URL . '/books');
        exit();
    }

    $authorId = getOrCreateAuthor($mysqli, $authorName);
    if (!$authorId) {
        $_SESSION['flash_error'] = "Erreur lors de la création de l'auteur.";
        header('Location: ' . BASE_URL . '/books');
        exit();
    }

    $projectRoot = dirname(dirname(dirname(__FILE__)));
    $coverDir    = $projectRoot . '/uploads/covers';
    $pdfDir      = $projectRoot . '/uploads/books';

    // 1. Upload couverture (optionnel)
    $coverPath = null;
    if (!empty($_FILES['cover']['name'])) {
        $coverResult = uploadCover($_FILES['cover'], $coverDir);
        if (isset($coverResult['error'])) {
            $_SESSION['flash_error'] = $coverResult['error'];
            header('Location: ' . BASE_URL . '/books');
            exit();
        }
        $coverPath = $coverResult['path'];
    }

    // 2. Upload PDF (obligatoire) + détection du nombre de pages
    if (empty($_FILES['pdf']['name'])) {
        if ($coverPath) @unlink($projectRoot . '/' . $coverPath);
        $_SESSION['flash_error'] = 'Le fichier PDF est obligatoire.';
        header('Location: ' . BASE_URL . '/books');
        exit();
    }

    $pdfResult = uploadPdf($_FILES['pdf'], $pdfDir);
    if (isset($pdfResult['error'])) {
        if ($coverPath) @unlink($projectRoot . '/' . $coverPath);
        $_SESSION['flash_error'] = $pdfResult['error'];
        header('Location: ' . BASE_URL . '/books');
        exit();
    }
    
    $nbPages = getPdfPageCount($pdfResult['filepath']);
    if ($nbPages === 0) {
        $nbPages = max(0, (int)($_POST['nb_pages'] ?? 0));
    }

    // 3. Insertion en base
    $bookId = addBook($mysqli, $authorId, $titre, $genre, $description, $coverPath, $nbPages, $_SESSION['user_id']);
    if (!$bookId) {
        if ($coverPath) @unlink($projectRoot . '/' . $coverPath);
        if ($pdfResult['path']) @unlink($pdfResult['filepath']);
        $_SESSION['flash_error'] = "Erreur lors de l'ajout du livre.";
        header('Location: ' . BASE_URL . '/books');
        exit();
    }

    if ($pdfResult['path']) {
        addDocument($mysqli, $bookId, $pdfResult['filename'], $pdfResult['path'], $pdfResult['mime'], $pdfResult['size'], $_SESSION['user_id'], true);
    }

    // 4. Gérer les ressources complémentaires
    if (!empty($_FILES['resources']['name'][0])) {
        foreach ($_FILES['resources']['name'] as $i => $name) {
            $fileobj = [
                'name' => $_FILES['resources']['name'][$i],
                'type' => $_FILES['resources']['type'][$i],
                'tmp_name' => $_FILES['resources']['tmp_name'][$i],
                'error' => $_FILES['resources']['error'][$i],
                'size' => $_FILES['resources']['size'][$i],
            ];
            $resUpload = uploadResource($fileobj, $pdfDir);
            if (!isset($resUpload['error']) && $resUpload['path']) {
                addDocument($mysqli, $bookId, $resUpload['filename'], $resUpload['path'], $resUpload['mime'], $resUpload['size'], $_SESSION['user_id']);
            }
        }
    }

    $pagesMsg = $nbPages > 0 ? " ({$nbPages} pages détectées)" : '';
    $_SESSION['flash_success'] = "Livre ajouté avec succès{$pagesMsg}.";

} elseif ($action === 'update_book') {
    if (!in_array(getUserRole(), ['admin', 'moderateur'])) {
        $_SESSION['flash_error'] = 'Action non autorisée.';
        header('Location: ' . BASE_URL . '/books');
        exit();
    }

    $bookId      = (int)($_POST['book_id'] ?? 0);
    $titre       = trim($_POST['titre'] ?? '');
    $authorName  = trim($_POST['author_name'] ?? '');
    $genre       = trim($_POST['genre'] ?? '') ?: null;
    $description = trim($_POST['description'] ?? '') ?: null;

    if (!$bookId || empty($titre) || empty($authorName)) {
        $_SESSION['flash_error'] = 'Tous les champs obligatoires doivent être remplis.';
        header('Location: ' . BASE_URL . '/books');
        exit();
    }

    $authorId = getOrCreateAuthor($mysqli, $authorName);
    if (!$authorId) {
        $_SESSION['flash_error'] = "Erreur lors de la gestion de l'auteur.";
        header('Location: ' . BASE_URL . '/books');
        exit();
    }

    $projectRoot = dirname(dirname(dirname(__FILE__)));
    $coverDir    = $projectRoot . '/uploads/covers';
    $pdfDir      = $projectRoot . '/uploads/books';

    // 1. Gérer la nouvelle couverture si présente
    $coverPath = null;
    if (!empty($_FILES['cover']['name'])) {
        $coverRes = uploadCover($_FILES['cover'], $coverDir);
        if (isset($coverRes['error'])) {
            $_SESSION['flash_error'] = $coverRes['error'];
            header('Location: ' . BASE_URL . '/books');
            exit();
        }
        $coverPath = $coverRes['path'];
        
        // Supprimer l'ancienne couverture
        $stmt = $mysqli->prepare("SELECT cover_path FROM books WHERE id = ?");
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc();
        if ($old && $old['cover_path'] && file_exists($projectRoot . '/' . $old['cover_path'])) {
            @unlink($projectRoot . '/' . $old['cover_path']);
        }
    }

    // 2. Gérer le nouveau PDF si présent
    $pdfResult = null;
    $nbPages = 0;
    if (!empty($_FILES['pdf']['name'])) {
        $pdfResult = uploadPdf($_FILES['pdf'], $pdfDir);
        if (isset($pdfResult['error'])) {
            if ($coverPath) @unlink($projectRoot . '/' . $coverPath);
            $_SESSION['flash_error'] = $pdfResult['error'];
            header('Location: ' . BASE_URL . '/books');
            exit();
        }
        $nbPages = getPdfPageCount($pdfResult['filepath']);
        if ($nbPages === 0) {
            $nbPages = max(0, (int)($_POST['nb_pages'] ?? 0));
        }
    } else {
        $nbPages = max(0, (int)($_POST['nb_pages'] ?? 0));
    }

    // 3. Mise à jour en base
    if (updateBook($mysqli, $bookId, $authorId, $titre, $genre, $description, $coverPath, $nbPages)) {
        if ($pdfResult && $pdfResult['path']) {
            updateDocument($mysqli, $bookId, $pdfResult['filename'], $pdfResult['path'], $pdfResult['mime'], $pdfResult['size'], $_SESSION['user_id']);
        }

        // 4. Supprimer les ressources décochées
        if (!empty($_POST['delete_resource_ids'])) {
            foreach ($_POST['delete_resource_ids'] as $docId) {
                deleteDocument($mysqli, (int)$docId);
            }
        }

        // 5. Ajouter les nouvelles ressources
        if (!empty($_FILES['resources']['name'][0])) {
            foreach ($_FILES['resources']['name'] as $i => $name) {
                $fileobj = [
                    'name' => $_FILES['resources']['name'][$i],
                    'type' => $_FILES['resources']['type'][$i],
                    'tmp_name' => $_FILES['resources']['tmp_name'][$i],
                    'error' => $_FILES['resources']['error'][$i],
                    'size' => $_FILES['resources']['size'][$i],
                ];
                $resUpload = uploadResource($fileobj, $pdfDir);
                if (!isset($resUpload['error']) && $resUpload['path']) {
                    addDocument($mysqli, $bookId, $resUpload['filename'], $resUpload['path'], $resUpload['mime'], $resUpload['size'], $_SESSION['user_id']);
                }
            }
        }

        $_SESSION['flash_success'] = "Livre mis à jour avec succès !";
    } else {
        if ($coverPath) @unlink($projectRoot . '/' . $coverPath);
        if ($pdfResult && $pdfResult['path']) @unlink($pdfResult['filepath']);
        $_SESSION['flash_error'] = "Erreur lors de la mise à jour du livre.";
    }

} elseif ($action === 'delete_book') {
    if (getUserRole() !== 'admin') {
        $_SESSION['flash_error'] = 'Action non autorisée.';
        header('Location: ' . BASE_URL . '/books');
        exit();
    }

    $bookId = (int)($_POST['book_id'] ?? 0);
    if ($bookId && deleteBook($mysqli, $bookId)) {
        $_SESSION['flash_success'] = "Livre supprimé avec succès !";
    } else {
        $_SESSION['flash_error'] = "Erreur lors de la suppression du livre.";
    }
} elseif ($action === 'add_review') {
    $bookId = (int)($_POST['book_id'] ?? 0);
    $note = (float)($_POST['note'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');
    $userId = $_SESSION['user_id'] ?? 0;

    if (!$bookId || !$userId) {
        $_SESSION['flash_error'] = "Action impossible.";
        header('Location: ' . BASE_URL . '/books');
        exit();
    }

    if ($note < 0 || $note > 5) {
        $_SESSION['flash_error'] = "La note doit être comprise entre 0 et 5.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }

    $visible = in_array(getUserRole(), ['admin', 'moderateur']) ? 1 : 0;

    if (addReview($mysqli, $bookId, $userId, $note, $commentaire, $visible)) {
        if ($visible) {
            $_SESSION['flash_success'] = "Votre avis a été publié !";
        } else {
            $_SESSION['flash_success'] = "Votre avis a été envoyé. Il sera bientôt visible après modération.";
        }
    } else {
        $_SESSION['flash_error'] = "Erreur lors de l'enregistrement de votre avis.";
    }
    
    header("Location: " . BASE_URL . "/books/" . $bookId . "#commentaires");
    exit();
} elseif ($action === 'save_progress') {
    $bookId = (int)($_POST['book_id'] ?? 0);
    $page = (int)($_POST['page_actuelle'] ?? 0);
    $userId = $_SESSION['user_id'] ?? 0;

    if (!$bookId || !$userId) {
        $_SESSION['flash_error'] = "Action impossible.";
        header('Location: ' . BASE_URL . '/books');
        exit();
    }

    $book = getBookDetails($mysqli, $bookId);
    if (!$book) {
        $_SESSION['flash_error'] = "Livre introuvable.";
        header('Location: ' . BASE_URL . '/books');
        exit();
    }

    if ($page < 1 || ($book['nb_pages'] > 0 && $page > $book['nb_pages'])) {
        $max = $book['nb_pages'] > 0 ? " (Max: {$book['nb_pages']})" : "";
        $_SESSION['flash_error'] = "Le numéro de page est invalide{$max}.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }

    if (updateReadingProgress($mysqli, $userId, $bookId, $page)) {
        $_SESSION['flash_success'] = "Progression enregistrée : page {$page} atteinte !";
    } else {
        $_SESSION['flash_error'] = "Erreur lors de l'enregistrement de la progression.";
    }

    header('Location: ' . BASE_URL . '/books/' . $bookId);
    exit();
}

header('Location: ' . BASE_URL . '/books');
exit();
