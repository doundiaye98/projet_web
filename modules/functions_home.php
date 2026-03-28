<?php
// Fonctions pour la page d'accueil (Accueil)

function getStats($mysqli, $userId, $userRole) {
    $stats = [
        'books' => 0,
        'my_books' => 0,
        'reviews' => 0,
        'sessions' => 0,
        'users' => 0,
    ];
    $result = $mysqli->query("SELECT COUNT(*) AS total FROM books");
    if ($result) {
        $stats['books'] = (int) ($result->fetch_assoc()['total'] ?? 0);
    }
    // Nombre de livres lus/commencés par l'utilisateur
    $stmt = $mysqli->prepare("SELECT COUNT(DISTINCT book_id) AS total FROM progress_solo WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stats['my_books'] = (int) ($row['total'] ?? 0);
        $stmt->close();
    }
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM reviews WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stats['reviews'] = (int) ($row['total'] ?? 0);
        $stmt->close();
    }
    $result = $mysqli->query("SELECT COUNT(*) AS total FROM sessions WHERE date_heure >= NOW()");
    if ($result) {
        $stats['sessions'] = (int) ($result->fetch_assoc()['total'] ?? 0);
    }
    if ($userRole === 'admin') {
        $result = $mysqli->query("SELECT COUNT(*) AS total FROM users");
        if ($result) {
            $stats['users'] = (int) ($result->fetch_assoc()['total'] ?? 0);
        }
    }
    return $stats;
}

function getUpcomingSessions($mysqli) {
    $upcomingSessions = [];
    $sql = "
        SELECT s.id, s.titre, s.date_heure, s.lieu, b.titre AS book_title
        FROM sessions s
        JOIN books b ON b.id = s.book_id
        WHERE s.date_heure >= NOW()
        ORDER BY s.date_heure ASC
        LIMIT 5
    ";
    $result = $mysqli->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $upcomingSessions[] = $row;
        }
    }
    return $upcomingSessions;
}

function getReadingProgress($mysqli, $userId) {
    $readingProgress = [];
    $progressStats = [
        'average' => 0,
        'completed' => 0,
    ];
    $sql = "
        SELECT
            b.id,
            b.titre,
            b.nb_pages,
            ps.page_actuelle,
            ps.updated_at
        FROM progress_solo ps
        JOIN books b ON b.id = ps.book_id
        WHERE ps.user_id = ?
        ORDER BY ps.updated_at DESC
        LIMIT 5
    ";
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $totalPercent = 0;
        $countPercent = 0;
        while ($row = $result->fetch_assoc()) {
            $nbPages = (int) ($row['nb_pages'] ?? 0);
            $currentPage = (int) ($row['page_actuelle'] ?? 0);
            $percent = 0;
            if ($nbPages > 0) {
                $percent = (int) round(min(100, ($currentPage / $nbPages) * 100));
                $totalPercent += $percent;
                $countPercent++;
                if ($currentPage >= $nbPages) {
                    $progressStats['completed']++;
                }
            }
            $row['percent'] = $percent;
            $readingProgress[] = $row;
        }
        if ($countPercent > 0) {
            $progressStats['average'] = (int) round($totalPercent / $countPercent);
        }
        $stmt->close();
    }
    return [$readingProgress, $progressStats];
}
