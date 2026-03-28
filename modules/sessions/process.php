<?php
defined("SECURE_ACCESS") or die("Acces direct interdit");
require_once __DIR__ . '/../../includes/auth/session.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/sessions');
    exit();
}

$action = $_POST['action'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (!$userId) {
    $_SESSION['flash_error'] = "Action impossible.";
    header('Location: ' . BASE_URL . '/sessions');
    exit();
}

if ($action === 'create_session') {
    if (!in_array(getUserRole(), ['admin', 'moderateur'], true)) {
        $_SESSION['flash_error'] = "Acces refuse.";
        header('Location: ' . BASE_URL . '/sessions');
        exit();
    }

    $bookId = (int) ($_POST['book_id'] ?? 0);
    $titre = trim($_POST['titre'] ?? '');
    $dateRaw = trim($_POST['date_heure'] ?? '');
    $lieu = trim($_POST['lieu'] ?? '');
    $lien = trim($_POST['lien'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($bookId < 1 || $titre === '' || $dateRaw === '') {
        $_SESSION['flash_error'] = "Veuillez remplir le livre, le titre et la date.";
        header('Location: ' . BASE_URL . '/sessions');
        exit();
    }

    if (mb_strlen($titre) > 255) {
        $_SESSION['flash_error'] = "Le titre est trop long (255 caracteres max).";
        header('Location: ' . BASE_URL . '/sessions');
        exit();
    }

    $dateHeure = null;
    $normalized = str_replace('T', ' ', $dateRaw);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalized)) {
        $dateHeure = $normalized . ':00';
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $normalized)) {
        $dateHeure = $normalized;
    }
    if ($dateHeure === null) {
        $ts = strtotime($dateRaw);
        if ($ts !== false) {
            $dateHeure = date('Y-m-d H:i:s', $ts);
        }
    }
    if ($dateHeure === null) {
        $_SESSION['flash_error'] = "Date ou heure invalide.";
        header('Location: ' . BASE_URL . '/sessions');
        exit();
    }

    $chk = $mysqli->prepare("SELECT id FROM books WHERE id = ? LIMIT 1");
    if (!$chk) {
        $_SESSION['flash_error'] = "Erreur interne.";
        header('Location: ' . BASE_URL . '/sessions');
        exit();
    }
    $chk->bind_param("i", $bookId);
    $chk->execute();
    if (!$chk->get_result()->fetch_assoc()) {
        $chk->close();
        $_SESSION['flash_error'] = "Livre introuvable.";
        header('Location: ' . BASE_URL . '/sessions');
        exit();
    }
    $chk->close();

    if (mb_strlen($lieu) > 255) {
        $lieu = mb_substr($lieu, 0, 255);
    }
    if (mb_strlen($lien) > 500) {
        $lien = mb_substr($lien, 0, 500);
    }

    $sql = "INSERT INTO sessions (book_id, titre, date_heure, lieu, lien, description, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        $_SESSION['flash_error'] = "Erreur lors de la creation.";
        header('Location: ' . BASE_URL . '/sessions');
        exit();
    }

    $stmt->bind_param(
        "isssssi",
        $bookId,
        $titre,
        $dateHeure,
        $lieu,
        $lien,
        $description,
        $userId
    );
    if ($stmt->execute()) {
        $_SESSION['flash_success'] = "Session creee.";
    } else {
        $_SESSION['flash_error'] = "Impossible d'enregistrer la session.";
    }
    $stmt->close();

    header('Location: ' . BASE_URL . '/sessions');
    exit();
}

$sessionId = (int) ($_POST['session_id'] ?? 0);

if (!$sessionId) {
    $_SESSION['flash_error'] = "Action impossible.";
    header('Location: ' . BASE_URL . '/sessions');
    exit();
}

$stmt = $mysqli->prepare("SELECT id, date_heure FROM sessions WHERE id = ? LIMIT 1");
if (!$stmt) {
    $_SESSION['flash_error'] = "Erreur interne.";
    header('Location: ' . BASE_URL . '/sessions');
    exit();
}
$stmt->bind_param("i", $sessionId);
$stmt->execute();
$sessionRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sessionRow) {
    $_SESSION['flash_error'] = "Session introuvable.";
    header('Location: ' . BASE_URL . '/sessions');
    exit();
}

$isPast = strtotime($sessionRow['date_heure']) < time();

if ($action === 'join_session') {
    if ($isPast) {
        $_SESSION['flash_error'] = "Impossible de s'inscrire a une session terminee.";
        header('Location: ' . BASE_URL . '/sessions');
        exit();
    }

    $sql = "INSERT INTO session_attendance (session_id, user_id, statut)
            VALUES (?, ?, 'inscrit')
            ON DUPLICATE KEY UPDATE statut = 'inscrit'";
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $sessionId, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        $_SESSION['flash_success'] = $ok ? "Participation enregistree." : "Erreur lors de l'inscription.";
    } else {
        $_SESSION['flash_error'] = "Erreur lors de l'inscription.";
    }
} elseif ($action === 'leave_session') {
    $stmt = $mysqli->prepare("DELETE FROM session_attendance WHERE session_id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $sessionId, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        $_SESSION['flash_success'] = $ok ? "Participation annulee." : "Erreur lors de l'annulation.";
    } else {
        $_SESSION['flash_error'] = "Erreur lors de l'annulation.";
    }
} else {
    $_SESSION['flash_error'] = "Action invalide.";
}

header('Location: ' . BASE_URL . '/sessions');
exit();
