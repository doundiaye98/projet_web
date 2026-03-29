<?php
defined("SECURE_ACCESS") or die("Acces direct interdit");

/**
 * Rend une URL de réunion / visioconf clickable (ajoute https si le schéma est omis).
 */
function normalize_session_join_url($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    $lower = strtolower($url);
    if (strpos($lower, 'mailto:') === 0 || strpos($lower, 'tel:') === 0) {
        return $url;
    }
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }
    if (strpos($url, '//') === 0) {
        return 'https:' . $url;
    }
    if (isset($url[0]) && $url[0] === '/') {
        return $url;
    }
    return 'https://' . $url;
}

/**
 * Recupere toutes les sessions avec les participants et le statut de l'utilisateur
 */
function getSessionsList($mysqli, $userId) {
    $sessions = [];
    $sql = "
        SELECT
            s.id,
            s.book_id,
            s.titre,
            s.date_heure,
            s.lieu,
            s.lien,
            s.description,
            s.created_by,
            b.titre AS book_title,
            b.nb_pages,
            u.nom AS creator_name,
            SUM(CASE WHEN sa.statut IN ('inscrit', 'present') THEN 1 ELSE 0 END) AS participants_count,
            MAX(CASE WHEN sa.user_id = ? THEN sa.statut ELSE NULL END) AS my_status,
            (SELECT page_actuelle FROM progress_session ps2 
             WHERE ps2.session_id = s.id AND ps2.user_id = s.created_by LIMIT 1) as session_progress
        FROM sessions s
        JOIN books b ON b.id = s.book_id
        JOIN users u ON u.id = s.created_by
        LEFT JOIN session_attendance sa ON sa.session_id = s.id
        GROUP BY s.id, s.book_id, s.titre, s.date_heure, s.lieu, s.lien, s.description, s.created_by, b.titre, b.nb_pages, u.nom
        ORDER BY s.date_heure ASC
    ";

    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }
        $stmt->close();
    }
    return $sessions;
}

/**
 * Recupere les livres pour le menu de creation de session
 */
function getBooksForSessions($mysqli) {
    $books = [];
    $bq = $mysqli->query(
        "SELECT b.id, b.titre, a.nom AS auteur
         FROM books b
         JOIN authors a ON a.id = b.author_id
         ORDER BY b.titre ASC"
    );
    if ($bq) {
        while ($row = $bq->fetch_assoc()) {
            $books[] = $row;
        }
    }
    return $books;
}

/**
 * Cree une session et inscrit automatiquement le createur
 */
function createSessionWithCreator($mysqli, $creatorId, $data) {
    $bookId = (int) $data['book_id'];
    $titre = $data['titre'];
    $dateHeure = $data['date_heure'];
    $lieu = $data['lieu'];
    $lien = $data['lien'];
    $description = $data['description'];

    $sql = "INSERT INTO sessions (book_id, titre, date_heure, lieu, lien, description, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;

    $stmt->bind_param("isssssi", $bookId, $titre, $dateHeure, $lieu, $lien, $description, $creatorId);
    $ok = $stmt->execute();
    
    if ($ok) {
        $newSessionId = $mysqli->insert_id;
        // Inscription automatique du createur
        $attStmt = $mysqli->prepare("INSERT INTO session_attendance (session_id, user_id, statut) VALUES (?, ?, 'inscrit')");
        if ($attStmt) {
            $attStmt->bind_param("ii", $newSessionId, $creatorId);
            $attStmt->execute();
            $attStmt->close();
        }
    }
    
    $stmt->close();
    return $ok;
}

/**
 * Supprime une session (Vérification admin dans process.php)
 */
function deleteSession($mysqli, $sessionId) {
    $stmt = $mysqli->prepare("DELETE FROM sessions WHERE id = ?");
    if (!$stmt) return false;
    $stmt->bind_param("i", $sessionId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Joint une session
 */
function joinSession($mysqli, $sessionId, $userId) {
    $sql = "INSERT INTO session_attendance (session_id, user_id, statut)
            VALUES (?, ?, 'inscrit')
            ON DUPLICATE KEY UPDATE statut = 'inscrit'";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("ii", $sessionId, $userId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Quitte une session
 */
function leaveSession($mysqli, $sessionId, $userId) {
    $stmt = $mysqli->prepare("DELETE FROM session_attendance WHERE session_id = ? AND user_id = ?");
    if (!$stmt) return false;
    $stmt->bind_param("ii", $sessionId, $userId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}
/**
 * Synchronise la progression de tous les participants d'une session (Modo/Admin uniquement)
 */
function syncSessionProgress($mysqli, $sessionId, $page) {
    $sql = "INSERT INTO progress_session (user_id, book_id, session_id, page_actuelle)
            SELECT sa.user_id, s.book_id, s.id, ? 
            FROM session_attendance sa
            JOIN sessions s ON s.id = sa.session_id
            WHERE s.id = ?
            ON DUPLICATE KEY UPDATE page_actuelle = VALUES(page_actuelle)";
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("ii", $page, $sessionId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Recupere la progression des sessions pour un utilisateur et un livre
 */
function getBookSessionsProgress($mysqli, $userId, $bookId) {
    $progress = [];
    $sql = "SELECT 
                ps.page_actuelle, 
                s.titre AS session_title, 
                s.id AS session_id,
                b.nb_pages
            FROM progress_session ps
            JOIN sessions s ON ps.session_id = s.id
            JOIN books b ON ps.book_id = b.id
            WHERE ps.user_id = ? AND ps.book_id = ?
            ORDER BY s.date_heure DESC";
            
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $userId, $bookId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $progress[] = $row;
        }
        $stmt->close();
    }
    return $progress;
}

/**
 * Verifie si un utilisateur est le createur d'une session ou admin
 */
function canManageSession($mysqli, $sessionId, $userId, $userRole) {
    if ($userRole === 'admin') return true;
    
    $stmt = $mysqli->prepare("SELECT created_by FROM sessions WHERE id = ?");
    if (!$stmt) return false;
    $stmt->bind_param("i", $sessionId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $res && (int)$res['created_by'] === (int)$userId;
}
