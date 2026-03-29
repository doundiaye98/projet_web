<?php
/**
 * Rend un avis visible (modération)
 */
function acceptReview($mysqli, $reviewId) {
    $stmt = $mysqli->prepare("UPDATE reviews SET visible = 1 WHERE id = ?");
    $stmt->bind_param("i", $reviewId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Supprime un avis (modération)
 */
function deleteReview($mysqli, $reviewId) {
    $stmt = $mysqli->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $reviewId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

// Fonctions pour la modération avec requêtes préparées
require_once __DIR__ . '/../../config/db.php';

function getModerationStats($mysqli) {
    $stmt = $mysqli->prepare("
        SELECT
            (SELECT COUNT(*) FROM reviews WHERE visible = 0) AS hidden_reviews,
            (SELECT COUNT(DISTINCT book_id) FROM reviews WHERE visible = 0) AS reported_books,
            (SELECT COUNT(*) FROM users WHERE role IN ('admin', 'moderateur') AND statut = 'actif') AS active_moderators
    ");
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: ['hidden_reviews' => 0, 'reported_books' => 0, 'active_moderators' => 0];
}

function getHiddenReviews($mysqli, $limit = 8) {
    $stmt = $mysqli->prepare("
        SELECT r.id, r.note, r.commentaire, r.created_at, b.titre AS book_title, u.nom AS author_name
        FROM reviews r
        JOIN books b ON b.id = r.book_id
        JOIN users u ON u.id = r.user_id
        WHERE r.visible = 0
        ORDER BY r.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    $stmt->close();
    return $reviews;
}
