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
$sessionId = (int) ($_POST['session_id'] ?? 0);

if (!$sessionId || !$userId) {
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

